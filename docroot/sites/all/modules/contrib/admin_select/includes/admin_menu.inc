<?php
/**
 * @file
 * Administration menu module integration.
 */

if (!function_exists('admin_menu_admin_select_info')) {
  /**
   * Implements hook_admin_select_info() on behalf of admin_menu.module.
   */
  function admin_menu_admin_select_info() {
    $info = array();

    $info['admin_menu'] = array(
      'title' => t('Administration menu module'),
      'access arguments' => array('access administration menu'),
      'suppress callback' => 'admin_menu_suppress',
    );

    return $info;
  }
}

/**
 * Implements hook_admin_menu().
 */
function admin_select_admin_menu_output_alter(&$content) {
  $content['icon']['icon']['admin_select'] = array(
    '#title' => 'Administration Menu select',
    '#href' => 'admin_select',
    '#weight' => 50,
  );
  $content['icon']['icon']['admin_select']['none'] = array(
    '#title' => 'None',
    '#href' => 'admin_select/none',
    '#weight' => 0,
  );

  $info = admin_select_get_admin_select_info();
  asort($info);
  foreach ($info as $key => $item) {
    $content['icon']['icon']['admin_select'][$key] = array(
      '#title' => $item['title'],
      '#href' => "admin_select/{$key}",
      '#weight' => 10,
    );
  }
}
