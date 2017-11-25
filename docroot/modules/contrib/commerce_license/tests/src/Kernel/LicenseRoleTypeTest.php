<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the role license type.
 *
 * @group commerce_license
 */
class LicenseRoleTypeTest extends EntityKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'entity',
    'state_machine',
    'commerce',
    'commerce_price',
    'commerce_product',
    'recurring_period',
    'commerce_license',
  ];

  /**
   * The license storage.
   */
  protected $licenseStorage;

  /**
   * The license type plugin manager.
   */
  protected $licenseTypeManager;

  /**
   * The role storage.
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_license');
    $this->installConfig('user');

    // Install the bundle plugins for the license entity type which this
    // module provides. This takes care of creating the fields which the bundle
    // plugins define.
    $this->container->get('entity.bundle_plugin_installer')->installBundles(
      $this->container->get('entity_type.manager')->getDefinition('commerce_license'),
      ['commerce_license']
    );

    $this->licenseTypeManager = $this->container->get('plugin.manager.commerce_license_type');
    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');
    $this->roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');
  }

  /**
   * Tests that a role license grants and revokes a role from its owner.
   */
  public function testLicenseGrantRevoke() {
    $role = $this->roleStorage->create(['id' => 'licensed_role']);
    $role->save();

    $license_owner = $this->createUser();

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'role',
      'state' => 'new',
      'product_variation' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_role' => $role,
    ]);

    $license->save();

    // Assert the user does not have the role.
    $license_owner = $this->reloadEntity($license_owner);
    $this->assertFalse($license_owner->hasRole('licensed_role'), "The user does not have the licensed role.");

    // Don't bother pushing the license through state changes, as that is
    // by covered by LicenseStateChangeTest. Just call the plugin direct to
    // grant the license.
    $license->getTypePlugin()->grantLicense($license);

    // The license owner now has the role.
    $license_owner = $this->reloadEntity($license_owner);
    $this->assertTrue($license_owner->hasRole('licensed_role'), "The user has the licensed role.");

    // Revoke the license.
    $license->getTypePlugin()->revokeLicense($license);

    // Assert the user does not have the role.
    $license_owner = $this->reloadEntity($license_owner);
    $this->assertFalse($license_owner->hasRole('licensed_role'), "The user does not have the licensed role.");
  }

  /**
   * Tests a license receives field values from a configured plugin.
   */
  public function testLicenseCreationFromPlugin() {
    $role = $this->roleStorage->create(['id' => 'licensed_role']);
    $role->save();

    $license_owner = $this->createUser();

    // Create a license which doesn't have any type-specific field values set.
    $license = $this->licenseStorage->create([
      'type' => 'role',
      'state' => 'new',
      'product_variation' => 1,
      'uid' => $license_owner->id(),
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Create a configured role license plugin.
    $plugin_configuration = [
      'license_role' => $role->id(),
    ];
    $license_type_plugin = $this->licenseTypeManager->createInstance('role', $plugin_configuration);

    // Set the license's type-specific fields from the configured plugin.
    $license->setValuesFromPlugin($license_type_plugin);

    $license->save();
    $license = $this->reloadEntity($license);

    $this->assertEquals($role->id(), $license->license_role->target_id, "The role field was set on the license.");
  }

}
