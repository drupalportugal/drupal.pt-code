<?php

namespace Drupal\memcache_storage;

use Drupal\Component\Utility\Timer;

/**
 * Class DrupalMemcachedDebug
 * @package Drupal\memcache_storage
 */
class DrupalMemcachedDebug {

  /**
   * Contains info about all memcached operations on the page.
   *
   * @var array
   */
  protected static $log = [];

  /**
   * Internal counter for each memcached operation.
   *
   * @var int
   */
  protected static $counter = 0;

  /**
   * Returns table element with summary debug information
   * about all memcached transactions.
   *
   * @return array|bool
   */
  public static function getSummaryLogTable() {
    if (empty(self::$log)) {
      return FALSE;
    }

    $common_stats = [];
    foreach (self::$log as $key => $entry) {
      $operation = $entry['operation'];

      if (empty($common_stats[$operation])) {
        $common_stats[$operation]['operation'] = $operation;
        $common_stats[$operation]['time'] = 0;
        $common_stats[$operation]['hit']  = 0;
        $common_stats[$operation]['miss'] = 0;
      }

      $status = !empty($entry['result']) ? 'hit' : 'miss';
      $common_stats[$operation][$status]++;

      // Do not collect statistics if previos row is same as current.
      if (isset(self::$log[$key-1]) &&
        self::$log[$key-1]['used_time'] == $entry['used_time'] &&
        self::$log[$key-1]['operation'] == $entry['operation'] &&
        strpos($entry['operation'], 'Multi') !== FALSE) {
        continue;
      }

      $common_stats[$operation]['time'] += $entry['used_time'];
    }

    foreach ($common_stats as &$stats) {
      $stats['hit']  = $stats['hit']  . ' / ' . number_format($stats['hit']  / ($stats['hit'] + $stats['miss']) * 100, 1) . '%';
      $stats['miss'] = $stats['miss'] . ' / ' . number_format($stats['miss'] / ($stats['hit'] + $stats['miss']) * 100, 1) . '%';
    }

    return [
      '#type' => 'table',
      '#header' => [
        t('Action'),
        t('Total time, ms'),
        t('Total hits / %'),
        t('Total misses / %')
      ],
      '#rows' => $common_stats,
      '#attributes' => ['id' => 'memcache-storage-common-debug'],
      '#attached' => ['library' => ['memcache_storage/debug-table']],
    ];
  }

  /**
   * Returns table element with detailed debug information
   * about all memcached transactions.
   *
   * @return array|bool
   */
  public static function getDetailedLogTable() {
    if (empty(self::$log)) {
      return FALSE;
    }

    $rows = [];
    foreach (self::$log as $entry) {
      $entry['result'] = !empty($entry['result']) ? 'HIT' : 'MISS';
      $class = strtolower($entry['result']);
      $rows[] = [
        'data' => $entry,
        'class' => [$class],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        t('Operation'),
        t('Time, ms'),
        t('Result'),
        t('Cache bin'),
        t('Cache key'),
        t('Memcached key'),
        t('Memcached cluster')
      ],
      '#rows' => $rows,
      '#attributes' => ['id' => 'memcache-storage-detailed-debug'],
      '#attached' => ['library' => ['memcache_storage/debug-table']],
    ];
  }

  /**
   * Prepares a new base for the upcoming transaction log.
   */
  public static function prepare() {
    self::$counter++;
    $key = 'memcache_storage_' . self::$counter;
    Timer::start($key);
  }

  /**
   * @param $operation
   *   Memcached operation. For example, 'get' or 'set'.
   *
   * @param $results
   *   Result of request to memcached.
   *
   * @param $memcached_keys
   *   Array which contains memcached key as an array keys and related
   *   Drupal cache id as an array values.
   *
   * @param $cache_bin
   *   Name of Drupal cache bin.
   *
   * @param $cluster_name
   *   Name of the memcached cluster defined in settings.
   */
  public static function process($operation, $results, $memcached_keys, $cache_bin, $cluster_name) {
    $key = 'memcache_storage_' . self::$counter;
    $used_time = Timer::read($key);

    if (sizeof($memcached_keys) > 1) {
      $operation .= 'Multi';
    }

    foreach ($memcached_keys as $memcached_key => $cache_key) {

      $result = $results;
      if (in_array($operation, ['get', 'getMulti'])) {
        $result = isset($results[$memcached_key]) ? TRUE : FALSE;
      }

      self::$log[] = [
        'operation' => $operation,
        'used_time' => $used_time,
        'result' => $result,
        'cache_bin' => $cache_bin,
        'cache_key' => $cache_key,
        'memcached_key' => $memcached_key,
        'cluster_name' => $cluster_name,
      ];
    }
  }

}
