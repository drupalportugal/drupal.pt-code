<?php

namespace Drupal\commerce_license;

use Drupal\Core\Session\AccountInterface;
use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;

/**
 * Prevents purchase of a license that grants rights the user already has.
 *
 * This does not check existing licenses, but checks the granted features
 * directly. For example, for a role license, this checks whether the user has
 * the role the license grants, rather than whether they have a license for
 * that role.
 *
 * Using an availability checker rather than an order processor, even though
 * they currently ultimately do the same thing (as availability checkers are
 * processed by AvailabilityOrderProcessor, which is itself an order processor),
 * because eventually availability checkers should deal with hiding the 'add to
 * cart' form -- see https://www.drupal.org/node/2710107.
 *
 * @see Drupal\commerce_license\LicenseOrderProcessorMultiples
 */
class LicenseAvailabilityCheckerExistingRights implements AvailabilityCheckerInterface {

  /**
   * Constructs a new availability checker.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    AccountInterface $current_user
  ) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $entity) {
    // This applies only to product variations which have our license trait on
    // them. Check for the field the trait provides, as checking for the trait
    // on the bundle is expensive -- see https://www.drupal.org/node/2894805.
    if (!$entity->hasField('license_type')) {
      return FALSE;
    }

    // This applies only to license types that implement the interface.
    $license_type_plugin = $entity->license_type->first()->getTargetInstance();
    if ($license_type_plugin instanceof ExistingRightsFromConfigurationCheckingInterface) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Hand over to the license type plugin configured in the product variation,
    // to let it determine whether the user already has what the license would
    // grant.
    $user = $context->getCustomer();
    $license_type_plugin = $entity->license_type->first()->getTargetInstance();

    $existing_rights_result = $license_type_plugin->checkUserHasExistingRights($user);

    if ($existing_rights_result->hasExistingRights()) {
      // Show a message that includes the reason from the rights check.
      if ($user->id() == $this->currentUser->id()) {
        $rights_check_message = $existing_rights_result->getOwnerUserMessage();
      }
      else {
        $rights_check_message = $existing_rights_result->getOtherUserMessage();
      }
      $message = $rights_check_message . ' ' . t("You may not purchase the @product-label product.", [
        '@product-label' => $entity->label(),
      ]);
      drupal_set_message($message);

      return FALSE;
    }

    // No opinion: return NULL.
  }

}
