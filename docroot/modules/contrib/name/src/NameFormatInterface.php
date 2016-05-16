<?php

/**
 * @file
 * Contains \Drupal\name\NameFormatInterface.
 */

namespace Drupal\name;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a name format.
 */
interface NameFormatInterface extends ConfigEntityInterface {

  public function getPattern($type);

  public function setPattern($pattern, $type);

  /**
   * Determines if this name format is locked.
   *
   * @return bool
   *   TRUE if the name format is locked, FALSE otherwise.
   */
  public function isLocked();
}
