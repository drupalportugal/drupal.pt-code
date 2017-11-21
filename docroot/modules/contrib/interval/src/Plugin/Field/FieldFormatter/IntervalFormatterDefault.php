<?php

/**
 * @file
 * Contains \Drupal\interval\Plugin\Field\FieldFormatter\IntervalFormatterDefault.
 */

namespace Drupal\interval\Plugin\Field\FieldFormatter;

/**
 * Provides a default formatter class for interval fields.
 *
 * @FieldFormatter(
 *   id = "interval_default",
 *   module = "interval",
 *   label = @Translation("Plain"),
 *   field_types = {
 *     "interval"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class IntervalFormatterDefault extends IntervalFormatterBase {}
