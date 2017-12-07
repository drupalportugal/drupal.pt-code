<?php

namespace Drupal\commerce_license\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for License edit forms.
 *
 * @ingroup commerce_license
 */
class LicenseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label License.', [
          '%label' => $entity->label(),
        ]));

        break;

      default:
        drupal_set_message($this->t('Saved the %label License.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.commerce_license.canonical', ['commerce_license' => $entity->id()]);
  }

}
