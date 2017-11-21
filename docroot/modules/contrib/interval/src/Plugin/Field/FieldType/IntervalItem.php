<?php

/**
 * @file
 * Contains \Drupal\interval\Plugin\Field\FieldType\IntervalItem.
 */

namespace Drupal\interval\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\interval\IntervalInterface;
use Drupal\interval\IntervalItemInterface;
use Drupal\interval\InvalidIntervalException;

/**
 * Provides a data type plugin for an interval item.
 *
 * @FieldType(
 *   id = "interval",
 *   label = @Translation("Interval"),
 *   description = @Translation("Provides an interval field allowing you to enter a number and select a period."),
 *   default_widget = "interval_default",
 *   default_formatter = "interval_default"
 * )
 */
class IntervalItem extends FieldItemBase implements IntervalItemInterface {

  /**
   * The interval plugin configuration for the selected period.
   *
   * @var array
   */
  protected $intervalPlugin = array();

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['interval'] = DataDefinition::create('integer')
      ->setLabel(t('Interval'));
    $properties['period'] = DataDefinition::create('string')
      ->setLabel(t('Period'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'interval' => array(
        'description'   => 'The number of multiples of the period',
        'type'          => 'int',
        'size'          => 'medium',
        'not null'      => TRUE,
        'default'       => 0,
      ),
      'period' => array(
        'description'   => 'The period machine name',
        'type'          => 'varchar',
        'size'          => 'normal',
        'length'        => 20,
        'not null'      => TRUE,
        'default'       => 'day',
      ),
    );
    $indexes = array(
      'period' => array('period'),
      'interval' => array('interval'),
    );
    return array(
      'columns' => $columns,
      'indexes' => $indexes,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInterval() {
    return (int) $this->get('interval')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriod() {
    return $this->get('period')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->getValue()['interval']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervalPlugin() {
    if (!$this->intervalPlugin) {
      $this->intervalPlugin = \Drupal::service('plugin.manager.interval.intervals')->getDefinition($this->getPeriod());
    }
    return $this->intervalPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function applyInterval(\DateTime $date, $limit = FALSE) {
    try {
      $old_date = clone $date;
      $datetime = $this->buildPHPString();
      $date->modify($datetime);
      $configuration = $this->getIntervalPlugin();

      if ($limit && $configuration['php'] == 'months') {
        $date_interval = $date->diff($old_date);
        if ($date_interval->d <> 0) {
          $date->modify("last day of last month");
        }
      }
    }
    catch (\Exception $e) {
      throw new InvalidIntervalException($e->getMessage(), 0, $e, $date, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPHPString() {
    $interval = $this->getIntervalPlugin();
    $value = $this->getInterval() * $interval['multiplier'];
    return $value . ' ' . $interval['php'];
  }
}
