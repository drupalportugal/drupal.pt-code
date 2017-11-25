<?php

namespace Drupal\commerce_license;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\commerce_license\Annotation\CommerceLicenseType;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface;

/**
 * Manages discovery and instantiation of license type plugins.
 *
 * @see \Drupal\commerce_license\Annotation\CommerceLicenseType
 * @see plugin_api
 */
class LicenseTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new LicenseTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Commerce/LicenseType',
      $namespaces,
      $module_handler,
      LicenseTypeInterface::class,
      CommerceLicenseType::class
    );

    $this->alterInfo('commerce_license_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_license_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The license type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
