<?php

namespace Drupal\memcache_storage;

use Psr\Log\LogLevel;

/**
 * Class DrupalMemcachedBase
 *
 * Provides a shared code base for DrupalMemcache and DrupalMemcached
 * classes.
 *
 * @package Drupal\memcache_storage
 */
abstract class DrupalMemcachedBase implements DrupalMemcachedInterface {

  /**
   * @var \Memcached or \Memcache object
   */
  protected $memcached;

  /**
   * Array of Settings:get('memcache_storage') settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Prefix for each memcached key.
   *
   * @var string
   */
  protected $keyPrefix = '';

  /**
   * The algorithm for hashing memcached keys longer than 250 chars.
   * By default sha1 is chosen because it performs quickly with minimal
   * collisions.
   *
   * @var string
   */
  protected $hashAlgorithm = 'sha1';

  /**
   * Name of pecl extension.
   * Currently supported only 'Memcache' and 'Memcached'.
   *
   * @var string
   */
  protected $extension;

  /**
   * Default list of memcached servers and its cluster name.
   *
   * @var array
   */
  protected $serversDefault = ['127.0.0.1:11211' => 'default'];

  /**
   * List of memcached servers referenced to the current cluster.
   *
   * @var array
   */
  protected $servers = [];

  /**
   * The name of the current memcached cluster.
   *
   * @var string
   */
  protected $cluster;

  /**
   * Indicates the connection to memcached servers.
   *
   * @var bool
   */
  protected $isConnected;

  /**
   * Status of the debug mode.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * Internal variable. Contains special int indexes
   * for each cache bin. The bin index included into building of
   * memcached key. See itemKey() method for more info.
   *
   * @var array
   */
  private $bin_indexes;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $cluster_name) {

    // Initialize pecl memcache(d) object.
    $this->memcached = new $this->extension();

    // Save settings related to memcache_storage.
    $this->settings = $settings;

    // Save memcached key prefix setting.
    if (!empty($settings['key_prefix'])) {
      $this->keyPrefix = $settings['key_prefix'];
    }

    // Turn on the debug mode.
    if (!empty($settings['debug'])) {
      $this->debug = TRUE;
    }

    // Get the hash algorithm for hashing memcached keys longer than 250 chars.
    // By default sha1 is chosen because it performs quickly with minimal
    // collisions.
    if (!empty($settings['key_hash_algorytm'])) {
      $this->hashAlgorithm = $settings['key_hash_algorytm'];
    }

    // Keep the cluster name.
    $this->cluster = $cluster_name;

    // Get a list of all servers from settings.
    $servers = !empty($this->settings['memcached_servers']) ? $this->settings['memcached_servers'] : $this->serversDefault;

    // Filter servers related to the current cluster.
    foreach ($servers as $server => $name) {
      if ($name == $this->cluster) {
        $this->servers[] = $server;
      }
    }

    // Add servers to the pecl memcache(d) object and return connection
    // status.
    $this->isConnected = $this->addServers($this->servers);
  }

  /**
   * {@inheritdoc}
   */
  public function addServers($servers) {

    // If server list is empty, then something is configured wrong.
    if (empty($servers)) {
      DrupalMemcachedUtils::log(LogLevel::ERROR, 'Could not find servers for the cluster %cluster.', ['%cluster' => $this->cluster]);
      return FALSE;
    }

    // Add each server to pecl memcache(d) object. Point your attention that
    // addServer() method first calls method from DrupalMemcache(d) class.
    foreach ($servers as $server) {
      list($host, $port) = DrupalMemcachedUtils::parseServerInfo($server);
      $this->addServer($host, $port);
    }

    // Get information about connected memcached servers.
    $stats = $this->getStats();
    $failed_connections = [];
    foreach ($stats as $server => $server_stats) {
      // If uptime info is empty - then this server is not available.
      if (empty($server_stats['uptime'])) {
        $failed_connections[] = $server;
        DrupalMemcachedUtils::log(LogLevel::WARNING, 'Could not connect to the server %server.', ['%server' => $server]);
      }
    }

    // If memcached is unable to connect to all servers it means that
    // we have no connections at all, and could not start memcached connection.
    if (sizeof($failed_connections) == sizeof($stats)) {
      DrupalMemcachedUtils::log(LogLevel::ERROR, 'Could not connect to all servers from the cluster %cluster.', ['%cluster' => $this->cluster]);
      return FALSE;
    }

    // Successfully connected to all servers, yay!
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value, $expiration = 0, $cache_bin = '') {
    // Simply prepate the object and execute multiple set method.
    $item = new \stdClass();
    $item->cid = $key;
    $item->expire = $expiration;
    $item->data = $value;
    $this->setMulti([$item], $cache_bin);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $cache_bin = '') {
    $cache = $this->getMulti([$key], $cache_bin = '');
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key, $cache_bin = '') {
    $this->deleteMulti([$key], $cache_bin);
  }

  /**
   * {@inheritdoc}
   */
  public function flush($cache_bin = '') {

    // No point in performing any action is we're not connected to memcached.
    if (empty($this->isConnected)) {
      return;
    }

    // Memcached doesn't support the flushing by cache bin because of its
    // architecture. Although we've got a workaround - we include a special
    // int number for every cache bin into the cache key. So as soon as this
    // index changes then key for every item changes and it leads to cache
    // rebuild.
    $this->increaseBinIndex($cache_bin);
  }

  /**
   * Return the formatted cache key as it will be stored in memcached.
   *
   * @param $key
   *   Cache item key string.
   *
   * @param $cache_bin
   *   Name of the cache bin.
   *
   * @return string
   *   Formatted cache key.
   */
  final public function itemKey($key, $cache_bin) {

    // Build unique cache key.
    $cache_key  = !empty($this->keyPrefix) ? $this->keyPrefix . '-' : '';
    $cache_key .= $cache_bin ? $cache_bin . $this->getBinIndex($cache_bin) . '-' : '';
    $cache_key .= $key;
    $cache_key = urlencode($cache_key);

    // Memcache only supports key length up to 250 bytes. If we have generated
    // a longer key, hash it with md5 which will shrink the key down to 32 bytes
    // while still keeping it unique.
    if (strlen($cache_key) > 250) {
      $cache_key = urlencode(hash($this->hashAlgorithm, $cache_key));
    }

    return $cache_key;
  }

  /**
   * Increase cache bin index.
   * This operation changes all memcache keys in the specified cache bin,
   * so we fake the cache flush operation.
   *
   * @param $cache_bin
   *   Name of the cache bin.
   */
  final protected function increaseBinIndex($cache_bin) {

    // We force reload the data from memcached to avoid race conditions with
    // other threads who could already increase bin index for the cache bin.
    // TODO: Lock this operation to avoid race conditions?
    $cache = $this->get('memcache_storage_bin_indexes');

    if (empty($cache->data)) {
      $this->bin_indexes[$cache_bin] = 1;
    }
    else {
      $this->bin_indexes = $cache->data;
      $current_index = !empty($cache->data[$cache_bin]) ? $cache->data[$cache_bin] : 0;
      $this->bin_indexes[$cache_bin] = ++$current_index;
    }

    $this->set('memcache_storage_bin_indexes', $this->bin_indexes, 0);
  }

  /**
   * Returns cache bin index.
   * This index is part of memcache key and changes if cache bin should be
   * cleared.
   *
   * @param $cache_bin
   *   Name of the cache bin.
   */
  final protected function getBinIndex($cache_bin) {

    // If the variable is not initialized - then make an attempt to load
    // the data from cache.
    if (!isset($this->bin_indexes)) {
      $this->bin_indexes = [];

      // An attempt to get bin indexes from cache.
      $cache = $this->get('memcache_storage_bin_indexes');
      if (!empty($cache->data)) {
        $this->bin_indexes = $cache->data;
      }
    }

    // If info about bin doesn't exist, then initialize it with the default
    // value.
    if (empty($this->bin_indexes[$cache_bin])) {
      $this->bin_indexes[$cache_bin] = 1; // Initial index value.
      $this->set('memcache_storage_bin_indexes', $this->bin_indexes, 0);
    }

    return $this->bin_indexes[$cache_bin];
  }

}
