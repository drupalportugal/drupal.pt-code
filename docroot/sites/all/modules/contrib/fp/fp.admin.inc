<?php


/**
 * Menu callback: administer permissions.
 *
 * @ingroup forms
 * @see theme_fp_admin_permissions_overview()
 */
function fp_admin_permissions_overview($form, $form_state){
  $options = array();
  $module_info = system_get_info('module');
  $hide_descriptions = system_admin_compact_mode();

  // Get a list of all the modules implementing a hook_permission() and sort by
  // display name.
  $modules = array();
  foreach (module_implements('permission') as $module) {
    $modules[$module] = $module_info[$module]['name'];
  }
  asort($modules);
  
  foreach ($modules as $module => $display_name) {
    if ($permissions = module_invoke($module, 'permission')) {
      $form['permission'][] = array(
        '#markup' => $module_info[$module]['name'],
        '#id' => $module,
      );
    }
  }

	return $form;
} 

/**
 * Menu callback: administer permissions.
 *
 * @ingroup forms
 * @see fp_admin_permissions_submit()
 * @see theme_fp_admin_permissions()
 */
function fp_admin_permissions($form, &$form_state, $module_name) {

  // Retrieve role names for columns.
  $role_names = user_roles();

  // Fetch permissions for all roles or the one selected role.
  $role_permissions = user_role_permissions($role_names);

  // Store $role_names for use when saving the data.
  $form['role_names'] = array(
    '#type' => 'value',
    '#value' => $role_names,
  );
  // Render role/permission overview:
  $options = array();
  $module_info = system_get_info('module');
  $hide_descriptions = system_admin_compact_mode();

  // Get a list of all the modules implementing a hook_permission() and sort by
  // display name.
  $modules = array();
  foreach (module_implements('permission') as $module) {
    $modules[$module] = $module_info[$module]['name'];
  }
  asort($modules);

  foreach ($modules as $module => $display_name) {
	  if($module === $module_name){ 
	    if ($permissions = module_invoke($module, 'permission')) {
	      $form['permission'][] = array(
	        '#markup' => $module_info[$module]['name'],
	        '#id' => $module,
	      );
	      foreach ($permissions as $perm => $perm_item) {
	        // Fill in default values for the permission.
	        $perm_item += array(
	          'description' => '',
	          'restrict access' => FALSE,
	          'warning' => !empty($perm_item['restrict access']) ? t('Warning: Give to trusted roles only; this permission has security implications.') : '',
	        );
	        $options[$perm] = '';
	        $form['permission'][$perm] = array(
	          '#type' => 'item',
	          '#markup' => $perm_item['title'],
	          '#description' => theme('user_permission_description', array('permission_item' => $perm_item, 'hide' => $hide_descriptions)),
	        );
	        foreach ($role_names as $rid => $name) {
	          // Builds arrays for checked boxes for each role
	          if (isset($role_permissions[$rid][$perm])) {
	            $status[$rid][] = $perm;
	          }
	        }
	      }
	    }
	  }
  }

  // Have to build checkboxes here after checkbox arrays are built
  foreach ($role_names as $rid => $name) {
    $form['checkboxes'][$rid] = array('#type' => 'checkboxes', '#options' => $options, '#default_value' => isset($status[$rid]) ? $status[$rid] : array());
    $form['role_names'][$rid] = array('#markup' => check_plain($name), '#tree' => TRUE);
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save permissions'));

  $form['#attached']['js'][] = drupal_get_path('module', 'user') . '/user.permissions.js';

  return $form;

}

/**
 * Save permissions selected on the administer permissions page.
 *
 * @see fp_admin_permissions()
 */
function fp_admin_permissions_submit($form, &$form_state) {
  foreach ($form_state['values']['role_names'] as $rid => $name) {
    user_role_change_permissions($rid, $form_state['values'][$rid]);
  }

  drupal_set_message(t('The changes have been saved.'));

  // Clear the cached pages and blocks.
  cache_clear_all();
}

/**
 * Returns HTML for the administer permissions page.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_fp_admin_permissions($variables) {
  $form = $variables['form'];

  $roles = user_roles();
  foreach (element_children($form['permission']) as $key) {
    $row = array();
    // Module name
    if (is_numeric($key)) {
      $row[] = array('data' => drupal_render($form['permission'][$key]), 'class' => array('module'), 'id' => 'module-' . $form['permission'][$key]['#id'], 'colspan' => count($form['role_names']['#value']) + 1);
    }
    else {
      // Permission row.
      $row[] = array(
        'data' => drupal_render($form['permission'][$key]),
        'class' => array('permission'),
      );
      foreach (element_children($form['checkboxes']) as $rid) {
        $form['checkboxes'][$rid][$key]['#title'] = $roles[$rid] . ': ' . $form['permission'][$key]['#markup'];
        $form['checkboxes'][$rid][$key]['#title_display'] = 'invisible';
        $row[] = array('data' => drupal_render($form['checkboxes'][$rid][$key]), 'class' => array('checkbox'));
      }
    }
    $rows[] = $row;
  }
  $header[] = (t('Permission'));
  foreach (element_children($form['role_names']) as $rid) {
    $header[] = array('data' => drupal_render($form['role_names'][$rid]), 'class' => array('checkbox'));
  }
  $output = theme('system_compact_link');
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'permissions')));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Returns HTML for the administer permissions overview page.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_fp_admin_permissions_overview($variables) {

  $form = $variables['form'];
		
  foreach (element_children($form['permission']) as $key) {
    $row = array();
    // Module name
    if (is_numeric($key)) {
      $row[] = array('data' => drupal_render($form['permission'][$key]), 'class' => array('module'), 'id' => 'module-' . $form['permission'][$key]['#id']);
      $row[] = array('data' => l('configure','admin/people/fp/' . $form['permission'][$key]['#id'], array('query' => drupal_get_destination())));
    }
    $rows[] = $row;
  }
  $header[] = (t('Permission'));
  $header[] = array('data' => 'Operations', 'class' => array('checkbox'));

  $output = theme('system_compact_link');
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'permissions')));
  $output .= drupal_render_children($form);
  return $output;

}
