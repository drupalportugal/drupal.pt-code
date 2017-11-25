<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\user\UserInterface;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\ExistingRights\ExistingRightsResult;

/**
 * Interface for license types that check for existing rights using config.
 *
 * This interface should be used by license types that are able to use the
 * license configuration on a product variation to check for existing rights.
 *
 * (License types that need to set values on the License entity based on
 * customer input, and therefore do not have a complete picture of the future
 * License entity from just the configuration on the Product Variation, should
 * not use this.)
 *
 * @see \Drupal\commerce_license\LicenseAvailabilityCheckerExistingRights
 */
interface ExistingRightsFromConfigurationCheckingInterface {

  /**
   * Checks whether the user already has the rights this license grants.
   *
   * This is called on a configured plugin.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being checked.
   *
   * @return \Drupal\commerce_license\ExistingRights\ExistingRightsResult
   *   A rights result object specifying the result and messages.
   */
  public function checkUserHasExistingRights(UserInterface $user);

}
