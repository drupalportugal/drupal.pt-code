<?php

/**
 * @file
 * Post update functions for Eu Cookie Compliance.
 */

use Drupal\user\Entity\Role;

/**
 * @addtogroup updates-8.x-1.0-beta5-to-8.x-1.0-beta6
 * @{
 */

/**
 * Update permissions keys to standardize permission machine name.
 */
function eu_cookie_compliance_post_update_permission_keys_to_lowercase() {
  /** @var \Drupal\user\RoleInterface $role */
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('administer EU Cookie Compliance popup')) {
      $role->revokePermission('administer EU Cookie Compliance popup');
      $role->grantPermission('administer eu cookie compliance popup');
    }
    if ($role->hasPermission('display EU Cookie Compliance popup')) {
      $role->revokePermission('display EU Cookie Compliance popup');
      $role->grantPermission('display eu cookie compliance popup');
    }
    $role->save();
  }
}

/**
 * @} End of "addtogroup updates-8.x-1.0-beta5-to-8.x-1.0-beta6".
 */
