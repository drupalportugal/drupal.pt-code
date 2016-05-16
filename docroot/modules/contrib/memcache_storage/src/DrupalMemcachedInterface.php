<?php

namespace Drupal\memcache_storage;

interface DrupalMemcachedInterface {

  /**
   * Builds a new DrupalMemcache(d) object.
   *
   * @param array $settings
   *   Array of Settings:get('memcache_storage') settings.
   *
   * @param $cluster_name
   *   Name of the memcached cluster.
   *   Default name is 'default'.
   */
  public function __construct(array $settings, $cluster_name);

  /**
   * @param $servers
   *   List of services related to the current cluster.
   *
   * @return bool
   *   Connection status.
   */
  public function addServers($servers);

  /**
   * Adds a new memcached server.
   *
   * @param $host
   *   Memcached host name.
   *   For example:
   *   - 127.0.0.1
   *   - /path/to/memcached.socket.
   *
   * @param $port
   *   Port for memcached host.
   *   Always 0 for unix sockets.
   */
  public function addServer($host, $port);

  /**
   * Returns info about connected memached servers.
   *
   * @return array
   *   Info about connected memached servers.
   */
  public function getStats();

  /**
   * Set the cache to the memcached.
   *
   * @param $key
   *   Cache key string.
   *
   * @param $value
   *   Cache value.
   *
   * @param int $expiration
   *   Timestamp of cache expiration date.
   *   If cache is permanent - then the value is 0.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   */
  public function set($key, $value, $expiration = 0, $cache_bin = '');

  /**
   * Bulk set if cache items to the memcached pool.
   *
   * @param array $items
   *   List of cache items.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   */
  public function setMulti(array $items, $cache_bin = '');

  /**
   * Get the cache item from memcached.
   *
   * @param $key
   *   Cache item key string.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   *
   * @return mixed
   *   Cache data or empty, if not found.
   */
  public function get($key, $cache_bin = '');

  /**
   * Get the multiple cache items from the memcached pool.
   *
   * @param array $keys
   *   List of cache keys to receive.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   *
   * @return array
   *   List of cached data with cache keys as array keys.
   */
  public function getMulti(array $keys, $cache_bin = '');

  /**
   * Delete the cache item from memcached.
   *
   * @param $key
   *   Cache item key string.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   */
  public function delete($key, $cache_bin = '');

  /**
   * Bulk delete from the memcached pool.
   *
   * @param array $keys
   *   List of cache keys to delete.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   */
  public function deleteMulti(array $keys, $cache_bin = '');

  /**
   * Reset the cache for the entire cache bin.
   *
   * @param string $cache_bin
   *   Name of the cache bin.
   */
  public function flush($cache_bin = '');

}
