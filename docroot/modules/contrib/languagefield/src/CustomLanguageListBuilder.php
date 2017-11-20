<?php

namespace Drupal\languagefield;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class to build a listing of Custom Languages.
 *
 * @see \Drupal\user\Entity\CustomLanguage
 */
class CustomLanguageListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'languagefield_custom_language_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Language name');
    $header['langcode'] = t('Language code');
    $header['native_name'] = t('Language native name');
    return $header + parent::buildHeader();
    
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\languagefield\Entity\CustomLanguageInterface */
    $row['label'] = $entity->label();
    $row['langcode'] = ['#markup' => $entity->id()];
    $row['native_name'] = ['#markup' => $entity->getNativeName()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => t('Edit'),
        'weight' => 20,
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message(t('The language has been updated.'));
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = $this->t('There is no custom language.');
    return $build;
  }
  
}
