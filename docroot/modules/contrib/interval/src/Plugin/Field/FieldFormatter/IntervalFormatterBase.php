<?php

/**
 * @file
 * Contains \Drupal\interval\Plugin\Field\FieldFormatter\IntervalFormatterBase.
 */

namespace Drupal\interval\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\interval\IntervalItemInterface;

/**
 * Provides a base formatter class for interval field formatters.
 */
abstract class IntervalFormatterBase extends FormatterBase {

  /**
   * Formats an interval as a string.
   *
   * @param \Drupal\interval\IntervalItemInterface $item
   *   Interval item to format.
   *
   * @return string
   *   Formatted interval.
   */
  protected function formatInterval(IntervalItemInterface $item) {
    $interval = $item->getIntervalPlugin();
    return $this->formatPlural(
      $item->getInterval(), '1 @singular', '@count @plural',
      array(
        '@singular' => $interval['singular'],
        '@plural' => $interval['plural'],
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#type' => 'html_tag',
        '#attributes' => array('class' => array('interval-value')),
        '#tag' => 'div',
        '#value' => $this->formatInterval($item),
      );
    }
    return $element;
  }

}
