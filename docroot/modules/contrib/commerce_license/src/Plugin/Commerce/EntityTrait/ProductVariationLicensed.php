<?php

namespace Drupal\commerce_license\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\commerce\BundleFieldDefinition;

/**
 * Provides an entity trait for Commerce Product Variation entities.
 *
 * Product variations that sell a license must use this trait. This adds fields
 * to the product variation type for storing the configuration of the licenses
 * that will be created when the product is purchased.
 *
 * @CommerceEntityTrait(
 *  id = "commerce_license",
 *  label = @Translation("Provides a license."),
 *  entity_types = {"commerce_product_variation"}
 * )
 */
class ProductVariationLicensed extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Builds the field definitions.
    $fields = [];
    $fields['license_type'] = BundleFieldDefinition::create('commerce_plugin_item:commerce_license_type')
      ->setLabel(t('License Type'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 20,
      ]);
    $fields['license_expiration'] = BundleFieldDefinition::create('commerce_plugin_item:recurring_period')
      ->setLabel(t('License Expiration'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 21,
      ]);
    return $fields;
  }

}
