<?php

/**
 * @file
 * Views integration for name fields.
 */

/**
 * Implements hook_field_views_data().
 *
 * Adds new fulltext name filter handler for every name field.
 */
function name_field_views_data(\Drupal\field\FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $field_name = $field_storage->getName();

  foreach ($data as $table_name => $table_data) {
    $data[$table_name][$field_name]['filter'] = array(
     'field' => $field_name,
     'table' => $table_name,
     'field_name' => $field_name,
     'id' => 'name_fulltext',
     'allow_empty' => TRUE,
    );
  }

  return $data;
}
