<?php

namespace Drupal\recurring_period\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the recurring period plugin annotation object.
 *
 * Plugin namespace: Plugin/RecurringPeriod.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class RecurringPeriod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
