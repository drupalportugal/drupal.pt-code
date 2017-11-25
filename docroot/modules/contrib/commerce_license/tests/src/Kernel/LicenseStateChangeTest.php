<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests changes to the license state have the correct effects.
 *
 * @group commerce_license
 */
class LicenseStateChangeTest extends EntityKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'state_machine',
    'commerce',
    'commerce_price',
    'commerce_product',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
  ];

  /**
   * The license storage.
   */
  protected $licenseStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_license');

    $this->licenseStorage = \Drupal::service('entity_type.manager')->getStorage('commerce_license');
  }

  /**
   * Tests that changes to a license's state causes the plugin to react.
   */
  public function testLicenseStateChanges() {
    $owner = $this->createUser();

    // Create a license in the 'new' state.
    $license = $this->licenseStorage->create([
      'type' => 'state_change_test',
      'state' => 'new',
      'product_variation' => 1,
      'uid' => $owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);

    $license->save();

    // The license is not active: the plugin should not react.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), NULL);

    // Activate the license: this puts it into the 'pending' state.
    $transition = $license->getState()->getWorkflow()->getTransition('activate');
    $license->getState()->applyTransition($transition);
    $license->save();

    // The license is not active: the plugin should not react.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), NULL);

    // Confirm the license: this puts it into the 'active' state.
    $transition = $license->getState()->getWorkflow()->getTransition('confirm');
    $license->getState()->applyTransition($transition);
    $license->save();

    // The license is now active: the plugin should be called.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), 'grantLicense');

    // Reset the test tracking state.
    \Drupal::state()->set('commerce_license_state_change_test', NULL);

    // Save the license again without changing its state.
    $license->save();

    // The license is unchanged: the plugin should not react.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), NULL);

    // Suspend the license.
    $transition = $license->getState()->getWorkflow()->getTransition('suspend');
    $license->getState()->applyTransition($transition);
    $license->save();

    // The license is now inactive: the plugin should be called.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), 'revokeLicense');

    // Reset the test tracking state.
    \Drupal::state()->set('commerce_license_state_change_test', NULL);

    // Revoke the license.
    $transition = $license->getState()->getWorkflow()->getTransition('revoke');
    $license->getState()->applyTransition($transition);
    $license->save();

    // Although the license state changed, it has gone from one inactive state
    // to another: the plugin should not react.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), NULL);

    // Reset the test tracking state.
    \Drupal::state()->set('commerce_license_state_change_test', NULL);

    // Test creating a license initially in the active state.
    $license = $this->licenseStorage->create([
      'type' => 'state_change_test',
      'state' => 'active',
      'product_variation' => 1,
      'uid' => 1,
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);

    $license->save();

    // The license is created active: the plugin should be called.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), 'grantLicense');
  }

}
