<?php

namespace Drupal\commerce_license\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for License edit forms.
 *
 * @ingroup commerce_license
 */
class LicenseEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    drupal_set_message($this->t('Saved the %label License.', [
      '%label' => $entity->label(),
    ]));

    $form_state->setRedirect('entity.commerce_license.canonical', ['commerce_license' => $entity->id()]);
  }

}
