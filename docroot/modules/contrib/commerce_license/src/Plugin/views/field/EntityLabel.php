<?php

namespace Drupal\commerce_license\Plugin\views\field;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\EntityLabel as CoreEntityLabel;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to display entity label optionally linked to entity page.
 *
 * @todo Remove this handler once https://www.drupal.org/node/2080745 is done.
 *
 * @ViewsField("commerce_license__entity_label")
 */
class EntityLabel extends CoreEntityLabel {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    FieldPluginBase::init($view, $display, $options);

    if (isset($this->definition['entity type field'])) {
      $this->additional_fields[$this->definition['entity type field']] = $this->definition['entity type field'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $entity_ids_per_type = [];
    foreach ($values as $value) {
      $type = $this->getEntityTypeFromValues($value);
      $entity_ids_per_type[$type][] = $this->getValue($value);
    }

    foreach ($entity_ids_per_type as $type => $ids) {
      $this->loadedReferencers[$type] = $this->entityManager->getStorage($type)->loadMultiple($ids);
    }
  }

  /**
   * Returns the entity type to use for a given result row.
   *
   * @param \Drupal\views\ResultRow $row
   *   A result row of values retrieved from the database.
   *
   * @return string
   */
  protected function getEntityTypeFromValues(ResultRow $row) {
    // Support a dynamically defined entity type.
    if (!empty($this->definition['entity type field'])) {
      $entity_type_id = $this->getValue($row, $this->definition['entity type field']);
      return $entity_type_id;
    }
    else {
      // Fall back to the entity type of the table.
      return parent::getEntityType();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity_type_id = $this->getEntityTypeFromValues($values);

    $value = $this->getValue($values);

    if (empty($this->loadedReferencers[$entity_type_id][$value])) {
      return;
    }

    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = $this->loadedReferencers[$entity_type_id][$value];

    if (!empty($this->options['link_to_entity'])) {
      try {
        $this->options['alter']['url'] = $entity->toUrl();
        $this->options['alter']['make_link'] = TRUE;
      }
      catch (UndefinedLinkTemplateException $e) {
        $this->options['alter']['make_link'] = FALSE;
      }
      catch (EntityMalformedException $e) {
        $this->options['alter']['make_link'] = FALSE;
      }
    }

    return $this->sanitizeValue($entity->label());
  }

}
