<?php

/**
 * @file
 * Contains \Drupal\interval\Plugin\Field\FieldFormatter\IntervalFormatterPhp.
 */

namespace Drupal\interval\Plugin\Field\FieldFormatter;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a default formatter class for interval fields.
 *
 * @FieldFormatter(
 *   id = "interval_php",
 *   module = "interval",
 *   label = @Translation("PHP date/time"),
 *   field_types = {
 *     "interval"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class IntervalFormatterPhp extends IntervalFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\interval\IntervalItemInterface $item */
    $element = array();
    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#markup' => SafeMarkup::checkPlain($item->buildPHPString()),
      );
    }
    return $element;
  }

}
