<?php

/**
 * @file
 * Contains \Drupal\diff\DiffBuilderManager.
 */

namespace Drupal\diff;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for field diff builders.
 *
 * @ingroup field_diff_builder
 */
class DiffBuilderManager extends DefaultPluginManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FieldDiffBuilderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity Manager service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/Diff', $namespaces, $module_handler, '\Drupal\diff\FieldDiffBuilderInterface', 'Drupal\diff\Annotation\FieldDiffBuilder');

    $this->setCacheBackend($cache_backend, 'field_diff_builder_plugins');
    $this->alterInfo('field_diff_builder_info');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Define whether a field should be displayed or not as a diff change.
   *
   * To define if a field should be displayed in the diff comparison, check if
   * it is revisionable and is not the bundle or revision field of the entity.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if the field will be displayed.
   */
  public function showDiff(FieldStorageDefinitionInterface $field_storage_definition) {
    $show_diff = FALSE;
    // Check if the field is revisionable.
    if ($field_storage_definition->isRevisionable()) {
      $show_diff = TRUE;
      // Do not display the field, if it is the bundle or revision field of the
      // entity.
      $entity_type = $this->entityTypeManager->getDefinition($field_storage_definition->getTargetEntityTypeId());
      if (in_array($field_storage_definition->getName(), [$entity_type->getKey('bundle'), $entity_type->getKey('revision')])) {
        $show_diff = FALSE;
      }
    }
    return $show_diff;
  }
}
