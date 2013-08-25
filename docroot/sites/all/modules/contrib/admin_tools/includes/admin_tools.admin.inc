<?php

/**
 * @file
 * Admin page callbacks for the Admin Tools module.
 */

/**
 * Menu callback for the Admin Tools module to display its administration
 */
function admin_tools_settings($form, $form_state) {
  $form = array();
  $form['row'] = array(
    '#tree' => TRUE,
    '#theme' => 'admin_tools_rows',
  );
  // Retrieve the menu links from the variables table.
  $links = array(
    FALSE => variable_get('admin_tools_custom_links_disabled', array()),
    TRUE  => variable_get('admin_tools_custom_links', array())
  );
  
  $rows = array();
  foreach ($links as $enabled => $custom_links) {
    foreach ($custom_links as $index => $custom_link) {
      $rows[] = array(
        'enabled'    => $enabled,
        'title'      => $custom_link['title'],
        'href'       => $custom_link['href'],
        'weight'     => $custom_link['weight'],
        'permission' => $custom_link['permission'],
        'redirect'   => $custom_link['redirect'],
      );
    }
  }
  
  // See how many rows there should be.
  $row_count = 0;
  if (isset($form_state['row_count'])) {
    $row_count = $form_state['row_count'];
  }
  else {
    $row_count = count($rows) + 1;
  }
  
  // Sort the rows and display them in the form.
  usort($rows, 'drupal_sort_weight');
  for ($index = 0; $index < $row_count; $index++) {
    if (isset($rows[$index])) {
      $row = $rows[$index];
      $form['row'][$index] = admin_tools_settings_row($index, $row['enabled'], $row['title'], $row['href'], $row['weight'], $row['permission'], $row['redirect']);
    }
    else {
      $form['row'][$index] = admin_tools_settings_row($index, -1);
    }
  }
  
  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions', 'container-inline')),
    
    'more_rows' => array(
      '#type'   => 'submit',
      '#value'  => t('Add row'),
      '#weight' => 2,
      '#submit' => array('admin_tools_more_rows_submit'),
      '#ajax'   => array(
        'callback' => 'admin_tools_ajax',
        'wrapper'  => 'admin-tools-wrapper',
        'method'   => 'replace',
        'effect'   => 'none',
      ),
    ),
    'submit' => array(
      '#type'   => 'submit',
      '#value'  => t('Save configuration'),
      '#weight' => 3,
    ),
    'remove' => array(
      '#type'   => 'submit',
      '#value'  => t('Save and remove disabled links'),
      '#weight' => 4,
      '#access' => !empty($rows),
    ),
  );
  
  return $form;
}

/**
 * Triggered when the user submits the administration page.
 */
function admin_tools_settings_validate($form, &$form_state) {
  // Don't validate form if a row is being added
  if ($form_state['values']['op'] != t('Add row')) {
    $rows = $form_state['values']['row'];
    foreach ($rows as $index => $row) {
      // Check whether a title exists without a path
      if (!empty($row['title']) && empty($row['href'])) {
        form_set_error("row][$index][href", t('A URL must be entered for %title.', array('%title' => $row['title'])));
      }
      // Check whether a path exists without a title
      elseif (empty($row['title']) && !empty($row['href'])) {
        form_set_error("row][$index][title", t('A title must be entered for path %path.', array('%path' => $row['href'])));
      }
      // Check whether the path exists in the system
      if (!empty($row['href']) &&!menu_get_item($row['href'])) {
        form_set_error("row][$index][href", t('The path %path is not valid.', array('%path' => $row['href'])));
      }
    }
  }
}

/**
 * Triggered when the user submits the administration page.
 */
function admin_tools_settings_submit($form, &$form_state) {
  if (!in_array($form_state['clicked_button']['#id'], array('edit-submit', 'edit-remove'))) {
    // Submit the form only for save and remove buttons.
    return;
  }
  
  // Format the words correctly so that they're put into the database correctly.
  $links = array(FALSE => array(), TRUE => array());
  foreach ($form_state['values']['row'] as $index => $row) {
    if (!empty($row['title'])) {
      $links[$row['enabled']][] = array(
        'title'      => $row['title'],
        'href'       => $row['href'],
        'weight'     => $row['weight'],
        'permission' => $row['permission'],
        'redirect'   => $row['redirect'],
      );
    }
  }
  
  // Save enabled links
  variable_set('admin_tools_custom_links', $links[1]);
  
  // Save the values and display a message to the user depend.
  switch ($form_state['clicked_button']['#id']) {
    case 'edit-submit':
      // Save disabled links
      variable_set('admin_tools_custom_links_disabled', $links[0]);
      drupal_set_message(t('Your changes have been saved.'));
      break;
    
    case 'edit-remove':
      // Remove disabled links
      variable_del('admin_tools_custom_links_disabled');
      drupal_set_message(t('All disabled menu links have been removed.'));
      break;
  }
}

/**
 * Function to return a menu link row form.
 */
function admin_tools_settings_row($delta = 0, $enabled = TRUE, $title = '', $href = '', $weight = 0, $permission = 0, $redirect = 0) {
  $row = array();
  
  $row['#tree'] = TRUE;
  $row['enabled'] = array(
    '#type'          => 'checkbox',
    '#default_value' => ($enabled == -1) ? TRUE : $enabled,
    // Have access if it's not a placeholder value.
    '#access'        => $enabled != -1,
    '#attributes'    => array(
      'title' => t('Flag whether this menu item should be active.'),
    ),
  );
  
  $row['title'] = array(
    '#type'          => 'textfield',
    '#size'          => 25,
    '#default_value' => $title,
  );
  
  $row['href'] = array(
    '#type'          => 'textfield',
    '#size'          => 50,
    '#default_value' => $href,
  );
  
  $weight_options = array();
  for ($i = -10; $i <= 100; $i++) {
    $weight_options[$i] = $i;
  }
  $row['weight'] = array(
    '#type'          => 'select',
    '#options'       => $weight_options,
    '#default_value' => $weight,
  );
  
  $row['permission'] = array(
    '#type'          => 'checkbox',
    '#default_value' => $permission,
    '#attributes'    => array(
      'title' => t('Should this link have a custom permission created?'),
    ),
  );
  
  $row['redirect'] = array(
    '#type'          => 'checkbox',
    '#size'          => 20,
    '#default_value' => $redirect,
    '#attributes'    => array(
      'title' => t('Should the user be redirected back to the referring page?'),
    ),
  );
  
  return $row;
}

/**
 * Theme the enabled menu link rows.
 */
function theme_admin_tools_rows($variables) {
  $form = $variables['form'];
  $header = array(
    ($form[0]['enabled']['#access']) ? array('data' => t('Enabled'), 'title' => t('Flag whether the given menu item should be active.')) : NULL,
    array('data' => t('Title')),
    array('data' => t('URL')),
    array('data' => t('Weight')),
    array('data' => t('Permission')),
    array('data' => t('Redirect')),
  );
  
  $rows = array();
  foreach (element_children($form) as $key) {
    // Build the table row.
    $rows[$key] = array(
      'data' => array(
        array('data' => drupal_render($form[$key]['enabled']), 'class' => 'admin-tools-enabled'),
        array('data' => drupal_render($form[$key]['title']), 'class' => 'admin-tools-title'),
        array('data' => drupal_render($form[$key]['href']), 'class' => 'admin-tools-href'),
        array('data' => drupal_render($form[$key]['weight']), 'class' => 'admin-tools-weight'),
        array('data' => drupal_render($form[$key]['permission']), 'class' => 'admin-tools-permission'),
        array('data' => drupal_render($form[$key]['redirect']), 'class' => 'admin-tools-redirect'),
      ),
    );
    // Add any attributes on the element to the row.
    if (array_key_exists('#attributes', $form[$key])) {
      $rows[$key] = array_merge($rows[$key], $form[$key]['#attributes']);
    }
  }
  
  $output = theme('table', array(
    'header'     => $header,
    'rows'       => $rows,
    'attributes' => array('id' => 'admin-tools-wrapper'),
  ));
  $output .= drupal_render_children($form);
  
  return $output;
}

/**
 * Menu callback; Display a new menu link row.
 */
function admin_tools_ajax($form, &$form_state) {
  return $form['row'];
}

/**
 * Submit handler; The "Add row" button.
 */
function admin_tools_more_rows_submit($form, &$form_state) {
  $form_state['row_count'] = count($form_state['values']['row']) + 1;
  $form_state['rebuild'] = TRUE;
}
