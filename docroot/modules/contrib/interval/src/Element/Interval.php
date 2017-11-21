<?php

/**
 * @file
 * Contains \Drupal\interval\Element\Interval.
 */

namespace Drupal\interval\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for date/time intervals.
 *
 * @FormElement("interval")
 */
class Interval extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [[get_class($this), 'process']],
      '#theme' => 'interval',
      '#theme_wrappers' => array('form_element'),
    ];
  }

  /**
   * Process callback.
   */
  public static function process(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = !empty($element['#default_value']) ? $element['#default_value'] : ['interval' => NULL, 'period' => NULL];

    $element['interval'] = [
      '#type' => 'number',
      '#default_value' => $value['interval'],
      '#required' => $element['#required'],
    ];

    $intervals = \Drupal::service('plugin.manager.interval.intervals')->getDefinitions();
    $periods = !empty($element['#periods']) ? $element['#periods'] : array_keys($intervals);
    $period_options = array();
    foreach ($intervals as $key => $detail) {
      if (in_array($key, $periods) && isset($detail['plural'])) {
        $period_options[$key] = $detail['plural'];
      }
    }
    $element['period'] = [
      '#type' => 'select',
      '#options' => $period_options,
      '#default_value' => $value['period'],
      '#required' => $element['#required'],
    ];

    return $element;
  }
}
