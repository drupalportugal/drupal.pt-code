<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests Commerce Recurring's subscription lifecycle with a license.
 *
 * The code for creating the order with the subscription is from
 * \Drupal\Tests\commerce_recurring\Kernel\SubscriptionLifecycleTest.
 *
 * @group commerce_license
 *
 * @requires module advancedqueue
 * @requires module commerce_recurring
 */
class CommerceRecurringSubscriptionLifecycleTest extends CommerceKernelTestBase {

  use LicenseOrderCompletionTestTrait;

  /**
   * The license storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $licenseStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'advancedqueue',
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
    'commerce_recurring',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_subscription');
    $this->installEntitySchema('commerce_license');
    $this->installEntitySchema('user');
    $this->installSchema('advancedqueue', 'advancedqueue');
    $this->installConfig('entity');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_recurring');

    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');

    $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');

    // Create a user to use for orders.
    $this->user = $this->createUser();

    // Create an order type for licenses which uses the fulfillment workflow.
    $order_type = $this->createEntity('commerce_order_type', [
      'id' => 'license_order_type',
      'label' => $this->randomMachineName(),
      'workflow' => 'order_default',
    ]);
    commerce_order_add_order_items_field($order_type);

    // Create an order item type that uses that order type.
    $order_item_type = $this->createEntity('commerce_order_item_type', [
      'id' => 'license_order_item_type',
      'label' => $this->randomMachineName(),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'license_order_type',
      'traits' => ['commerce_license_order_item_type'],
    ]);
    $trait = $trait_manager->createInstance('commerce_license_order_item_type');
    $trait_manager->installTrait($trait, 'commerce_order_item', $order_item_type->id());

    // Create a product variation type with the license and subscription entity
    // traits, using our order item type.
    $product_variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'license_pv_type',
      'label' => $this->randomMachineName(),
      'orderItemType' => 'license_order_item_type',
      'traits' => ['commerce_license'],
    ]);
    $trait = $trait_manager->createInstance('commerce_license');
    $trait_manager->installTrait($trait, 'commerce_product_variation', $product_variation_type->id());
    $trait = $trait_manager->createInstance('purchasable_entity_subscription');
    $trait_manager->installTrait($trait, 'commerce_product_variation', $product_variation_type->id());

    // Create a billing schedule.
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = BillingSchedule::create([
      'id' => 'test_id',
      'label' => 'Hourly schedule',
      'displayLabel' => 'Hourly schedule',
      'billingType' => BillingSchedule::BILLING_TYPE_PREPAID,
      'plugin' => 'fixed',
      'configuration' => [
        'interval' => [
          'number' => '1',
          'unit' => 'hour',
        ],
      ],
      'prorater' => 'proportional',
      'proraterConfiguration' => [],
    ]);
    $billing_schedule->save();
    $this->billingSchedule = $this->reloadEntity($billing_schedule);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();
    $this->paymentGateway = $this->reloadEntity($payment_gateway);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->paymentGateway,
      'uid' => $this->user->id(),
    ]);
    $payment_method->save();
    $this->paymentMethod = $this->reloadEntity($payment_method);

    // Create a product variation which grants a license.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'license_pv_type',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'state_change_test',
        'target_plugin_configuration' => [],
      ],
      // Use the unlimited expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      // Subscription configuration.
      'billing_schedule' => $this->billingSchedule,
      'subscription_type' => [
        'target_plugin_id' => 'license',
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $this->reloadEntity($this->variation);
    $this->variation->save();
  }

  /**
   * Tests the creation of the license when a subscription order is placed.
   */
  public function testCreation() {
    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(0, $licenses, "There are no licenses yet.");

    $order_item = OrderItem::create([
      // The order item must be of a type with the license trait.
      'type' => 'license_order_item_type',
      'purchased_entity' => $this->variation,
      'quantity' => '1',
    ]);
    $order_item->save();

    $order = Order::create([
      // The order item must be of a type with a fulfillment workflow.
      'type' => 'license_order_type',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$order_item],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $order->save();

    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(0, $subscriptions);

    // Take the order through checkout.
    $this->completeLicenseOrderCheckout($order);
    $order = $this->reloadEntity($order);

    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethod()->id());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('1', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals('active', $subscription->getState()->value);

    // Confirm that a recurring order is present.
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $result = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->pager(1)
      ->execute();
    $this->assertNotEmpty($result);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $recurring_order = $order_storage->load(reset($result));
    $this->assertNotEmpty($recurring_order);
    // Confirm that the recurring order has an order item for the subscription.
    $recurring_order_items = $recurring_order->getItems();
    $this->assertCount(1, $recurring_order_items);
    $recurring_order_item = reset($recurring_order_items);
    $this->assertEquals($subscription->id(), $recurring_order_item->get('subscription')->target_id);

    // Check that the order item now refers to a new license which has been
    // created for the user.
    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(1, $licenses, "One license was saved.");
    $license = reset($licenses);

    $order_item = $this->reloadEntity($order_item);
    $this->assertEquals($license->id(), $order_item->license->entity->id(), "The order item has a reference to the saved license.");

    $this->assertEquals($license->id(), $subscription->license->entity->id(), "The subscription has a reference to the saved license.");

    $this->assertEquals('active', $license->state->value, "The license is active.");
    $this->assertEquals('grantLicense', \Drupal::state()->get('commerce_license_state_change_test'), "The license plugin's grantLicense() method was called.");

    // Make the subscription cancel.
    // This is the state it goes to when a renewal payment fails.
    // No need to go via the order system for this, as Commerce Recurring's
    // tests check this. We can just modify the subscription.
    $subscription->state = 'canceled';
    $subscription->save();

    $license = $this->reloadEntity($license);
    $this->assertEquals('canceled', $license->state->value, "The license is canceled.");
    $this->assertEquals('revokeLicense', \Drupal::state()->get('commerce_license_state_change_test'), "The license plugin's revokeLicense() method was called.");

    // TODO: cover the subscription reaching the 'expired' state -- though there
    // is nothing yet in Commerce Recurring that ever makes it reach that state.
  }

  /**
   * Creates and saves a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new, saved entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
