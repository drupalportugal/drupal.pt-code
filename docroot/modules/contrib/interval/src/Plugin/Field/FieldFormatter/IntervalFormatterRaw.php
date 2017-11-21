<?php

/**
 * @file
 * Contains \Drupal\interval\Plugin\Field\FieldFormatter\IntervalFormatterRaw.
 */

namespace Drupal\interval\Plugin\Field\FieldFormatter;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a default formatter class for interval fields.
 *
 * @FieldFormatter(
 *   id = "interval_raw",
 *   module = "interval",
 *   label = @Translation("Raw value"),
 *   field_types = {
 *     "interval"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class IntervalFormatterRaw extends IntervalFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#markup' => SafeMarkup::checkPlain($this->formatInterval($item)),
      );
    }
    return $element;
  }

}
