<?php

namespace Drupal\commerce_license\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LicenseExpirationTypeWithSubscription constraint.
 */
class LicenseExpirationTypeWithSubscriptionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LicenseSubscriptionTypeConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    $product_variation = $items->getEntity();
    if (!$product_variation->hasField('subscription_type')) {
      // Don't act if the product variation doens't use subscriptions.
      return;
    }

    $expiration_type_plugin_id = $items->first()->target_plugin_id;

    if ($expiration_type_plugin_id != 'unlimited') {
      $this->context->buildViolation($constraint->message)
        ->setInvalidValue($expiration_type_plugin_id)
        ->addViolation();
    }
  }

}
