<?php

namespace Drupal\Tests\recurring_period\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\recurring_period\RecurringPeriodManager;

/**
 * Base class for kernel tests.
 *
 * @group recurring_period
 */
abstract class RecurringPeriodTestBase extends KernelTestBase {

  /**
   * The modules to enable.
   */
  public static $modules = [
    'interval',
    'recurring_period',
  ];

  /**
   * The recurring period plugin manager.
   *
   * @var \Drupal\recurring_period\RecurringPeriodManager
   */
  protected $recurringPeriodManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->recurringPeriodManager = $this->container->get('plugin.manager.recurring_period');
  }

}
