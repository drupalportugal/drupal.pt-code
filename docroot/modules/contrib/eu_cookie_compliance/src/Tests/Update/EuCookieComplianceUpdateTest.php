<?php

namespace Drupal\eu_cookie_compliance\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the upgrade path for eu Cookie Compliance permission keys.
 *
 * @see https://www.drupal.org/node/2774143
 *
 * @group eu_cookie_compliance
 */
class EuCookieComplianceUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['eu_cookie_compliance'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.eu-cookie-compliance-beta5.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.user-role-manager-2774143.php',
    ];
  }

  /**
   * Tests Eu Cookie Compliance permission keys updates.
   *
   * @see eu_cookie_compliance_post_update_permission_keys_to_lowercase()
   */
  public function testPostUpdatePermissionKeys() {
    // Login using root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/people/permissions');

    // Tests to ensure that before updates the permission keys are in camelcase.
    /** @var \Drupal\user\RoleInterface $testfor2774143 */
    $testfor2774143 = Role::load('testfor2774143');
    $this->assertTrue($testfor2774143->hasPermission('administer EU Cookie Compliance popup'));
    $this->assertFalse($testfor2774143->hasPermission('administer eu cookie compliance popup'));
    /** @var \Drupal\user\RoleInterface $secondtestfor2774143 */
    $secondtestfor2774143 = Role::load('secondtestfor2774143');
    $this->assertTrue($secondtestfor2774143->hasPermission('display EU Cookie Compliance popup'));
    $this->assertFalse($secondtestfor2774143->hasPermission('display eu cookie compliance popup'));

    $this->runUpdates();

    $this->drupalGet('admin/people/permissions');

    // Tests to ensure that after updates the permission keys are in lowercase.
    /** @var \Drupal\user\RoleInterface $testfor2774143 */
    $testfor2774143 = Role::load('testfor2774143');
    $this->assertFalse($testfor2774143->hasPermission('administer EU Cookie Compliance popup'));
    $this->assertTrue($testfor2774143->hasPermission('administer eu cookie compliance popup'));
    /** @var \Drupal\user\RoleInterface $secondtestfor2774143 */
    $secondtestfor2774143 = Role::load('secondtestfor2774143');
    $this->assertFalse($secondtestfor2774143->hasPermission('display EU Cookie Compliance popup'));
    $this->assertTrue($secondtestfor2774143->hasPermission('display eu cookie compliance popup'));
  }

}
