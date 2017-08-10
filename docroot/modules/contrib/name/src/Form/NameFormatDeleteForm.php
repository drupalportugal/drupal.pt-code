<?php

/**
 * @file
 * Contains \Drupal\name\Form\NameFormatDeleteForm.
 */

namespace Drupal\name\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a name format.
 */
class NameFormatDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the custom format %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('name.name_format_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->entity->delete();
    drupal_set_message(t('The custom name format %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
