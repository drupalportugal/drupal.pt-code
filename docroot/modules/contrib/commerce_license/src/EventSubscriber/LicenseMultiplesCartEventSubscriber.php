<?php

namespace Drupal\commerce_license\EventSubscriber;

use Drupal\Core\Url;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemUpdateEvent;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ensures only 1 of each license may be added to the cart.
 *
 * We need to act on the cart event, as need to change the item quantity before
 * \Drupal\commerce_cart\EventSubscriber\CartEventSubscriber can show a message
 * to the user about adding the item to the cart.
 *
 * @see \Drupal\commerce_license\LicenseOrderProcessorMultiples
 */
class LicenseMultiplesCartEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      // These need to run before \Drupal\commerce_cart\EventSubscriber\CartEventSubscriber
      // Also expects the patch at https://www.drupal.org/project/commerce/issues/2930979
      // so that CartEventSubscriber doesn't show its message when we prevent
      // an item from being added.
      CartEvents::CART_ENTITY_ADD => ['onCartEntityAdd', 100],
      CartEvents::CART_ORDER_ITEM_UPDATE => ['onCartItemUpdate', 100],
    ];
    return $events;
  }

  /**
   * Enforces the maximum count when a product is added to the cart.
   *
   * Note that this event is triggered when adding a product to the cart from
   * the add to cart form when an item for this product is already in the cart.
   * This is treated as an entity add, even though technically it's an order
   * item that is being updated.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The cart event.
   */
  public function onCartEntityAdd(CartEntityAddEvent $event) {
    $order_item = $event->getOrderItem();

    // Only act if the order item has a license reference field.
    if (!$order_item->hasField('license')) {
      return;
    }

    // TODO: Allow license type plugins to respond here, as for types that
    // collect user data in the checkout form, the same product variation can
    // result in different licenses.

    // Only act if the order item quantity is greater than 1.
    if ($order_item->getQuantity() == 1) {
      return;
    }

    // Force the quantity back to 1.
    $this->forceOrderItemQuantity($order_item);

    drupal_set_message(t('You may only have one of @product-label in <a href="@cart-url">your cart</a>.', [
      '@product-label' => $order_item->getPurchasedEntity()->label(),
      '@cart-url' => Url::fromRoute('commerce_cart.page')->toString(),
    ]), 'error');
  }

  /**
   * Enforces the maximum count when an item is updated.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemUpdateEvent $event
   *   The cart event.
   */
  public function onCartItemUpdate(CartOrderItemUpdateEvent $event) {
    $order_item = $event->getOrderItem();

    // Only act if the order item has a license reference field.
    if (!$order_item->hasField('license')) {
      return;
    }

    // TODO: Allow license type plugins to respond here, as for types that
    // collect user data in the checkout form, the same product variation can
    // result in different licenses.

    // Only act if the order item quantity is greater than 1.
    if ($order_item->getQuantity() == 1) {
      return;
    }

    // Force the quantity back to 1.
    $this->forceOrderItemQuantity($order_item);

    // Don't show a link to the cart as the user will typically be on the cart
    // page.
    drupal_set_message(t('You may only have one of @product-label in your cart.', [
      '@product-label' => $order_item->getPurchasedEntity()->label(),
    ]), 'error');
  }

  /**
   * Forces the quantity of an order item to 1, and saves it.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  protected function forceOrderItemQuantity(OrderItemInterface $order_item) {
    // Force the quantity back to 1.
    $order_item->setQuantity(1);

    // Save the order item again.
    $order_item->save();
  }

}
