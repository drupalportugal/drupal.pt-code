<?php
/**
 * @file
 * Toolbar module integration.
 */

/**
 * Implements hook_admin_select_info() on behalf of toolbar.module.
 */
function toolbar_admin_select_info() {
  $info = array();

  $info['toolbar'] = array(
    'title' => t('Toolbar module'),
    'access arguments' => array('access toolbar'),
    'suppress callback' => 'admin_select_toolbar_suppress',
  );

  return $info;
}

/**
 * Implements hook_page_alter() on behalf of toolbar.module.
 *
 * We have to do this as there is no toolbar_suppress() function in core so we
 * must manually disable the toolbar in the page render array if desired.
 *
 * @see admin_select_toolbar_suppress()
 */
function admin_select_page_alter(&$page) {
  if (admin_select_toolbar_suppress(FALSE) && !empty($page['page_top']['toolbar'])) {
    $page['page_top']['toolbar']['#access'] = FALSE;
  }
}

/**
 * Adds a suppress callback on behalf of toolbar.module.
 *
 * @see admin_select_page_alter()
 */
function admin_select_toolbar_suppress($set = TRUE) {
  static $suppress = FALSE;
  if (!empty($set)) {
    $suppress = TRUE;
  }
  return $suppress;
}
