<?php

namespace Drupal\memcache_storage;

use Drupal\Core\Site\Settings;

class DrupalMemcachedInitializeException extends \Exception {};

class DrupalMemcachedFactory {

  /**
   * An object with site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * PECL extension name.
   * Supported 'Memcache' or 'Memcached'.
   *
   * @var string
   */
  protected $extension;

  /**
   * List of cache bin referenced to specific memcached clusters.
   *
   * @var array
   */
  protected $bins_clusters = [];

  /**
   * Contains initialized DrupalMemcache(d) objects for memcached clusters.
   *
   * @var array
   */
  protected $clusters;

  /**
   * Constructs the factory object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   An object with site settings.
   *
   * @throws \Drupal\memcache_storage\DrupalMemcachedInitializeException
   */
  public function __construct(Settings $settings) {

    // Validate pecl extension configuration.
    $this->extension = DrupalMemcachedUtils::getPeclExtension();
    if (!class_exists($this->extension) || !in_array($this->extension, ['Memcache', 'Memcached'])) {
      throw new DrupalMemcachedInitializeException('Could not initialize ' . $this->extension . ' PECL extension');
    }

    // Keep memcache_storage settings.
    $this->settings = $settings->get('memcache_storage', []);

    // Get configuration of cache bins per memcached clusters.
    if (!empty($this->settings['bins_clusters'])) {
      $this->bins_clusters = $this->settings['bins_clusters'];
    }
  }

  /**
   * Returns initialized DrupalMemcache(d) class for the
   * specified cache bin.
   *
   * @param $bin
   *   The cache bin name.
   *
   * @return object
   *   DrupalMemcache(d) object.
   */
  public function get($bin) {

    // Get the name of the memcached cluster for the specified cache bin.
    $cluster_name = !empty($this->bins_clusters[$bin]) ? $this->bins_clusters[$bin] : 'default';

    // If the connection to the cluster is not initialized yet - then do it!
    if (!isset($this->clusters[$cluster_name])) {

      // Initializes a new DrupalMemcache(d) object.
      // TODO: Switch to services.
      $class_name = 'Drupal\memcache_storage\Drupal' . ucfirst(strtolower($this->extension));
      $memcached = new $class_name($this->settings, $cluster_name);

      // Store DrupalMemcache(d) object as a static object, to avoid duplicate
      // connections to the same memcached cluster but for another cache bin.
      $this->clusters[$cluster_name] = $memcached;
    }

    return $this->clusters[$cluster_name];
  }

}
