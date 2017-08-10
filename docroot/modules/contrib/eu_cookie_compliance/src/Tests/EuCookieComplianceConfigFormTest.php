<?php

namespace Drupal\eu_cookie_compliance\Tests;

/**
 * Test functionality for EU Cookie Compliance Config form.
 *
 * @group eu_cookie_compliance
 */
class EuCookieComplianceConfigFormTest extends EuCookieComplianceTestBase {

  /**
   * An admin user with administrative permissions for EUCC.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'eu_cookie_compliance',
    'eu_cookie_compliance_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and log in admin user.
    $this->adminUser = $this->drupalCreateUser(['display eu cookie compliance popup', 'administer eu cookie compliance popup']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the EuCookieComplianceConfigForm.
   */
  public function testEuCookieComplianceConfigForm() {
    $this->drupalGet('admin/config/system/eu-cookie-compliance');
    $this->assertNoFieldChecked('edit-popup-enabled');
    $edit = ['popup_enabled' => 1];

    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertFieldChecked('edit-popup-enabled');
    $this->assertText('Privacy policy link field is required.');

    $edit += ['popup_link' => 'https://drupal.org'];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
  }

}
