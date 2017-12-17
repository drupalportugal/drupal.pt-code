<?php

namespace Drupal\commerce_license\FormAlter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\GrantedEntityLockingInterface;

/**
 * Alters entity forms for entities that are affected by a license.
 *
 * This allows license type plugins that implement
 * \Drupal\commerce_license\Plugin\Commerce\LicenseType\GrantedEntityLockingInterface
 * to alter forms for entities owned by the owner of the license.
 */
class GrantedEntityFormAlter {

  /**
   * The entity that is being edited in the form being altered.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Construct a GrantedEntityFormAlter object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Alters the form.
   *
   * Helper for hook_form_alter(); same parameters.
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    if (!($form_object instanceof EntityFormInterface)) {
      // We're only interested in forms for an entity.
      return;
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $entity = $form_object->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    if (!($entity instanceof EntityOwnerInterface) && $entity_type_id != 'user') {
      // We only act on entities that have an owner, and user entities.
      return;
    }

    if ($entity_type_id == 'commerce_license') {
      // Don't act on licenses themselves.
      return;
    }

    if ($entity->isNew()) {
      // Don't act on a new entity, as it can't be the target of a license.
      return;
    }

    // Get the ID of owner of this entity, or of the user itself.
    $user_id = ($entity_type_id == 'user') ? $entity->id() : $entity->getOwnerId();
    if (is_null($user_id)) {
      // Bail if we didn't manage to get a user ID. Shouldn't get this far but
      // some forms might misbehave.
      return;
    }

    // Get all active licenses owned by this user.
    // Note: this assumes that users have relatively few licenses each. If
    // scalability becomes an issue, consider instead first asking each license
    // type plugin for which entity types it might be interested in, and then
    // query only for those license types if there is a match with the form's
    // entity.
    $licenses = \Drupal::service('entity_type.manager')->getStorage('commerce_license')->loadByProperties([
      'uid' => $user_id,
      'state' => 'active',
    ]);

    // Let each suitable license's plugin alter the form for the license.
    foreach ($licenses as $license) {
      $license_type_plugin = $license->getTypePlugin();
      if ($license_type_plugin instanceof GrantedEntityLockingInterface) {
        $license_type_plugin->alterEntityOwnerForm($form, $form_state, $form_id, $license, $entity);
      }
    }

  }

}
