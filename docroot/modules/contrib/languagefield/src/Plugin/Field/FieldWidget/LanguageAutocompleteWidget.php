<?php

namespace Drupal\languagefield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\languagefield\Plugin\Field\FieldType\LanguageItem;


/**
 * Plugin implementation of the 'languagefield_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "languagefield_autocomplete",
 *   label = @Translation("Language autocomplete"),
 *   field_types = {
 *     "language_field",
 *   }
 * )
 */
class LanguageAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => '60',
      'autocomplete_route_name' => 'languagefield.autocomplete',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * Form element validate handler for language autocomplete element.
   */
  public static function validateElement($element, FormStateInterface $form_state) {
    if ($value = $element['#value']) {
      $languages = $element['#languagefield_options'];
      $langcode = array_search($value, $languages);
      if (!empty($langcode)) {
        $form_state->setValueForElement($element, $langcode);
      }
      else {
        $form_state->setError($element, t('An unexpected language is entered.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /* @var $item LanguageItem */
    $item = $items[$delta];
    $value = $item->value;

    // Add Languages to custom values: $this->options and $element[]
    $this->options = $languages = $item->getSettableOptions();

    // Cache available languages for this field for a day.
    $field_name = $this->fieldDefinition->id();
    \Drupal::cache('data')->set('languagefield:languages:' . $field_name, $languages, strtotime('+1 day', time()));

    $element['value'] = $element + array(
        '#type' => 'textfield',
        '#default_value' => (!empty($value) && isset($languages[$value])) ? $languages[$value] : '',
        '#languagefield_options' => $languages,
        '#autocomplete_route_name' => $this->getSetting('autocomplete_route_name'),
        '#autocomplete_route_parameters' => array('field_name' => $field_name),
        '#size' => $this->getSetting('size'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#maxlength' => 255,
        '#element_validate' => array(array(get_class($this), 'validateElement')),
      );

    return $element;
  }

}
