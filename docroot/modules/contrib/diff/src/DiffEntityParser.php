<?php

/**
 * @file
 * Contains \Drupal\diff\DiffEntityParser.
 */

namespace Drupal\diff;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;

class DiffEntityParser {

  /**
   * The diff field builder plugin manager.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Wrapper object for writing/reading simple configuration from diff.settings.yml
   */
  protected $config;

  /**
   * Wrapper object for writing/reading simple configuration from diff.plugins.yml
   */
  protected $pluginsConfig;

  /**
   * Constructs an EntityComparisonBase object.
   *
   * @param DiffBuilderManager $diffBuilderManager
   *   The diff field builder plugin manager.
   * @param ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(DiffBuilderManager $diffBuilderManager, ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('diff.settings');
    $this->pluginsConfig =  $configFactory->get('diff.plugins');
    $this->diffBuilderManager = $diffBuilderManager;
  }

  /**
   * Transforms an entity into an array of strings.
   *
   * Parses an entity's fields and for every field it builds an array of string
   * to be compared. Basically this function transforms an entity into an array
   * of strings.
   *
   * @param ContentEntityInterface $entity
   *   An entity containing fields.
   *
   * @return array
   *   Array of strings resulted by parsing the entity.
   */
  public function parseEntity(ContentEntityInterface $entity) {
    $result = array();
    $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    // Load entity of current language, otherwise fields are always compared by
    // their default language.
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }
    $entity_type_id = $entity->getEntityTypeId();

    // Load the diff plugin definitions.
    $diff_plugin_definitions = $this->diffBuilderManager->getDefinitions();
    $plugins = [];
    foreach ($diff_plugin_definitions as $plugin_definition) {
      if (isset($plugin_definition['field_types'])) {
        // Add the plugin's ID to each field type this plugin supports.
        foreach ($plugin_definition['field_types'] as $id) {
          $plugins[$id][] = $plugin_definition['id'];
        }
      }
    }

    // Loop through entity fields and transform every FieldItemList object
    // into an array of strings according to field type specific settings.
    foreach ($entity as $field_items) {
      $field_name = $field_items->getFieldDefinition()->getName();

      // Define if the current field should be displayed as a diff change.
      $show_diff = $this->diffBuilderManager->showDiff($field_items->getFieldDefinition()->getFieldStorageDefinition());
      if (!$show_diff) {
        continue;
      }

      // Get the field plugin configuration.
      $plugin_config = $this->pluginsConfig->get('fields.' . $entity_type_id . '.' . $field_name);

      $plugin = NULL;
      // If there is no plugin defined, take the first available.
      if (!$plugin_config) {
        // Load the plugins that can be applied to this field.
        $plugin_options = [];
        if (isset($plugins[$field_items->getFieldDefinition()->getType()])) {
          foreach ($plugins[$field_items->getFieldDefinition()->getType()] as $id) {
            $plugin_options[$id] = $diff_plugin_definitions[$id]['label'];
          }
        }
        $default_plugin['type'] = array_keys($plugin_options)[0];
        $plugin = $this->diffBuilderManager->createInstance($default_plugin['type'], []);
      }
      // If there is a plugin defined create an instance of it.
      if ($plugin_config && $plugin_config['type'] != 'hidden') {
        $plugin = $this->diffBuilderManager->createInstance($plugin_config['type'], $plugin_config['settings']);
      }
      if ($plugin) {
        // Configurable field. It is the responsibility of the class extending
        // this class to hide some configurable fields from comparison. This
        // class compares all configurable fields.
        $build = $plugin->build($field_items);
        if (!empty($build)) {
          $result[$field_items->getName()] = $build;
        }
      }
    }

    return $result;
  }
}
