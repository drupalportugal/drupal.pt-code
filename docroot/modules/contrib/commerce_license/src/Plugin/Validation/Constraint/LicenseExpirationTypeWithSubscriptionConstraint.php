<?php

namespace Drupal\commerce_license\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures the license expiry type is unlimited when a subscription is used.
 *
 * @Constraint(
 *   id = "LicenseExpirationTypeWithSubscription",
 *   label = @Translation("The license expiry type when a subscription is present.", context = "Validation")
 * )
 */
class LicenseExpirationTypeWithSubscriptionConstraint extends Constraint {

  public $message = 'The License expiry must be set to "Unlimited" when a subscription is used.';

}
