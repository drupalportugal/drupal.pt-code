<?php

/**
 * Implements hook_field_validate().
 *
 * @TODO
 * Look at reintroducing this at some stage. Currently, this is problematic as
 * Drupal appears to loss track of what fields are shown on the form and this
 * results in validation errors.
 */
//function name_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
//  // Hack to test the default settings form.
//  if (empty($entity) && empty($entity_type)) {
//    return;
//  }
//
//  if ($instance['required']) {
//    if (is_array($items)) {
//      foreach ($items as $delta => $item) {
//        if (!name_field_is_empty($item, $field)) {
//          return;
//        }
//      }
//    }
//    $errors[$field['field_name']][$langcode][0][] = array(
//      'error' => 'required_components',
//      'message' => t('%name is required.', array('%name' => t($instance['label']))),
//    );
//  }
//}
