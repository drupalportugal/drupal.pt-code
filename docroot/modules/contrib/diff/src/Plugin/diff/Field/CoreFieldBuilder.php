<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\diff\DiffEntityParser;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldDiffBuilder(
 *   id = "core_field_diff_builder",
 *   label = @Translation("Core Field Diff"),
 *   field_types = {"decimal", "integer", "float", "email", "telephone",
 *     "date", "uri", "string", "timestamp", "created",
 *     "string_long", "language", "uuid", "map", "datetime", "boolean"
 *   },
 * )
 */
class CoreFieldBuilder extends FieldDiffBuilderBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a FieldDiffBuilderBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\diff\DiffEntityParser $entityParser
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityManagerInterface $entityManager, DiffEntityParser $entityParser, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config, $entityManager, $entityParser);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('diff.entity_parser'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = array();

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        if (isset($values['value'])) {
          $value = $field_item->view(['label' => 'hidden']);
          $result[$field_key][] = $this->renderer->renderPlain($value);
        }
      }
    }

    return $result;
  }

}
