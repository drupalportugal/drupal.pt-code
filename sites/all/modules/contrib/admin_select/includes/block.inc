<?php
/**
 * @file
 * Block module integration.
 */

/**
 * Implements hook_block_info().
 */
function admin_select_block_info() {
  $blocks = array();
  $blocks[] = array(
    'info' => t('Administration Menu select'),
    'admin' => TRUE,
  );
  return $blocks;
}

/**
 * Implements hook_block_view().
 */
function admin_select_block_view($delta = '') {
  $block = array();

  switch ($delta) {
    case 0:
      $info = admin_select_get_admin_select_info();
      $options = array();
      foreach ($info as $key => $item) {
        if (!empty($item['access'])) {
          $options[$key] = $item['title'];
        }
      }
      asort($options);
      if (!empty($options)) {
        $block = array(
          'subject' => t('Administration Menu select'),
          'content' => drupal_get_form('admin_select_block_form', $options),
        );
      }
      break;
  }

  return $block;
}

/**
 * Administration Menu select block form.
 */
function admin_select_block_form($form, &$form_state, $options) {
  $form['admin_select'] = array(
    '#type' => 'select',
    '#options' => array('' => t('None')) + $options,
    '#default_value' => isset($GLOBALS['user']->data['admin_select']) ? $GLOBALS['user']->data['admin_select'] : '',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Switch'),
  );
  return $form;
}

/**
 * Administration Menu select block form submit callback.
 */
function admin_select_block_form_submit($form, $form_state) {
  user_save($GLOBALS['user'], array('admin_select' => $form_state['values']['admin_select']));
}
