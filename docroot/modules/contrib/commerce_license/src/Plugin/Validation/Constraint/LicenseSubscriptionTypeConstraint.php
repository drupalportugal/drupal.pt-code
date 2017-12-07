<?php

namespace Drupal\commerce_license\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures the license subscription type can only be used with a license trait.
 *
 * @Constraint(
 *   id = "LicenseSubscriptionType",
 *   label = @Translation("The license subscription type.", context = "Validation")
 * )
 */
class LicenseSubscriptionTypeConstraint extends Constraint {

  public $message = 'The License subscription type may only be used on product variations which are configured to use a license.' . ' '
    . 'You must enable the license trait on the <a href="@url-edit-product-variation-type">edit %product-variation-type-label product variation type</a>.';

}
