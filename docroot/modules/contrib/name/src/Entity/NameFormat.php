<?php

/**
 * @file
 * Contains \Drupal\name\Entity\NameFormat.
 */

namespace Drupal\name\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\name\NameFormatInterface;

/**
 * Defines the Name Format configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "name_format",
 *   label = @Translation("Name format"),
 *   handlers = {
 *     "access" = "Drupal\name\NameFormatAccessController",
 *     "list_builder" = "Drupal\name\NameFormatListBuilder",
 *     "form" = {
 *       "add" = "Drupal\name\Form\NameFormatAddForm",
 *       "edit" = "Drupal\name\Form\NameFormatEditForm",
 *       "delete" = "Drupal\name\Form\NameFormatDeleteForm"
 *     },
 *     "list_builder" = "Drupal\name\NameFormatListBuilder"
 *   },
 *   config_prefix = "name_format",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/regional/name/manage/{name_format}",
 *     "delete-form" = "/admin/config/regional/name/manage/{name_format}/delete"
 *   }
 * )
 */
class NameFormat extends ConfigEntityBase implements NameFormatInterface {

  /**
   * The name format machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The name format UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the name format entity.
   *
   * @var string
   */
  public $label;

  /**
   * The name format pattern.
   *
   * @var array
   */
  public $pattern;

  /**
   * The locked status of this name format.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/config/regional/name/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->getEntityType(),
        'entity' => $this,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPattern($type = NULL) {
    return isset($this->pattern[$type]) ? $this->pattern[$type] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern, $type = NULL) {
    $this->pattern[$type] = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

}
