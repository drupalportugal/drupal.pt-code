<?php

namespace Drupal\commerce_license_test\Plugin\Commerce\LicenseType;

use Drupal\user\UserInterface;
use Drupal\commerce_license\ExistingRights\ExistingRightsResult;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;

/**
 * Test license type which reports the user has existing rights.
 *
 * @CommerceLicenseType(
 *   id = "existing_rights_check_config",
 *   label = @Translation("Tests existing rights"),
 * )
 */
class ExistingRightsCheckConfig extends TestLicenseBase implements ExistingRightsFromConfigurationCheckingInterface {

  /**
   * {@inheritdoc}
   */
  public function checkUserHasExistingRights(UserInterface $user) {
    // Mark that we've been called.
    \Drupal::state()->set('commerce_license_test.called.checkUserHasExistingRights', TRUE);

    // The state tells us whether to say rights exist or not.
    return ExistingRightsResult::rightsExistIf(
      \Drupal::state()->get('commerce_license_test.existing_rights_check_config'),
      $this->t("You already have the rights."),
      $this->t("The user already has the rights.")
    );
  }

}
