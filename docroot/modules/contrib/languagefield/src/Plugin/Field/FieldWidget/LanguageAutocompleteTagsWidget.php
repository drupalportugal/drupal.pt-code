<?php

namespace Drupal\languagefield\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'languagefield_autocomplete_tags' widget.
 *
 * @FieldWidget(
 *   id = "languagefield_autocomplete_tags",
 *   label = @Translation("Language autocomplete (Tags style)"),
 *   field_types = {
 *     "language_field",
 *   },
 *   multiple_values = TRUE
 * )
 */
class LanguageAutocompleteTagsWidget extends LanguageAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $values = [];
    $languages = $items[0]->getSettableOptions();
    foreach ($items as $item) {
      $values[] = $languages[$item->value];
    }

    $element['value']['#tags'] = TRUE;
    $element['value']['#default_value'] = count($values) ? Tags::implode($values) : '';

    return $element;
  }

  /**
   * Form element validate handler for language autocomplete tags element.
   */
  public static function validateElement($element, FormStateInterface $form_state) {
    if ($value = $element['#value']) {
      // Create array of ISO-2 codes from the submitted string of languages.
      $values = [];
      $languages = $element['#languagefield_options'];
      $input_values = Tags::explode($element['#value']);
      foreach ($input_values as $value) {
        $langcode = array_search($value, $languages);
        if(!empty($langcode)) {
          $values[] = ['value' => $langcode];
        }
      }
      // Make sure all the submitted languages have valid ISO-2 codes.
      if (count($values) === count($input_values)) {
        $form_state->setValueForElement($element, $values);
      }
      else {
        $form_state->setError($element, t('An unexpected language is entered.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['value'];
  }

}
