<?php

namespace Drupal\memcache_storage;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class DrupalMemcache
 *
 *  * Contains integration for Drupal & PECL Memcache extension.
 *
 * @package Drupal\memcache_storage
 */
class DrupalMemcache extends DrupalMemcachedBase {

  /**
   * Defines pecl extension to use.
   *
   * @var string
   */
  protected $extension = 'Memcache';

  /**
   * Contains setting about pecl memcache compression.
   * @see http://php.net/manual/en/memcache.setcompressthreshold.php
   *
   * @var array
   */
  protected $compressThreshold = ['threshold' => 20000, 'min_savings' => 0.2];

  /**
   * Indicates if compression should be enabled.
   * @see http://php.net/manual/en/memcache.set.php
   *
   * @var bool
   */
  protected $compressionEnabled;

  /**
   * Indicates if compression should be enabled.
   * @see http://php.net/manual/en/memcache.addserver.php
   *
   * @var bool
   */
  protected $persistentConnection;

  /**
   * @var \Memcache.
   */
  protected $memcached;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $cluster_name) {
    parent::__construct($settings, $cluster_name);

    // For more information see
    // http://www.php.net/manual/en/memcache.setcompressthreshold.php.
    if (!empty($settings['compress_threshold'])) {
      $this->compressThreshold = $settings['compress_threshold'];
    }
    if (isset($this->compressThreshold['threshold']) && isset($this->compressThreshold['min_savings'])) {
      $this->memcached->setCompressThreshold($this->compressThreshold['threshold'], $this->compressThreshold['min_savings']);
    }

    // See http://php.net/manual/en/memcache.addserver.php
    $this->persistentConnection = !empty($settings['persistent_connection']);

    // See http://php.net/manual/en/memcache.set.php
    $this->compressionEnabled = !empty($settings['compression_enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function getStats() {
    // Supress errors, because if one of the memcached servers is not connected
    // it will throw php warning, but we handle this case ourselves.
    return @$this->memcached->getExtendedStats();
  }

  /**
   * {@inheritdoc}
   */
  public function addServer($host, $port) {
    $this->memcached->addserver($host, $port, $this->persistentConnection);
  }

  /**
   * {@inheritdoc}
   */
  public function setMulti(array $items, $cache_bin = '') {

    // No point in performing any action is we're not connected to memcached.
    if (empty($this->isConnected)) {
      return;
    }

    // PECL memcache doesn't support multiple set, so just loop through
    // every cache item and set it.
    foreach ($items as $item) {

      // Get formatted cache key.
      $memcached_key = $this->itemKey($item->cid, $cache_bin);

      // Prepare the expiration time for memcached.
      $expiration = $item->expire;
      if ($item->expire == CacheBackendInterface::CACHE_PERMANENT) {
        $expiration = 0;
      }

      // Perform preparations for the debug logging.
      if (!empty($this->debug)) {
        DrupalMemcachedDebug::prepare();
      }

      // Set the value to the memcached pool.
      $compression = !empty($this->compressionEnabled) ? MEMCACHE_COMPRESSED : 0;
      $result = $this->memcached->set($memcached_key, $item, $compression, $expiration);

      // Logs the debug entry about the memcached operation.
      if (!empty($this->debug)) {
        $memcached_keys = [$memcached_key => $item->cid];
        DrupalMemcachedDebug::process('set', $result, $memcached_keys, $cache_bin, $this->cluster);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMulti(array $keys, $cache_bin = '') {

    // No point in performing any action is we're not connected to memcached.
    if (empty($this->isConnected)) {
      return [];
    }

    // Format every cache key before the request to memcached pool.
    $memcached_keys = [];
    foreach ($keys as $key) {
      $memcached_key = $this->itemKey($key, $cache_bin);
      $memcached_keys[$memcached_key] = $key;
    }

    // Perform preparations for the debug logging.
    if (!empty($this->debug)) {
      DrupalMemcachedDebug::prepare();
    }

    // Get all cache items from memcached.
    $compression = !empty($this->compressionEnabled) ? MEMCACHE_COMPRESSED : 0;
    $result = $this->memcached->get(array_keys($memcached_keys), $compression);

    // Logs the debug entry about the memcached operation.
    if (!empty($this->debug)) {
      DrupalMemcachedDebug::process('get', $result, $memcached_keys, $cache_bin, $this->cluster);
    }

    // Replace formatted memcached keys by Drupal keys.
    $cache = [];
    foreach ($result as $memcached_key => $value) {
      $normal_key = $memcached_keys[$memcached_key];
      $cache[$normal_key] = $value;
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMulti(array $keys, $cache_bin = '') {

    // No point in performing any action is we're not connected to memcached.
    if (empty($this->isConnected)) {
      return;
    }

    // PECL memcache doesn't support multiple deletion of elements, so
    // loop through all elements and delete one by one.
    foreach ($keys as $key) {
      $memcached_key = $this->itemKey($key, $cache_bin);

      // Perform preparations for the debug logging.
      if (!empty($this->debug)) {
        DrupalMemcachedDebug::prepare();
      }

      $result = $this->memcached->delete($memcached_key);

      // Logs the debug entry about the memcached operation.
      if (!empty($this->debug)) {
        $memcached_keys = [$memcached_key => $key];
        DrupalMemcachedDebug::process('delete', $result, $memcached_keys, $cache_bin, $this->cluster);
      }
    }
  }

}
