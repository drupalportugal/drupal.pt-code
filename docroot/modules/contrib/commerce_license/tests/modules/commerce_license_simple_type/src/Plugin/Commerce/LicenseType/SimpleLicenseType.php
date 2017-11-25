<?php

namespace Drupal\commerce_license_simple_type\Plugin\Commerce\LicenseType;

use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * This license type plugin is for use in tests that don't need to do anything
 * in particular with the license type, but need to give a type for license
 * entities.
 *
 * @CommerceLicenseType(
 *   id = "simple",
 *   label = @Translation("Simple license"),
 * )
 */
class SimpleLicenseType extends LicenseTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    return 'test license';
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
  }

}
