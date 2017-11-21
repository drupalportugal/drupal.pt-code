<?php

namespace Drupal\recurring_period\Plugin\RecurringPeriod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a fixed date period.
 *
 * This period runs until the next occurrence of a date within a given interval,
 * meaning that all periods created with the same configuration will align to
 * the same dates, even if they begin on different dates.
 *
 * For example, this can create periods for tax years, which always end on the
 * same day of the year, and will be shorter than a year if begun after the
 * start date. For this, configure the date to the start of the tax year, such
 * as 1 Jan, and the interval to 1 year. Then this will produce periods as
 * follows:
 *  - 1 Jan 2017 -> 1 Jan 2018 -> 1 Jan 2019 -> ...
 *  - 9 May 2017 -> 1 Jan 2018 -> 1 Jan 2019 -> ...
 *  - 3 Oct 2017 -> 1 Jan 2018 -> 1 Jan 2019 -> ...
 *
 * @RecurringPeriod(
 *   id = "fixed_reference_date_interval",
 *   label = @Translation("Interval based on reference date"),
 *   description = @Translation("Provide a period until the next appropriate date based on a fixed reference date and interval."),
 * )
 */
class FixedReferenceDateInterval extends RecurringPeriodBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'reference_date' => '',
      'interval' => [
        'period' => '',
        'interval' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['reference_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Reference date'),
      '#default_value' => $config['reference_date'],
      '#required' => TRUE,
    ];
    $form['interval'] = [
      '#type' => 'interval',
      '#title' => 'Interval',
      '#default_value' => $config['interval'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $this->configuration['reference_date'] = $values['reference_date'];
    $this->configuration['interval'] = $values['interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDate(\DateTimeImmutable $start) {
    $config = $this->getConfiguration();

    $interval_configuration = $config['interval'];
    // The interval plugin ID is the 'period' value.
    $interval_plugin_id = $interval_configuration['period'];

    // Create a DateInterval that represents the interval.
    // TODO: This can be removed when https://www.drupal.org/node/2900435 lands.
    $interval_plugin_definition = \Drupal::service('plugin.manager.interval.intervals')->getDefinition($interval_plugin_id);
    $value = $interval_configuration['interval'] * $interval_plugin_definition['multiplier'];
    $date_interval = \DateInterval::createFromDateString($value . ' ' . $interval_plugin_definition['php']);

    return $this->findNextAppropriateDate($start, $config['reference_date'], $date_interval);
  }

  /**
   * Finds the next appropriate date after the start date.
   *
   * @param \DateTimeImmutable $start_date
   *   The start date.
   * @param string $reference_date_string
   *   A date string using the PHP date format 'Y-m-d'. The timezone will be
   *   assumed to be that of the $start_date.
   * @param \DateInterval $interval
   *   The interval.
   *
   * @return \DateTimeImmutable
   *   The end date.
   */
  protected function findNextAppropriateDate(\DateTimeImmutable $start_date, $reference_date_string, \DateInterval $interval) {
    $reference_date = new \DateTimeImmutable($reference_date_string, $start_date->getTimezone());

    $is_reference_date_in_future = $reference_date->diff($start_date)->invert;
    if ($is_reference_date_in_future) {
      // The reference date is in the future, so rewind it until it precedes
      // the start date, then increase it by one interval unit to find the
      // next appropriate date.
      while ($reference_date->diff($start_date)->invert == TRUE) {
        $reference_date = $reference_date->sub($interval);
      }
      $reference_date = $reference_date->add($interval);
    }
    else {
      // The reference date is in the past, so fast forward it until the next
      // increment beyond the start date to find the next appropriate date.
      while ($reference_date->diff($start_date)->invert == FALSE) {
        $reference_date = $reference_date->add($interval);
      }
    }

    return $reference_date;
  }

}
