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

  foreach ($data as $field_name => $field_data) {
    foreach (array_keys($name_fields) as $name) {
      // Check on field storage type prevents warnings on non-sql field storage.
      if (isset($field_data[$name]) && $name_fields[$name]['storage']['type'] == 'field_sql_storage') {
        $data[$field_name][$name]['filter'] = array(
          'field' => $name,
          'table' => key($name_fields[$name]['storage']['details']['sql'][FIELD_LOAD_CURRENT]),
          'handler' => 'name_handler_filter_name_fulltext',
          'field_name' => $name,
          'allow_empty' => TRUE,
        );

        $components = array_filter($name_fields[$name]['settings']['components']);
        foreach (_name_translations() as $key => $title) {
          if (in_array($key, $components)) {
            $component_key = $name . '_' . $key;
            $data[$field_name][$component_key] = array(
              'title' => $title,
              'help' => t('@component component of the @label field.', array('@component' => $title, '@label' => $data[$field_name][$name]['title'])),
              'field' => array(
                'handler' => 'views_handler_field',
                'type' => 'text',
              ),
              'field' => array(
                'group' => t('Name Components'),
                'handler' => 'views_handler_field',
                'click sortable' => TRUE,
                'link_to_node default' => TRUE,
               ),
              'sort' => array(
                'group' => t('Name Components'),
                'handler' => 'views_handler_sort',
              ),
              'filter' => array(
                'group' => t('Name Components'),
                'handler' => 'views_handler_filter_string',
              ),
              'argument' => array(
                'group' => t('Name Components'),
                'handler' => 'views_handler_argument_string',
              ),
            );
          }
        }
      }
    }
  }
}
