<?php

/**
 * @file
 * Contains \Drupal\interval\IntervalItemInterface.
 */

namespace Drupal\interval;

interface IntervalItemInterface {

  /**
   * Gets the interval value for this item.
   *
   * @return int
   *   The interval for this item
   */
  public function getInterval();

  /**
   * Gets the period value for this item.
   *
   * @return int
   *   The period for this item
   */
  public function getPeriod();

  /**
   * Gets the interval plugin for this item.
   *
   * @return \Drupal\interval\IntervalInterface
   *   The interval plugin.
   */
  public function getIntervalPlugin();

  /**
   * Applies an interval to a date object.
   *
   * @param \DateTime $date
   *   A DateTime object to which the interval needs to be applied
   * @param bool $limit
   *   When calling the interval apply function with months or a month
   *   multiplier, keep the date in the last day of the month if this was
   *   exceeded. Example, with $limit set to TRUE, January 31st +1 month will
   *   result in February 28th.
   *
   * @throws \Drupal\interval\InvalidIntervalException
   */
  public function applyInterval(\DateTime $date, $limit = FALSE);

  /**
   * Builds a php date interval string from the plugin properties.
   *
   * @return string
   *   The interval translated to a php string compatible with
   *   \DateTime::modify.
   */
  public function buildPHPString();

}
