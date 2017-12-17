<?php

namespace Drupal\commerce_license\Plugin\Commerce\SubscriptionType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_order\Entity;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeBase;

/**
 * Provides a Commerce Recurring subscription type for use with licenses.
 *
 * @CommerceSubscriptionType(
 *   id = "license",
 *   label = @Translation("License"),
 *   purchasable_entity_type = "commerce_product_variation",
 * )
 */
class LicenseSubscription extends SubscriptionTypeBase {

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionCreate(SubscriptionInterface $subscription, OrderItemInterface $order_item) {
    $purchased_entity = $subscription->getPurchasedEntity();
    $uid = $subscription->getCustomerId();

    // Ensure that the order item being used has the license trait, otherwise
    // the license won't get handled properly.
    if (!$order_item->hasField('license')) {
      throw new \Exception(sprintf("Order item type %s used for product variation %s is missing the license field.",
        $order_item->bundle(),
        $purchased_entity->id()
      ));
    }

    // The order item should already have a license set, as our
    // \Drupal\commerce_license\EventSubscriber\LicenseOrderSyncSubscriber's
    // commerce_order.place.pre_transition listener
    // should run before Commerce Recurring's
    // \Drupal\commerce_recurring\EventSubscriber\EventSubscriber listener,
    // which is what then creates the subscription.
    if (empty($order_item->license->entity)) {
      // Something's gone wrong: either other code has changed priorities, or
      // the modules' relative priorities have become out of sync due to changes
      // in code.
      throw new \Exception(sprintf("Attempt to create a license subscription with order item ID %s that doesn't have a license.",
        $order_item->id()
      ));
    }

    // Get the license the order item refers to.
    $license = $order_item->license->entity;

    // Ensure that the license expiry is unlimited.
    if ($license->expiration_type->target_plugin_id != 'unlimited') {
      throw new \Exception(sprintf("Invalid expiry type %s on product variation %s",
        $license->expiration_type->target_plugin_id,
        $purchased_entity->id()
      ));
    }

    // Set the license on the subscription, but don't save the subscription, as
    // it's currently only being created by the storage handler.
    $subscription->license = $license;
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionActivate(SubscriptionInterface $subscription, OrderInterface $order) {
    // We don't need to do anything here, as LicenseOrderSyncSubscriber takes
    // care of activating the license in an initial order with a subscription.
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionRenew(SubscriptionInterface $subscription, OrderInterface $order, OrderInterface $next_order) {
    $license = $subscription->license->entity;

    // Change the license's renewed time and save it.
    // Use the subscription's renewed time rather than the current time to
    // ensure the timestamps are in sync.
    $license->setRenewedTime($subscription->getRenewedTime());
    $license->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionExpire(SubscriptionInterface $subscription) {
    $license = $subscription->license->entity;

    // Change the license's state to expired.
    // The License entity will handle deactivating the license type plugin.
    $transition = $license->getState()->getWorkflow()->getTransition('expire');
    $license->getState()->applyTransition($transition);
    $license->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionCancel(SubscriptionInterface $subscription) {
    $license = $subscription->license->entity;

    // Change the license's state to canceled.
    $transition = $license->getState()->getWorkflow()->getTransition('cancel');
    $license->getState()->applyTransition($transition);
    $license->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['license'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('License'))
      ->setDescription(t('The license this subscription controls.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_license');

    return $fields;
  }

}
