<?php

namespace Drupal\age_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'age_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "age_field_formatter",
 *   label = @Translation("Age formatter"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class AgeFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['birthdate'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['birthdate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include birthdate'),
      '#description' => $this->t('Include the birthdate before the age.'),
      '#default_value' => $this->getSetting('birthdate'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $settings = $this->getSetting('birthdate');
    if ($settings == '1') {
      $format = $this->t('date (age)');
    } else {
      $format = $this->t('age only');
    }
    $summary[] = $this->t('Age format: %format', array('%format' => $format));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.

    $from = new DrupalDateTime($item->date);
    $to = new DrupalDateTime();

    $age = $from->diff($to)->y;

    $agelabel = $this->t('Age');

    if ($this->getSetting('birthdate')) { //1
      $age = $item->value." (".$agelabel.": ". $age .")";
    } else {
      $age; // We do not force prefix label to the value.
    }

    return nl2br(Html::escape($age));
  }

}
