<?php

namespace Drupal\memcache_storage;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class DrupalMemcached
 *
 * Contains integration for Drupal & PECL Memcached extension.
 *
 * @package Drupal\memcache_storage
 */
class DrupalMemcached extends DrupalMemcachedBase {

  /**
   * Defines pecl extension to use.
   *
   * @var string
   */
  protected $extension = 'Memcached';

  /**
   * Contains list of pecl memcached settings.
   *
   * @var array
   */
  protected $options;

  /**
   * Contains username and password for memcached SASL authentication.
   *
   * @var array
   */
  protected $saslAuth;

  /**
   * Default pecl memcached options.
   * Can be overriden in settings.
   *
   * @var array
   */
  protected $optionsDefault = [
    \Memcached::OPT_COMPRESSION => FALSE,
    \Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
  ];

  /**
   * Pecl memcached object.
   *
   * @var \Memcached.
   */
  protected $memcached;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $cluster_name) {
    parent::__construct($settings, $cluster_name);

    // For more info about memcached constants see
    // http://www.php.net/manual/en/memcached.constants.php.
    $this->options = !empty($settings['memcached_options']) ?  $settings['memcached_options'] : [];
    $this->options += $this->optionsDefault;

    // Add SASL support.
    // See http://php.net/manual/en/memcached.setsaslauthdata.php
    if (!empty($this->settings['sasl_auth']['user']) && !empty($this->settings['sasl_auth']['password'])) {
      $this->memcached->setSaslAuthData($this->settings['sasl_auth']['user'], $this->settings['sasl_auth']['password']);

      // SASL auth works only with binary protocol.
      $this->options[\Memcached::OPT_BINARY_PROTOCOL] = TRUE;
    }

    // Set pecl memcached options.
    // See http://php.net/manual/en/memcached.setoptions.php
    $this->memcached->setOptions($this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function getStats() {
    return $this->memcached->getStats();
  }

  /**
   * {@inheritdoc}
   */
  public function addServer($host, $port) {
    $this->memcached->addServer($host, $port);
  }

  /**
   * {@inheritdoc}
   */
  public function setMulti(array $items, $cache_bin = '') {

    // No point in performing any action is we're not connected to memcached.
    if (empty($this->isConnected)) {
      return;
    }

    // To perform a multiple set operation we have to group cache items
    // by the expiration time.
    // See http://php.net/manual/en/memcached.setmulti.php
    $item_groups = [];
    foreach ($items as $item) {

      // Get the formatted cache key.
      $memcached_key = $this->itemKey($item->cid, $cache_bin);

      // Prepare the expiration time for memcached.
      $expiration = $item->expire;
      if ($item->expire == CacheBackendInterface::CACHE_PERMANENT) {
        $expiration = 0;
      }

      $item_groups[$expiration][$memcached_key] = $item;
    }

    // Multiple set of cache items.
    foreach ($item_groups as $expiration => $cache_items) {

      // Perform preparations for the debug logging.
      if (!empty($this->debug)) {
        DrupalMemcachedDebug::prepare();

        // Prepare the matching between memcached keys and drupal cache ids.
        $memcached_keys = [];
        foreach ($cache_items as $cache_key => $item) {
          $memcached_keys[$cache_key] = $item->cid;
        }
      }

      $result = $this->memcached->setMulti($cache_items, $expiration);

      // Logs the debug entry about the memcached operation.
      if (!empty($this->debug)) {
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
    $result = $this->memcached->getMulti(array_keys($memcached_keys));

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

    // Make a request to delete all cache items.
    $result = $this->memcached->deleteMulti(array_keys($memcached_keys));

    // Logs the debug entry about the memcached operation.
    if (!empty($this->debug)) {
      DrupalMemcachedDebug::process('delete', $result, $memcached_keys, $cache_bin, $this->cluster);
    }
  }

}
