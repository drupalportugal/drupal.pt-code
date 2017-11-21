<?php

/**
 * @file
 * Contains \Drupal\interval\InvalidIntervalException.
 */

namespace Drupal\interval;

/**
 * Defines an exception for handling invalid intervals.
 */
class InvalidIntervalException extends \InvalidArgumentException {

  /**
   * The date time that caused the exception.
   *
   * @var \DateTime
   */
  protected $date;

  /**
   * The item that caused the exception.
   *
   * @var \Drupal\interval\IntervalItemInterface
   */
  protected $item;

  /**
   * Constructs a new \Drupal\interval\InvalidIntervalException.
   *
   * @param string $message
   *   Error message.
   * @param int $code
   *   Error code.
   * @param \Exception $previous
   *   The previous exception
   * @param \DateTime $date
   *   The date that caused the exception.
   * @param \Drupal\interval\IntervalItemInterface $item
   *   The item that caused the exception.
   */
  public function __construct($message = "", $code = 0, \Exception $previous = NULL, \DateTime $date = NULL, IntervalItemInterface $item = NULL) {
    $this->item = $item;
    $this->date = $date;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Get the date that caused the exception.
   *
   * @return \DateTime
   *   The date time that caused the exception.
   */
  public function getDateTime() {
    return $this->date;
  }

  /**
   * Get the item that caused the exception.
   *
   * @return \Drupal\interval\IntervalItemInterface
   *   The item that caused the exception.
   */
  public function getItem() {
    return $this->item;
  }

}
