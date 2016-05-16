<?php

namespace Drupal\memcache_storage;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Site\Settings;

class MemcachedBackend implements CacheBackendInterface {

  /**
   * Cache bin name.
   *
   * @var string
   */
  protected $bin;

  /**
   * An object that handles memcached requests.
   * Point your attention that it is not \Memcache or \Memcached object.
   *
   * @var \Drupal\memcache_storage\DrupalMemcachedInterface
   */
  protected $memcached;

  /**
   * Drupal settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * An object that handles invalidation by tags.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Contructs MemcachedBackend object.
   *
   * @param $bin
   *   Cache bin name.
   *
   * @param \Drupal\memcache_storage\DrupalMemcachedInterface $memcached
   *   An object that handles memcached requests.
   *   Point your attention that it is not \Memcache or \Memcached object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal settings object.
   *
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   An object that handles invalidation by tags.
   */
  public function __construct($bin, DrupalMemcachedInterface $memcached, Settings $settings, CacheTagsChecksumInterface $checksum_provider) {
    $this->bin = $bin;
    $this->memcached = $memcached;
    $this->settings = $settings;
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {

    // Handover get operation to the DrupalMemcache(d) object.
    $cache = $this->memcached->getMulti($cids, $this->bin);

    // Make sure that every item and its cache tags were not expired.
    foreach ($cache as $cid => $item) {
      if (!$this->isValid($item) && !$allow_invalid) {
        unset($cache[$cid]);
      }
    }

    // Remove items from the referenced $cids array that we are returning,
    // per comment in Drupal\Core\Cache\CacheBackendInterface::getMultiple().
    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Validates a cached item.
   *
   * Checks that items are either permanent or did not expire.
   */
  protected function isValid($cache) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    // Make sure that cache tags were not expired.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    return $cache->valid;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    $this->setMultiple([
      $cid => ['data' => $data, 'expire' => $expire, 'tags' => $tags]
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {

    $values = array();
    foreach ($items as $cid => $item) {

      // All items should have expiration data and initialized tags value.
      $item += array(
        'expire' => CacheBackendInterface::CACHE_PERMANENT,
        'tags' => array(),
      );

      assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($item[\'tags\'])', 'Cache Tags must be strings.');

      // Organize tags.
      $item['tags'] = array_unique($item['tags']);
      sort($item['tags']);

      // Create a new object which will be passed to the memcached server for
      // storage.
      $value = new \stdClass();
      $value->cid = $cid;
      $value->expire = $item['expire'];
      $value->created = round(microtime(TRUE), 3);
      $value->tags = $item['tags'];
      $value->checksum = $this->checksumProvider->getCurrentChecksum($item['tags']);
      $value->data = $item['data'];

      $values[] = $value;
    }

    if (empty($values)) {
      return TRUE;
    }

    // Handover set operation to DrupalMemcache(d) object.
    return $this->memcached->setMulti($values, $this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->deleteMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $this->memcached->deleteMulti($cids, $this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->memcached->flush($this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    $this->deleteMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $this->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // Nothing to do here, because memcached wipes expired items itself.
  }

  /**
   * We could just leave this method empty, because memcached
   * doesn't support real deletion of cache bin. But instead
   * we increase the bin internal index to catch the case when
   * the bin was removed and then added again.
   */
  public function removeBin() {
    $this->memcached->flush($this->bin);
  }

}
