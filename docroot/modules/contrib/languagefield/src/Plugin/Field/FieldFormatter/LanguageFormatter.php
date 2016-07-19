<?php

namespace Drupal\languagefield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\languagefield\Plugin\Field\FieldType\LanguageItem;

/**
 * Plugin implementation of the 'language_field' formatter.
 *
 * @FieldFormatter(
 *   id = "languagefield_default",
 *   label = @Translation("Language"),
 *   field_types = {
 *     "language_field",
 *   }
 * )
 */
class LanguageFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format'] = array('name' => 'name',);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['format'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Display'),
      '#description' => t('Select the elements you want to show. The elements will be concatenated when showing the field.'),
      '#default_value' => $this->getSetting('format'),
      '#options' => LanguageItem::_settingsOptions('formatter'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings()['format'];
    $options = LanguageItem::_settingsOptions('formatter');

    if (empty($settings)) {
      $summary[] = t('** Not set **');
    }
    else {
      foreach ($settings as $key => $value) {
        switch ($value) {
          case '0':
            // Option is not selected.
            break;
          default:
            $summary[] = isset($options[$value]) ? $options[$value] : '...';
            break;
        }
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    // Todo: implement prepareView. (if necessary: replaces D7's hook_field_prepare_view())
/*
    D7: $languages = _languagefield_options($field['settings']['language_range'], $langcode);
    D8: $languages = LanguageItem::getPossibleValues(...);
    foreach ($entities as $id => $object) {
      foreach ($items[$id] as $delta => $item) {
        // Check if item value is set, otherwise continue to next item.
        if (!empty($item['value'])) {
          $items[$id][$delta] = $languages[$item['value']];
        }
      }
    }
 */
    return parent::prepareView($entities_items);
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $settings = $this->getSettings();

    $langcode = $item->value;

    // Do NOT use the languagemanager, since it only uses installed languages.
    // $language_manager = \Drupal::languageManager();
    // $language = $language_manager->getLanguage($langcode); // Does not work for e.g. Danish (da).
    $language = new Language(array('id' => $langcode));
    $language_translated_name = $this->t($language->getName());
    // Create the markup for this value.
    $markup = array();

    if (!empty($settings['format']['iso'])) {
      $markup[] = $langcode;
    }
    if (!empty($settings['format']['name'])) {
      // @todo: Use language of user of of content entity?
      $markup[] = $language_translated_name;
    }
    if (!empty($settings['format']['name_native'])) {
      // @todo: Create feature request to add function to D8 core.
      $markup[] = (empty($settings['format']['name'])) ? $item->getNativeName() : '(' . $item->getNativeName() . ')';
    }

    $markup = (empty($markup)) ? $language_translated_name : implode(' ', $markup);

    $result = [
//      '#type' => 'inline_template',
//      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => $item->value],
      '#type' => 'processed_text',
      '#format' => $item->format,
//      '#langcode' => $langcode,
    ];

    // Add variables for languageicons theme function.
    if (!empty($settings['format']['icon']) && \Drupal::moduleHandler()->moduleExists('languageicons')) {
      $result += array(
        'language' => $language,
        'title' => $markup,
      );
      languageicons_link_add($result, $language_translated_name);
      unset($result['language']);
      unset($result['html']);
    }
    else {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.
      $result += array(
        '#text' => $markup,
      );
    }

    return $result;
  }

}
