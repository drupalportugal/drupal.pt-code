<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Defines the storage handler class for License entities.
 *
 * This extends the base storage class, adding required special handling for
 * License entities.
 *
 * @ingroup commerce_license
 */
interface LicenseStorageInterface extends ContentEntityStorageInterface {

  /**
   * Creates a new license from an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item. Values for the license will be taken from the order
   *   item's customer and the purchased entity's license_type and
   *   license_expiration fields.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   A new, unsaved license entity, whose state is 'new'.
   */
  public function createFromOrderItem(OrderItemInterface $order_item);

  /**
   * Creates a new license from a product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation. Values for the license will be taken from the
   *   license_type and license_expiration fields.
   * @param int $uid
   *   The uid for whom the license will be created.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   A new, unsaved license entity, whose state is 'new'.
   */
  public function createFromProductVariation(ProductVariationInterface $variation, $uid);

}
