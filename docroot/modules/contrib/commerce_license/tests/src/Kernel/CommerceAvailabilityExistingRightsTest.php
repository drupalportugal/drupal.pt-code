<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_cart\Kernel\CartManagerTestTrait;

/**
 * Tests a product is not added to the cart when the user has existing rights.
 *
 * This tests that Commerce's availability checker is correctly notified to
 * prevent a product being added to the cart when the license type plugin
 * reports that the customer already has the rights the license grants.
 *
 * See \Drupal\Tests\commerce_cart\Kernel\CartOrderPlacedTest for test code for
 * working with orders.
 *
 * @group commerce_license
 */
class CommerceAvailabilityExistingRightsTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->createUser();

    // Create an order type for licenses which uses the fulfillment workflow.
    $order_type = $this->createEntity('commerce_order_type', [
      'id' => 'license_order_type',
      'label' => $this->randomMachineName(),
      'workflow' => 'order_fulfillment',
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
    $this->traitManager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $trait = $this->traitManager->createInstance('commerce_license_order_item_type');
    $this->traitManager->installTrait($trait, 'commerce_order_item', $order_item_type->id());

    // Create a product variation type with the license trait, using our order
    // item type.
    $product_variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'license_pv_type',
      'label' => $this->randomMachineName(),
      'orderItemType' => 'license_order_item_type',
      'traits' => ['commerce_license'],
    ]);
    $trait = $this->traitManager->createInstance('commerce_license');
    $this->traitManager->installTrait($trait, 'commerce_product_variation', $product_variation_type->id());

    // Create a product variation which grants a license.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'license_pv_type',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'existing_rights_check_config',
        'target_plugin_configuration' => [],
      ],
      // Use the unlimited expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
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

    // Create a user to use for orders.
    $this->user = $this->createUser();
  }

  /**
   * Tests a product is not to the cart when the user has existing rights.
   */
  public function testAddToCart() {
    $this->installCommerceCart();

    $this->store = $this->createStore();
    $customer = $this->createUser();
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $customer);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');

    // Tell our test license type plugin to report that the user has existing rights.
    \Drupal::state()->set('commerce_license_test.existing_rights_check_config', TRUE);

    // Try to add the product to the cart.
    $this->cartManager->addEntity($cart_order, $this->variation);
    $order = $this->reloadEntity($cart_order);

    $this->assertTrue(\Drupal::state()->get('commerce_license_test.called.checkUserHasExistingRights'), "The checkUserHasExistingRights() method was called on the license type plugin.");

    $this->assertCount(0, $order->getItems(), "The product was not added to the cart.");

    // Tell our test license type plugin to report that the user does not have
    // existing rights.
    \Drupal::state()->set('commerce_license_test.existing_rights_check_config', FALSE);

    // Try to add the product to the cart.
    $this->cartManager->addEntity($cart_order, $this->variation);
    $order = $this->reloadEntity($cart_order);

    $this->assertCount(1, $order->getItems(), "The product was added to the cart.");
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
