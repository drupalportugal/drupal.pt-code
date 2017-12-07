<?php

namespace Drupal\commerce_license\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LicenseSubscriptionType constraint.
 */
class LicenseSubscriptionTypeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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

    $subscription_plugin_id = $items->first()->target_plugin_id;

    // Don't act if the subscription plugin isn't ours.
    if ($subscription_plugin_id != 'license') {
      return;
    }

    $product_variation = $items->getEntity();

    if (!$product_variation->hasField('license_type')) {
      $product_variation_type_id = $product_variation->bundle();
      $product_variation_type = $this->entityTypeManager->getStorage('commerce_product_variation_type')->load($product_variation_type_id);

      $this->context->buildViolation($constraint->message)
        ->setParameter('@url-edit-product-variation-type', $product_variation_type->toUrl('edit-form')->toString())
        ->setParameter('%product-variation-type-label', $product_variation_type->label())
        ->addViolation();
    }
  }

}
