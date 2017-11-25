<?php

namespace Drupal\Tests\commerce_license\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that a role granted by a license can't be removed in the user form.
 *
 * @group commerce_license
 */
class RoleGrantedLockingTest extends BrowserTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'recurring_period',
    'commerce_license',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->owner = $this->createUser();

    $this->roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');
    $role = $this->roleStorage->create([
      'id' => 'licensed_role',
      'label' => 'Licensed role',
    ]);
    $role->save();

    $this->licenseStorage = \Drupal::service('entity_type.manager')->getStorage('commerce_license');

    // Create a license in the 'active' state.
    $license = $this->licenseStorage->create([
      'type' => 'role',
      'state' => 'active',
      'product_variation' => 1,
      'uid' => $this->owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_role' => $role,
    ]);

    // Confirm the license: this puts it into the 'active' state.
    $transition = $license->getState()->getWorkflow()->getTransition('confirm');
    $license->getState()->applyTransition($transition);
    $license->save();
  }

  /**
   * Tests a role granted by a license is locked on a user's account form.
   */
  public function testUserFormHasLock() {
    // Create an admin user who can edit a user's roles.
    $admin = $this->createUser([
      'administer users',
      'administer permissions',
    ]);

    $this->drupalLogin($admin);

    // Get the account for for the license owner user.
    $this->drupalGet("user/" . $this->owner->id() . "/edit");
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldDisabled("roles[licensed_role]");
    $this->assertSession()->pageTextContains("This role is granted by a license. It cannot be removed manually.");
  }

}
