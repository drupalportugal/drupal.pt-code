<?php

/**
 * @file
 * Contains \Drupal\interval\Plugin\Field\FieldWidget\IntervalWidget.
 */

namespace Drupal\interval\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\interval\IntervalPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interval widget.
 *
 * @FieldWidget(
 *   id = "interval_default",
 *   label = @Translation("Interval and Period"),
 *   field_types = {
 *     "interval"
 *   }
 * )
 */
class IntervalWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The interval plugin manager service.
   *
   * @var \Drupal\interval\IntervalPluginManagerInterface
   */
  protected $intervalManager;

  /**
   * Constructs a WidgetBase object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\interval\IntervalPluginManagerInterface $interval_manager
   *   The interval plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, IntervalPluginManagerInterface $interval_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->intervalManager = $interval_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.interval.intervals')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'allowed_periods' => [],
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\interval\Plugin\Field\FieldType\IntervalItem $item */
    $item = $items->get($delta);

    $element += array(
      '#type' => 'interval',
      '#periods' => array_keys(array_filter($this->getSetting('allowed_periods'))),
      '#default_value' => array(
        'interval' => $item->getInterval(),
        'period' => $item->getPeriod(),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $intervals = $this->intervalManager->getDefinitions();
    foreach ($intervals as $key => $detail) {
      $options[$key] = $detail['plural'];
    }

    $form['allowed_periods'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Allowed periods'),
      '#options' => $options,
      '#description' => t('Select the periods you wish to be available in the dropdown. Selecting none will make all of them available.'),
      '#default_value' => $this->getSetting('allowed_periods'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Allowed periods: @periods', array('@periods' => implode(', ', array_filter($this->getSetting('allowed_periods')))));

    return $summary;
  }

}
