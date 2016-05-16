<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallForm.
 */

namespace Drupal\disable_modules\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for disabling modules.
 */
class ModulesDisableForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules_disable';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Set theme function.
    $form['#theme'] = 'disable_modules';

    // Get a list of installed modules.
    $disabled_modules = disable_modules_get_disabled_modules();
    $modules = system_rebuild_module_data();
    $disable_list = disable_modules_get_list($modules);

    // Include system.admin.inc so we can use the sort callbacks.
    \Drupal::moduleHandler()->loadInclude('system', 'inc', 'system.admin');

    $form['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $form['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter module name'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '#system-modules-disable',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the module name or description to filter by.'),
      ),
    );

    $form['modules'] = array();

    // Only build the rest of the form if there are any modules available to
    // disable;
    if (empty($disable_list)) {
      return $form;
    }

    $profile = drupal_get_profile();

    // Sort all modules by their name.
    uasort($disable_list, 'system_sort_modules_by_info_name');

    // Note we use 'uninstall' as the key because we're using
    // the same theming function as the uninstall page.
    $form['uninstall'] = array('#tree' => TRUE);
    foreach ($disable_list as $module) {
      $name = $module->info['name'] ?: $module->getName();
      $form['modules'][$module->getName()]['#module_name'] = $name;
      $form['modules'][$module->getName()]['name']['#markup'] = $name;
      $form['modules'][$module->getName()]['description']['#markup'] = $this->t($module->info['description']);

      $form['uninstall'][$module->getName()] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Disable @module module', array('@module' => $name)),
        '#title_display' => 'invisible',
        '#default_value' => in_array($module->getName(), $disabled_modules)
      );

      // All modules which depend on this one must be uninstalled first, before
      // we can allow this module to be uninstalled. (The installation profile
      // is excluded from this list.)
      foreach (array_keys($module->required_by) as $dependent) {
        if ($dependent != $profile && drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED) {
          $name = isset($modules[$dependent]->info['name']) ? $modules[$dependent]->info['name'] : $dependent;
          $form['modules'][$module->getName()]['#required_by'][] = $name;
          $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
        }
      }
    }

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $modules = $form_state->getValue('uninstall');
    disable_modules_do($modules);
    drupal_set_message($this->t('The selected modules have been enabled/disabled.'));
  }
}
