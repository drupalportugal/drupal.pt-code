<?php

/**
 * @file
 * Disable modules drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function disable_modules_drush_command() {
  $items = array();

  $items['disable-modules'] = array(
    'description' => 'Disable modules temporarily',
    'arguments' => array(
      'modules'    => 'Space separated list of modules',
    ),
    'aliases' => array(
      'd8-dis'
    ),
  );

  $items['enable-modules'] = array(
    'description' => 'Enable modules',
    'arguments' => array(
      'modules'    => 'Space separated list of modules',
    ),
    'aliases' => array(
      'd8-en'
    ),
  );

  return $items;
}

/**
 * Drush callback: disable modules.
 */
function drush_disable_modules() {
  $list = func_get_args();

  if (empty($list)) {
    drush_log(dt('No modules found to disable'), 'warning');
    return;
  }

  if (in_array('disable_modules', $list)) {
    drush_log(dt('You can not disable the "disable_modules" module :)'), 'warning');
    return;
  }

  if (!drush_confirm(dt('Are you sure you want to disable following modules: @modules', array('@modules' => implode(', ', $list))))) {
    drush_log(dt('Command aborted'), 'warning');
    return;
  }

  $modules_array = array();
  $disabled_modules = disable_modules_get_disabled_modules();
  $modules = system_rebuild_module_data();
  $disable_list = disable_modules_get_list($modules);
  foreach (array_keys($disable_list) as $key) {
    $modules_array[$key] = in_array($key, $list) || in_array($key, $disabled_modules) ? TRUE : FALSE;
  }

  disable_modules_do($modules_array);

  drush_log(dt('Disabled @modules', array('@modules' => implode(', ', $list))), 'warning');
}

/**
 * Drush callback: enable modules.
 */
function drush_disable_modules_enable_modules() {
  $list = func_get_args();

  if (empty($list)) {
    drush_log('No modules found to enable.', 'warning');
    return;
  }

  if (in_array('disable_modules', $list)) {
    drush_log(dt('You can not enable the "disable_modules" module :)'), 'warning');
    return;
  }

  if (!drush_confirm(dt('Are you sure you want to enable following modules: @modules', array('@modules' => implode(', ', $list))))) {
    drush_log(dt('Command aborted'), 'warning');
    return;
  }

  $modules_array = array();
  $disabled_modules = disable_modules_get_disabled_modules();
  $modules = system_rebuild_module_data();
  $disable_list = disable_modules_get_list($modules);
  foreach (array_keys($disable_list) as $key) {
    $modules_array[$key] = in_array($key, $disabled_modules) && !in_array($key, $list) ? TRUE : FALSE;
  }

  disable_modules_do($modules_array);

  drush_log(dt('Enabled @modules', array('@modules' => implode(', ', $list))), 'warning');
}
