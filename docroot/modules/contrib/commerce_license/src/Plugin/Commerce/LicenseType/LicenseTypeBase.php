<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Provides the base license type class.
 */
abstract class LicenseTypeBase extends PluginBase implements LicenseTypeInterface {

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return 'license_default';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // This assumes that for each property defined in defaultConfiguration(),
    // there is a form element of the same name, and saves its value into
    // that configuration property.
    $values = $form_state->getValue($form['#parents']);

    $property_names = array_keys($this->defaultConfiguration());

    foreach ($property_names as $property) {
      $this->configuration[$property] = $values[$property];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValuesOnLicense(LicenseInterface $license) {
    // By default, assume that a key in the configuration array corresponds to
    // a field provided to the license type as an entity trait field in
    // buildFieldDefinitions().
    foreach ($this->configuration as $key => $value) {
      $license->{$key} = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
