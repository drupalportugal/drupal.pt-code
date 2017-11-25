<?php

namespace Drupal\commerce_license;

use Drupal\entity\UncacheableEntityPermissionProvider;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides permissions for the License entity.
 *
 * This makes a few minor changes to the permissions provided by Entity module's
 * generic permissions provider.
 */
class LicensePermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);

    $entity_type_id = $entity_type->id();

    // Mark the 'overview' permission as restricted.
    $permissions["access {$entity_type_id} overview"]['restrict access'] = TRUE;

    // Add a description to the 'create'  to make it clear that it only covers
    // admin creation, not creation via product purchase.
    $permissions["create {$entity_type_id}"]['description'] = $this->t('Create licenses in administrative mode, bypassing the purchase of a product.');

    return $permissions;
  }

}
