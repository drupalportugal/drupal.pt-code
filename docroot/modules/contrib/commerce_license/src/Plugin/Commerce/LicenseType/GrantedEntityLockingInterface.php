<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Interface for license types that lock an aspect of the entities they grant.
 *
 * This interface should be used by license types that are able to alter the
 * forms for entities they grant rights to. Typically, this will be to prevent
 * removal of something the licenes grants, and thus prevent the license and the
 * entity getting out of sync.
 *
 * For example, the Role license type that grants a role to a user prevents the
 * user having that role removed in the user edit form.
 */
interface GrantedEntityLockingInterface {

  /**
   * Alter a form for an entity owned by the license owner.
   *
   * This is for use by license type plugins that change something on an entity
   * owned by the license owner when they grant the license.
   *
   * This allows the license type plugin to disable elements of the form if they
   * are being granted by the license, to prevent them being removed while the
   * license is active. For example, the role plugin locks the role that it
   * grants a user on that user's edit form.
   *
   * @see commerce_license_form_alter()
   *
   * @param &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param $form_id
   *   The form ID.
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license being considered.
   * @param \Drupal\Core\Entity\EntityInterface $form_entity
   *   The entity being edited in the form. It is active, and either owned by
   *   the license owner, or is the actual license owner user entity.
   */
  public function alterEntityOwnerForm(&$form, FormStateInterface $form_state, $form_id, LicenseInterface $license, EntityInterface $form_entity);

}
