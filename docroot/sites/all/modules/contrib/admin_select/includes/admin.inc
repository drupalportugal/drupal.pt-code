<?php
/**
 * @file
 * Admin module integration.
 */

if (!function_exists('admin_admin_select_info')) {
  /**
   * Implements hook_admin_select_info() on behalf of admin.module.
   */
  function admin_admin_select_info() {
    $info = array();

    $info['admin'] = array(
      'title' => t('Admin module'),
      'access arguments' => array('use admin toolbar'),
      'suppress callback' => 'admin_suppress',
    );

    return $info;
  }
}
