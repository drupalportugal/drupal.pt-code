<?php
/**
 * @file
 * Contains \Drupal\name\Tests\NameTestTrait.
 */

namespace Drupal\name\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

trait NameTestTrait {

  /**
   * Creates a name field with default settings.
   *
   * @param $field_name
   * @param $entity_type
   * @param $bundle
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   */
  public function createNameField($field_name, $entity_type, $bundle) {
    FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'name',
    ))
    ->save();

    $field_config = FieldConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'name',
      'bundle' => $bundle,
    ));

    $field_config->save();
    return $field_config;
  }

  /**
   * Forms an associative array from a linear array.
   *
   * @param array $values
   *
   * @return array
   */
  public function mapAssoc(array $values) {
    return array_combine($values, $values);
  }

}
