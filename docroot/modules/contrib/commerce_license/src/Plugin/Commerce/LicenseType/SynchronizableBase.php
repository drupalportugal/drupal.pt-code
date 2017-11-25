<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the base license type class.
 */
abstract class SynchronizableBase extends Base implements LicenseTypeSynchronizableInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}

