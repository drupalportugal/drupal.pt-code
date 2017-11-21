<?php

namespace Drupal\Tests\recurring_period\Kernel;

use Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface;

/**
 * Tests the rolling interval plugin.
 *
 * @group recurring_period
 */
class RollingIntervalTest extends RecurringPeriodTestBase {

  /**
   * Tests a 2 week interval in UTC.
   */
  public function test2WeekInterval() {
    $timezone_utc = new \DateTimeZone('UTC');

    /** @var RecurringPeriodInterface $plugin */
    $plugin = $this->recurringPeriodManager->createInstance('rolling_interval', [
      'interval' => [
        'period' => 'week',
        'interval' => 2,
      ],
    ]);

    $start_date = new \DateTimeImmutable('2017-01-01T09:00:00', $timezone_utc);
    $expected_end_date = new \DateTimeImmutable('2017-01-15T09:00:00', $timezone_utc);
    $actual_end_date = $plugin->calculateDate($start_date);
    $this->assertEquals($expected_end_date, $actual_end_date);

    // The timestamp difference should be 14*86400 seconds.
    $expected_timestamp_diff = 14 * 86400;
    $actual_timestamp_diff = (int) $actual_end_date->format('U') - (int)$start_date->format('U');
    $this->assertEquals($expected_timestamp_diff, $actual_timestamp_diff);
  }

  /**
   * Tests a 2 week interval spanning a daylight saving change.
   */
  public function test2WeekIntervalSpanningDSTChange() {
    $timezone_london = new \DateTimeZone('Europe/London');

    /** @var RecurringPeriodInterface $plugin */
    $plugin = $this->recurringPeriodManager->createInstance('rolling_interval', [
      'interval' => [
        'period' => 'week',
        'interval' => 2,
      ],
    ]);

    $start_date = new \DateTimeImmutable('2017-10-17T09:00:00', $timezone_london);
    $expected_end_date = new \DateTimeImmutable('2017-10-31T09:00:00', $timezone_london);
    $actual_end_date = $plugin->calculateDate($start_date);
    $this->assertEquals($expected_end_date, $actual_end_date);

    // The timestamp difference should take into account the extra hour
    // because of the the switch from DST.
    $expected_timestamp_diff = 14 * 86400 + 3600;
    $actual_timestamp_diff = (int) $actual_end_date->format('U') - (int)$start_date->format('U');
    $this->assertEquals($expected_timestamp_diff, $actual_timestamp_diff);
  }

}
