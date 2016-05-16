<?php

/**
 * @file
 * Contains Drupal\Core\Extension\ModuleHandler.
 */

namespace Drupal\disable_modules\Extension;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleInstaller;

/**
 * Class that overrides the default ModuleInstaller.
 */
class ModuleInstallerDisableModules extends ModuleInstaller {

  /**
   * {@inheritdoc}
   */
  public function install(array $module_list, $enable_dependencies = TRUE) {

    $route_match = \Drupal::routeMatch();
    if ($route_match->getRouteName() != 'disable_modules.modules_disable' && empty($GLOBALS['drush_disable_modules'])) {
      return parent::install($module_list, $enable_dependencies);
    }

    $extension_config = \Drupal::configFactory()->getEditable('core.extension');
    if ($enable_dependencies) {
      // Get all module data so we can find dependencies and sort.
      $module_data = system_rebuild_module_data();
      $module_list = $module_list ? array_combine($module_list, $module_list) : array();
      if ($missing_modules = array_diff_key($module_list, $module_data)) {
        // One or more of the given modules doesn't exist.
        throw new MissingDependencyException(sprintf('Unable to enable modules %s due to missing modules %s.', implode(', ', $module_list), implode(', ', $missing_modules)));
      }

      // Only process currently disabled modules.
      $installed_modules = $extension_config->get('module') ?: array();
      if (!$module_list = array_diff_key($module_list, $installed_modules)) {
        // Nothing to do. All modules already enabled.
        return TRUE;
      }

      // Add dependencies to the list. The new modules will be processed as
      // the while loop continues.
      while (list($module) = each($module_list)) {
        foreach (array_keys($module_data[$module]->requires) as $dependency) {
          if (!isset($module_data[$dependency])) {
            // The dependency does not exist.
            throw new MissingDependencyException("Unable to install modules: module '$module' is missing its dependency module $dependency.");
          }

          // Skip already installed modules.
          if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
            $module_list[$dependency] = $dependency;
          }
        }
      }

      // Set the actual module weights.
      $module_list = array_map(function ($module) use ($module_data) {
        return $module_data[$module]->sort;
      }, $module_list);

      // Sort the module list by their weights (reverse).
      arsort($module_list);
      $module_list = array_keys($module_list);
    }

    // Required for module installation checks.
    include_once $this->root . '/core/includes/install.inc';

    $modules_enabled = array();
    foreach ($module_list as $module) {
      $enabled = $extension_config->get("module.$module") !== NULL;
      if (!$enabled) {
        // Throw an exception if the module name is too long.
        if (strlen($module) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
          throw new ExtensionNameLengthException("Module name '$module' is over the maximum allowed length of " . DRUPAL_EXTENSION_NAME_MAX_LENGTH . ' characters');
        }

        // Save this data without checking schema. This is a performance
        // improvement for module installation.
        $extension_config
          ->set("module.$module", 0)
          ->set('module', module_config_sort($extension_config->get('module')))
          ->save(TRUE);

        // Prepare the new module list, sorted by weight, including filenames.
        // This list is used for both the ModuleHandler and DrupalKernel. It
        // needs to be kept in sync between both. A DrupalKernel reboot or
        // rebuild will automatically re-instantiate a new ModuleHandler that
        // uses the new module list of the kernel. However, DrupalKernel does
        // not cause any modules to be loaded.
        // Furthermore, the currently active (fixed) module list can be
        // different from the configured list of enabled modules. For all active
        // modules not contained in the configured enabled modules, we assume a
        // weight of 0.
        $current_module_filenames = $this->moduleHandler->getModuleList();
        $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
        $current_modules = module_config_sort(array_merge($current_modules, $extension_config->get('module')));
        $module_filenames = array();
        foreach ($current_modules as $name => $weight) {
          if (isset($current_module_filenames[$name])) {
            $module_filenames[$name] = $current_module_filenames[$name];
          }
          else {
            $module_path = drupal_get_path('module', $name);
            $pathname = "$module_path/$name.info.yml";
            $filename = file_exists($module_path . "/$name.module") ? "$name.module" : NULL;
            $module_filenames[$name] = new Extension($this->root, 'module', $pathname, $filename);
          }
        }

        // Update the module handler in order to load the module's code.
        // This allows the module to participate in hooks and its existence to
        // be discovered by other modules.
        // The current ModuleHandler instance is obsolete with the kernel
        // rebuild below.
        $this->moduleHandler->setModuleList($module_filenames);
        $this->moduleHandler->load($module);

        // Clear the static cache of system_rebuild_module_data() to pick up the
        // new module, since it merges the installation status of modules into
        // its statically cached list.
        drupal_static_reset('system_rebuild_module_data');

        // Update the kernel to include it.
        $this->updateKernel($module_filenames);

        // Clear plugin manager caches.
        \Drupal::getContainer()->get('plugin.cache_clearer')->clearCachedDefinitions();

        // Set the schema version to the number of the last update provided by
        // the module, or the minimum core schema version.
        $version = \Drupal::CORE_MINIMUM_SCHEMA_VERSION;
        $versions = drupal_get_schema_versions($module);
        if ($versions) {
          $version = max(max($versions), $version);
        }

        // If the module has no current updates, but has some that were
        // previously removed, set the version to the value of
        // hook_update_last_removed().
        if ($last_removed = $this->moduleHandler->invoke($module, 'update_last_removed')) {
          $version = max($version, $last_removed);
        }
        drupal_set_installed_schema_version($module, $version);

        // Record the fact that it was installed.
        $modules_enabled[] = $module;

        // Drupal's stream wrappers needs to be re-registered in case a
        // module-provided stream wrapper is used later in the same request. In
        // particular, this happens when installing Drupal via Drush, as the
        // 'translations' stream wrapper is provided by Interface Translation
        // module and is later used to import translations.
        \Drupal::service('stream_wrapper_manager')->register();

        // Update the theme registry to include it.
        drupal_theme_rebuild();

        // Modules can alter theme info, so refresh theme data.
        // @todo ThemeHandler cannot be injected into ModuleHandler, since that
        //   causes a circular service dependency.
        // @see https://www.drupal.org/node/2208429
        \Drupal::service('theme_handler')->refreshInfo();

        // Record the fact that it was installed.
        \Drupal::logger('system')->info('%module module enabled.', array('%module' => $module));
      }
    }

    // If any modules were newly enabled, rebuild router.
    if (!empty($modules_enabled)) {
      \Drupal::service('router.builder')->setRebuildNeeded();
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $module_list, $uninstall_dependents = TRUE) {

    $route_match = \Drupal::routeMatch();
    if ($route_match->getRouteName() != 'disable_modules.modules_disable' && empty($GLOBALS['drush_disable_modules'])) {
      return parent::uninstall($module_list, $uninstall_dependents);
    }

    // Get all module data so we can find dependencies and sort.
    $module_data = system_rebuild_module_data();
    $module_list = $module_list ? array_combine($module_list, $module_list) : array();
    if (array_diff_key($module_list, $module_data)) {
      // One or more of the given modules doesn't exist.
      return FALSE;
    }

    $extension_config = \Drupal::configFactory()->getEditable('core.extension');
    $installed_modules = $extension_config->get('module') ?: array();
    if (!$module_list = array_intersect_key($module_list, $installed_modules)) {
      // Nothing to do. All modules already disabled.
      return TRUE;
    }

    if ($uninstall_dependents) {
      // Add dependent modules to the list. The new modules will be processed as
      // the while loop continues.
      $profile = drupal_get_profile();
      while (list($module) = each($module_list)) {
        foreach (array_keys($module_data[$module]->required_by) as $dependent) {
          if (!isset($module_data[$dependent])) {
            // The dependent module does not exist.
            return FALSE;
          }

          // Skip already uninstalled modules.
          if (isset($installed_modules[$dependent]) && !isset($module_list[$dependent]) && $dependent != $profile) {
            $module_list[$dependent] = $dependent;
          }
        }
      }
    }

    // Set the actual module weights.
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Sort the module list by their weights.
    asort($module_list);
    $module_list = array_keys($module_list);

    // Only process modules that are enabled. A module is only enabled if it is
    // configured as enabled. Custom or overridden module handlers might contain
    // the module already, which means that it might be loaded, but not
    // necessarily installed.
    foreach ($module_list as $module) {

      // Remove the module's entry from the config. Don't check schema when
      // uninstalling a module since we are only clearing a key.
      \Drupal::configFactory()->getEditable('core.extension')->clear("module.$module")->save(TRUE);

      // Update the module handler to remove the module.
      // The current ModuleHandler instance is obsolete with the kernel rebuild
      // below.
      $module_filenames = $this->moduleHandler->getModuleList();
      unset($module_filenames[$module]);
      $this->moduleHandler->setModuleList($module_filenames);

      // Clear the static cache of system_rebuild_module_data() to pick up the
      // new module, since it merges the installation status of modules into
      // its statically cached list.
      drupal_static_reset('system_rebuild_module_data');

      // Clear plugin manager caches.
      \Drupal::getContainer()->get('plugin.cache_clearer')->clearCachedDefinitions();

      // Update the kernel to exclude the uninstalled modules.
      $this->updateKernel($module_filenames);

      // Update the theme registry to remove the newly uninstalled module.
      drupal_theme_rebuild();

      // Modules can alter theme info, so refresh theme data.
      // @todo ThemeHandler cannot be injected into ModuleHandler, since that
      //   causes a circular service dependency.
      // @see https://www.drupal.org/node/2208429
      \Drupal::service('theme_handler')->refreshInfo();

      \Drupal::logger('system')->info('%module module disabled.', array('%module' => $module));
    }
    \Drupal::service('router.builder')->setRebuildNeeded();
    drupal_get_installed_schema_version(NULL, TRUE);

    // Flush all persistent caches.
    // Any cache entry might implicitly depend on the uninstalled modules,
    // so clear all of them explicitly.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }

    return TRUE;
  }

}
