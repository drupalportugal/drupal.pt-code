<?php

namespace Drupal\languagefield\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the CustomLanguage entity edit forms.
 *
 * @todo: copy more code from \Drupal\language\Form\LanguageEditForm.
 */
class CustomLanguageForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\languagefield\Entity\CustomLanguage $entity */
    $entity = $this->entity;

    if ($entity->isNew()) {
      $form['langcode'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Language code'),
        '#default_value' => '',
        '#size' => 10,
        '#required' => TRUE,
        '#maxlength' => 10,
        '#description' => $this->t('Use language codes as <a href=":w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', [':w3ctags' => 'http://www.w3.org/International/articles/language-tags/']),
      ];
    }
    else {
      $form['langcode_view'] = [
        '#type' => 'item',
        '#title' => $this->t('Language code'),
        '#markup' => $entity->id(),
      ];
      $form['langcode'] = [
        '#type' => 'value',
        '#value' => $entity->id(),
      ];
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
    ];
    $form['native_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Native Name'),
      '#default_value' => $entity->getNativeName(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    /** @var \Drupal\languagefield\Entity\CustomLanguage $entity */
    $entity = $this->entity;

    //$edit_link = $this->entity->toLink($this->t('Edit'),'edit-form');
    $action = $status == SAVED_UPDATED ? 'updated' : 'added';

    // Tell the user we've updated their custom language.
    drupal_set_message($this->t('The language %label has been %action.', ['%label' => $entity->label(), '%action' => $action]));
    $this->logger('languagefield')->notice('The language %label has been %action.', ['%label' => $entity->label(), '%action' => $action]);

    // Redirect back to the list view.
    $form_state->setRedirect('languagefield.custom_language.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = ($this->entity->isNew()) ? $this->t('Add custom language') : $this->t('Save language');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure sane field values for langcode and names.
    if (!isset($form['langcode_view']) && !preg_match('@^[a-zA-Z]{1,8}(-[a-zA-Z0-9]{1,8})*$@', $form_state->getValue('langcode'))) {
      $form_state->setErrorByName('langcode', $this->t('%field must be a valid language tag as <a href=":url">defined by the W3C</a>.', [
        '%field' => $form['langcode']['#title'],
        ':url' => 'http://www.w3.org/International/articles/language-tags/',
      ]));
    }
    foreach (['label', 'native_name'] as $field) {
      if ($form_state->getValue($field) != Html::escape($form_state->getValue($field))) {
        $form_state->setErrorByName($field, $this->t('%field cannot contain any markup.', ['%field' => $form[$field]['#title']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $langcode = trim($form_state->getValue('langcode'));
    $label = trim($form_state->getValue('label'));
    $direction = trim($form_state->getValue('direction'));
    $native = trim($form_state->getValue('native_name'));

    $entity->set('id', $langcode);
    $entity->set('label', $label);
    $entity->set('native_name', $native);
    $entity->set('direction', $direction);

    // There is no weight on the edit form. Fetch all configurable languages
    // ordered by weight and set the new language to be placed after them.
    //$languages = \Drupal::languageManager()->getLanguages(ConfigurableLanguage::STATE_CONFIGURABLE);
    //$last_language = end($languages);
    //$entity->setWeight($last_language->getWeight() + 1);
  }

}
