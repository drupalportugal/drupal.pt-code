<?php

namespace Drupal\commerce_license\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\commerce\BundleFieldDefinition;

/**
 * Provides an entity trait for Commerce Order Item entities.
 *
 * Product variations that sell a license must use an order item that uses this
 * trait in order for the license to be created and granted when the order goes
 * through the checkout process.
 *
 * You may use an order item without this trait, provided you either ensure
 * synchronization of the license entity another way, or do not require it, for
 * example in a recurring order.
 *
 * @see \Drupal\commerce_license\EventSubscriber\LicenseOrderSyncSubscriber
 *
 * @CommerceEntityTrait(
 *  id = "commerce_license_order_item_type",
 *  label = @Translation("Provides an order item type for use with licenses"),
 *  entity_types = {"commerce_order_item"}
 * )
 */
class OrderItemLicensed extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Builds the field definitions.
    $fields = [];
    $fields['license'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('License'))
      ->setDescription(t('The license purchased with this order item.'))
      ->setSetting('target_type', 'commerce_license')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      // Won't be set when the order item is initially created, so can't be
      // required.
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);
    return $fields;
  }

}
