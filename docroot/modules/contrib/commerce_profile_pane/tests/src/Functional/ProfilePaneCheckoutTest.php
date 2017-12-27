<?php

namespace Drupal\Tests\commerce_profile_pane\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\profile\Entity\ProfileType;

/**
 * Test case class TODO.
 */
class ProfilePaneCheckoutTest extends CommerceBrowserTestBase {

  /**
   * The ID of the profile type used in the test.
   *
   * @var string
   */
  protected $profileTypeId;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'views_ui',
    'commerce_profile_pane',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->profileStorage = \Drupal::service('entity_type.manager')->getStorage('profile');

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    // Create a profile type.
    // This will automatically get the pane showing in the checkout flow.
    $this->profileTypeId = $this->randomMachineName();
    $type = ProfileType::create([
      'id' => $this->profileTypeId,
      'label' => $this->randomMachineName(),
      'registration' => FALSE,
      'roles' => [],
      'multiple' => FALSE,
    ]);
    $type->save();

    // Add a field to the profile type and set it to show in the default
    // form mode.
    \Drupal::service('entity_type.manager')->getStorage('field_storage_config')->create([
      'entity_type' => 'profile',
      'field_name' => 'test_field',
      'type' => 'string',
    ])->save();
    \Drupal::service('entity_type.manager')->getStorage('field_config')->create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'bundle' => $this->profileTypeId,
      'label' => 'Profile field',
    ])->save();
    entity_get_form_display('profile', $this->profileTypeId, 'default')
      ->setComponent('test_field', ['type' => 'string_textfield'])
      ->save();

    // Create a product and variation to put into the cart.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$this->variation],
      'stores' => [$this->store],
    ]);
  }

  /**
   * Tests a user with a profile sees it in the pane.
   */
  public function testUserWithProfile() {
    $customer = $this->createUser([
      "create {$this->profileTypeId} profile",
      "update own {$this->profileTypeId} profile",
    ]);
    $this->drupalLogin($customer);

    // Create a profile for the user.
    $this->drupalGet('user/' . $customer->id() . '/' . $this->profileTypeId);
    $field_value = $this->randomString();
    $this->submitForm([
      "test_field[0][value]" => $field_value,
    ], 'Save');

    // Create a cart for the customer. No need to go via the UI.
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('default', $this->store, $customer);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $this->cartManager->addEntity($cart_order, $this->variation);

    $this->drupalGet('cart');
    $this->submitForm([], 'Checkout');

    $this->assertSession()->pageTextContains('Edit profile', "The profile pane is shown in the checkout step.");
    $this->assertSession()->pageTextContains('Profile field', "The profile field is shown in the profile checkout pane.");
    $this->assertSession()->fieldValueEquals("profile_form:{$this->profileTypeId}[profile][test_field][0][value]", $field_value, "The existing profile field value is shown in the form.");

    $field_new_value = $this->randomString();
    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
      "profile_form:{$this->profileTypeId}[profile][test_field][0][value]" => $field_new_value,
    ], 'Continue to review');

    $this->profileStorage->resetCache();

    $profile = $this->profileStorage->loadByUser($customer, $this->profileTypeId);

    $this->assertEquals($field_new_value, $profile->test_field->value, "The profile field value was updated.");
  }

  /**
   * Tests a user without a profile has one created by the pane.
   */
  public function testUserWithoutProfile() {
    $customer = $this->createUser([
      "create {$this->profileTypeId} profile",
      "update own {$this->profileTypeId} profile",
    ]);
    $this->drupalLogin($customer);

    $profile = $this->profileStorage->loadByUser($customer, $this->profileTypeId);
    $this->assertEmpty($profile, "The user does not have a {$this->profileTypeId} profile.");

    // Create a cart for the customer. No need to go via the UI.
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('default', $this->store, $customer);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $this->cartManager->addEntity($cart_order, $this->variation);

    $this->drupalGet('cart');
    $this->submitForm([], 'Checkout');

    $this->assertSession()->pageTextContains('Edit profile', "The profile pane is shown in the checkout step.");
    $this->assertSession()->pageTextContains('Profile field', "The profile field is shown in the profile checkout pane.");

    $field_value = $this->randomString();
    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
      "profile_form:{$this->profileTypeId}[profile][test_field][0][value]" => $field_value,
    ], 'Continue to review');

    $this->profileStorage->resetCache();

    $profile = $this->profileStorage->loadByUser($customer, $this->profileTypeId);
    $this->assertNotEmpty($profile, "The user has a {$this->profileTypeId} profile.");

    $this->assertEquals($field_value, $profile->test_field->value, "The profile field value was set.");
  }

}
