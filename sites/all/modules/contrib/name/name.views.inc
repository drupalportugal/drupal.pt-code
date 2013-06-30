<?php

/**
 * Implements hook_views_data_alter().
 *
 * Adds new fulltext name filter handler for every name field.
 */
function name_views_data_alter(&$data) {
  $name_fields = array();
  foreach (field_info_fields() as $field_name => $field) {
    if ($field['module'] == 'name') {
      $name_fields[$field_name] = $field;
    }
  }
  foreach ($data as $field_name => $field) {
    foreach (array_keys($name_fields) as $name) {
      // Check on field storage type prevents warnings on non-sql field storage.
      if (isset($field[$name]) && $name_fields[$name]['storage']['type'] == 'field_sql_storage') {
        $data[$field_name][$name]['filter'] = array(
          'field' => $name,
          'table' => key($name_fields[$name]['storage']['details']['sql'][FIELD_LOAD_CURRENT]),
          'handler' => 'name_handler_filter_name_fulltext',
          'field_name' => $name,
          'allow_empty' => TRUE,
        );
      }
    }
  }
}
