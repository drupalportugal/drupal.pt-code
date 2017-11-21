<?php

namespace Drupal\recurring_period\Plugin\RecurringPeriod;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a period that never ends.
 *
 * @RecurringPeriod(
 *   id = "unlimited",
 *   label = @Translation("Unlimited"),
 *   description = @Translation("No end date"),
 * )
 */
class Unlimited extends RecurringPeriodBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $form['description'] = [
      '#markup' => 'Unlimited.',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDate(\DateTimeImmutable $start) {
    return self::UNLIMITED;
  }

}
