<?php

namespace Drupal\languagefield\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\language\ConfigurableLanguageInterface;

/**
 * Provides an interface defining a contrib language entity.
 */
interface CustomLanguageInterface extends ConfigEntityInterface, ConfigurableLanguageInterface {

  /**
   * @return string
   */
  public function getNativeName();

}
