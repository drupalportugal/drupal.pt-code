<?php

namespace Drupal\Tests\recurring_period\Kernel;

use Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface;

/**
 * Tests the fixed interval plugin.
 *
 * @group recurring_period
 */
class FixedReferenceDateIntervalTest extends RecurringPeriodTestBase {

  /**
   * Tests the fixed interval plugin.
   *
   * @dataProvider testFixedIntervalPluginProvider
   */
  public function testFixedIntervalPlugin($timezone_name, $reference_date, $interval, $start_date, $expected) {
    $timezone = new \DateTimeZone($timezone_name);

    /** @var RecurringPeriodInterface $plugin */
    $plugin = $this->recurringPeriodManager->createInstance('fixed_reference_date_interval', [
      'reference_date' => $reference_date,
      'interval' => $interval,
    ]);

    $start_date = new \DateTimeImmutable($start_date, $timezone);
    $expected_end_date = new \DateTimeImmutable($expected, $timezone);

    $this->assertEquals($expected_end_date, $plugin->calculateDate($start_date));
  }

  /**
   * Data provider for testSteppedByItem().
   */
  public function testFixedIntervalPluginProvider() {
    return [
      'annual recurring, due later this year' => [
        // Timezone.
        'Europe/London',
        // Reference date.
        '1970-12-25',
        // Interval.
        [
          'period' => 'year',
          'interval' => 1,
        ],
        // Start date.
        '2017-07-01T00:00:00',
        // Expected end date.
        '2017-12-25T00:00:00',
      ],
      'annual recurring, due next year' => [
        'Europe/London',
        '1970-01-01',
        [
          'period' => 'year',
          'interval' => 1,
        ],
        '2017-07-01T00:00:00',
        '2018-01-01T00:00:00',
      ],
      'annual recurring, entire year left' => [
        'Europe/London',
        '2025-01-01',
        [
          'period' => 'year',
          'interval' => 1,
        ],
        '2017-01-01T00:00:00',
        '2018-01-01T00:00:00',
      ],
    ];
  }

}
