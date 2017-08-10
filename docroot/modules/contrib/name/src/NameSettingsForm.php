<?php

/**
 * @file
 * Contains \Drupal\name\NameSettingsForm.
 */

namespace Drupal\name;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure name settings for this site.
 */
class NameSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'name_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['name.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'name', 'name.admin');

    $config = $this->configFactory->get('name.settings');

    $form['name_settings'] = array('#tree' => TRUE);
    $form['name_settings']['default_format'] = array(
      '#type' => 'textfield',
      '#title' => t('Default format'),
      '#default_value' => $config->get('default_format'),
      '#description' => t('See help on drupal.org for more info.'),
      '#required' => TRUE,
    );
    $form['name_settings']['sep1'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator 1 replacement token'),
      '#default_value' => $config->get('sep1'),
    );
    $form['name_settings']['sep2'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator 2 replacement token'),
      '#default_value' => $config->get('sep2'),
    );
    $form['name_settings']['sep3'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator 3 replacement token'),
      '#default_value' => $config->get('sep3'),
    );
    // As the fieldset does not have the #input flag, this is not saved.
    $form['name_format_help'] = _name_get_name_format_help_form();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $default_format = trim($form_state->getValue(['name_settings', 'default_format']));
    if (empty($default_format) && !strlen($default_format)) {
      $form_state->setErrorByName('name_settings][default_format', t('%title field is required.', array('%title' => $form['name_settings']['default_format']['#title'])));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('name.settings')
      ->set('default_format', $form_state->getValue(['name_settings', 'default_format']))
      ->set('sep1', $form_state->getValue(['name_settings', 'sep1']))
      ->set('sep2', $form_state->getValue(['name_settings', 'sep2']))
      ->set('sep3', $form_state->getValue(['name_settings', 'sep3']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
