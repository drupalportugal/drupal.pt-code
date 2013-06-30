<?php
/**
 * @file
 * System module integration.
 */

/**
 * Implements hook_menu().
 */
function admin_select_menu() {
  $items = array();

  $items['admin/config/administration/admin_select'] = array(
    'title' => 'Administration Menu select',
    'access arguments' => array('administer administration menu select'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('admin_select_defaults'),
    'type' => MENU_NORMAL_ITEM,
  );

  $items['admin_select'] = array(
    'title' => 'Adminstration Menu select',
    'access callback' => TRUE,
    'page callback' => '_admin_select_menu_callback',
    'page arguments' => array(),
    'type' => MENU_CALLBACK,
  );
  $items['admin_select/none'] = array(
    'title' => 'None',
    'access callback' => TRUE,
    'page callback' => '_admin_select_menu_callback',
    'page arguments' => array('none'),
    'type' => MENU_CALLBACK,
  );

  $info = admin_select_get_admin_select_info();
  asort($info);
  foreach ($info as $key => $item) {
    $items["admin_select/{$key}"] = array(
      'title' => $item['title'],
      'access callback' => $item['access callback'],
      'access arguments' => $item['access arguments'],
      'page callback' => '_admin_select_menu_callback',
      'page arguments' => array($key),
      'type' => MENU_CALLBACK,
    );
  }

  return $items;
}

/**
 * Implements hook_translated_menu_link_alter().
 */
function admin_select_translated_menu_link_alter(&$item) {
  if (strpos($item['href'], 'admin_select') === 0) {
    $item['localized_options']['query'] = drupal_get_destination();
  }
}

/**
 * Administration Select menu callback.
 *
 * @param $admin
 *   Id of Administration menu to set for the current user.
 */
function _admin_select_menu_callback($admin = NULL) {
  if ($admin) {
    user_save($GLOBALS['user'], array('admin_select' => $admin));
  }
  drupal_goto();
}
