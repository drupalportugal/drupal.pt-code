<?php

namespace Drupal\commerce_profile_pane\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives checkout pane plugins for profile types.
 *
 * Checkout pane plugins exist as one instances for each checkout workflow, and
 * new ones can't be added, so we need a derivative plugin for each profile
 * type.
 */
class ProfileFormCheckoutPaneDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Creates a deriver instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $profile_bundle_info = $this->entityTypeBundleInfo->getBundleInfo('profile');

    foreach ($profile_bundle_info as $bundle => $info) {
      // Skip the 'customer' type, which Commerce provides and has its own
      // checkout panes to set values into, which we don't want to interfere
      // with.
      if ($bundle == 'customer') {
        continue;
      }

      // TODO: this allows profile types which are set to allow multiple, but
      // the pane doesn't allow any way to set which profile gets edited.

      $this->derivatives[$bundle] = [
        'label' => $this->t('@profile-type profile form', [
          '@profile-type' => $info['label'],
        ]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
