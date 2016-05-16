<?php

/**
 * @file
 * Contains \Drupal\memcache_storage\Tests\MemcacheStorageBackendUnitTest.
 */

namespace Drupal\memcache_storage\Tests;

use Drupal\memcache_storage\MemcachedBackendFactory;
use Drupal\system\Tests\Cache\GenericCacheBackendUnitTestBase;

/**
 * Tests the MemcacheBackend.
 *
 * @group memcache
 */
class MemcacheStorageBackendUnitTest extends GenericCacheBackendUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'memcache_storage'];

  /**
   * Creates a new instance of MemcachedBackend.
   *
   * @return \Drupal\memcache_storage\MemcachedBackend
   *   A new MemcachedBackend object.
   */
  protected function createCacheBackend($bin) {
    $settings = $this->container->get('settings');
    $factory = $this->container->get('memcache_storage.factory');
    $checksum_provider = $this->container->get('cache_tags.invalidator.checksum');
    $factory = new MemcachedBackendFactory($factory, $settings, $checksum_provider);
    return $factory->get($bin);
  }

}
