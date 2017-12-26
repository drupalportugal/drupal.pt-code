<?php

namespace Drupal\commerce_license\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes a license's state in sync with an order's workflow.
 */
class LicenseOrderSyncSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The license storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $licenseStorage;

  /**
   * Constructs a new LicenseOrderSyncSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->licenseStorage = $entity_type_manager->getStorage('commerce_license');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      // Events defined by state_machine, derived from the workflow defined in
      // commerce_order.workflows.yml.
      // See Drupal\state_machine\Plugin\Field\FieldType\StateItem::dispatchTransitionEvent()

      // TODO: Revisit these transitions and check they are correct.

      // Subscribe to events for reaching the states we support for activation.
      // We need our commerce_order.place.pre_transition method to run before
      // Commerce Recurring's, so that an initial order that purchases a
      // license subscription runs our method before
      // \Drupal\commerce_recurring\EventSubscriber's.
      // This is to ensure that the license is created here before it's
      // set on the subscription entity in
      // \Drupal\commerce_license\Plugin\Commerce\SubscriptionType::onSubscriptionCreate().
      'commerce_order.place.pre_transition' => ['onCartOrderTransition', 100],
      'commerce_order.validate.pre_transition' => ['onCartOrderTransition', -100],
      'commerce_order.fulfill.pre_transition' => ['onCartOrderTransition', -100],
      // Event for reaching the 'canceled' order state.
      'commerce_order.cancel.post_transition' => ['onCartOrderCancel', -100],
    ];
    return $events;
  }

  /**
   * Creates and activates a license in reaction to an order state change.
   *
   * We always create a license when the order goes through the 'place'
   * transition, regardless of which state that reaches, and at latest activate
   * it when the order reaches the 'completed' state.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onCartOrderTransition(WorkflowTransitionEvent $event) {
    // Get the states we are leaving and reaching.
    $from_state = $event->getFromState()->getId();
    $reached_state = $event->getToState()->getId();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    $license_order_items = $this->getOrderItemsWithLicensedProducts($order);

    foreach ($license_order_items as $order_item) {
      // We don't need to do anything if there is already an active license on
      // the order item.
      // This happens when we come here for the 'validate' transition having
      // already come for the 'place' transition , or if another event
      // subscriber has activated the license.
      if (!empty($order_item->license->entity) && $order_item->license->entity->getState()->value == 'active') {
        continue;
      }

      $purchased_entity = $order_item->getPurchasedEntity();

      // TODO: throw an exception if the variation doesn't have this set.
      $license_type_plugin = $purchased_entity->get('license_type')->first()->getTargetInstance();

      // Create the license the first time we come here, though allow for
      // something else to have created it for us already: this allows for
      // orders to be created programmatically with a configured license.
      if (empty($order_item->license->entity)) {
        // Create a new license. It will be in the 'new' state and so not yet
        // active.
        $license = $this->licenseStorage->createFromOrderItem($order_item);

        $license->save();

        // Set the license field on the order item so we have a reference
        // and can get hold of it in later events.
        $order_item->license = $license->id();
        $order_item->save();
      }
      else {
        // Get the existing license the order item refers to.
        $license = $order_item->license->entity;
      }

      // Now determine whether to activate it.
      $activate_license = FALSE;
      if ($reached_state == 'completed') {
        // Always activate the license when we reach the 'completed' state.
        $activate_license = TRUE;
      }
      else {
        // Activate the license in the 'place' transition if the product
        // variation type is configured to do so.
        // This then relies on onCartOrderCancel() to cancel the license if
        // the order itself is canceled later.
        $product_variation_type = $this->entityTypeManager->getStorage('commerce_product_variation_type')->load($purchased_entity->bundle());
        $activate_on_place = $product_variation_type->getThirdPartySetting('commerce_license', 'activate_on_place');

        // We have to check the from state, because the event can't tell us
        // the transition: see https://www.drupal.org/project/state_machine/issues/2931447
        if ($activate_on_place && $from_state == 'draft') {
          $activate_license = TRUE;
        }
      }

      if (!$activate_license) {
        continue;
      }

      // Attempt to activate and confirm the license.
      // TODO: This needs to be expanded for synchronizable licenses.
      // TODO: how does a license type plugin indicate that it's not able to
      // activate? And how do we notify the order at this point?
      $transition = $license->getState()->getWorkflow()->getTransition('activate');
      $license->getState()->applyTransition($transition);
      $license->save();

      $transition = $license->getState()->getWorkflow()->getTransition('confirm');
      $license->getState()->applyTransition($transition);
      $license->save();
    }
  }

  /**
   * Reacts to an order being cancelled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onCartOrderCancel(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    $license_order_items = $this->getOrderItemsWithLicensedProducts($order);

    foreach ($license_order_items as $order_item) {
      // Get the license from the order item.
      $license = $order_item->license->entity;

      // Cancel the license.
      $transition = $license->getState()->getWorkflow()->getTransition('cancel');
      $license->getState()->applyTransition($transition);
      $license->save();
    }
  }

  /**
   * Returns the order items from an order which are for licensed products.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   An array of the order items whose purchased products are for licenses.
   */
  protected function getOrderItemsWithLicensedProducts(OrderInterface $order) {
    $return_items = [];

    foreach ($order->getItems() as $order_item) {
      // Skip order items that do not have a license reference field.
      // We check order items rather than the purchased entity to allow products
      // with licenses to be purchased without the checkout flow triggering
      // our synchronization. This is for cases such as recurring orders, where
      // the license entity should not be put through the normal workflow.
      // Checking the order item's bundle for our entity trait is expensive, as
      // it requires loading the bundle entity to call hasTrait() on it.
      // For now, just check whether the order item has our trait's field on it.
      // @see https://www.drupal.org/node/2894805
      if (!$order_item->hasField('license')) {
        continue;
      }

      $return_items[] = $order_item;
    }

    return $return_items;
  }

}
