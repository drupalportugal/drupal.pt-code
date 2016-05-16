<?php

/**
 * @file
 * Contains \Drupal\disable_modules\DisableModulesServiceProvider.
 */

namespace Drupal\disable_modules;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the module_installer service.
 */
class DisableModulesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('module_installer');
    $definition->setClass('Drupal\disable_modules\Extension\ModuleInstallerDisableModules');
  }

}
