<?php

/**
 * @file
 * Contains \Drupal\interval\IntervalPluginManager.
 */

namespace Drupal\interval;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Configurable interval manager.
 */
class IntervalPluginManager extends DefaultPluginManager implements IntervalPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'plural' => '',
    'singular' => '',
    'php' => 'hours',
    'multiplier' => 1,
    'class' => 'Drupal\interval\IntervalBase',
    'id' => '',
  );

  /**
   * Constructs a new IntervalPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->discovery = new YamlDiscovery('intervals', $module_handler->getModuleDirectories());
    $this->discovery->addTranslatableProperty('plural', 'plural_context');
    $this->discovery->addTranslatableProperty('singular', 'singular_context');
    $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('intervals');
    $this->setCacheBackend($cache_backend, 'interval_plugins');
  }

}
