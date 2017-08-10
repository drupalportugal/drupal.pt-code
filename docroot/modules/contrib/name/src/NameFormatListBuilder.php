<?php

/**
 * @file
 * Drupal \Drupal\name\NameFormatListBuilder.php
 */

namespace Drupal\name;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class NameFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['label'] = t('Label');
    $row['id'] = t('Machine name');
    $row['format'] = t('Format');
    $row['examples'] = t('Examples');
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['format'] = $entity->get('pattern');
    $row['examples'] = array(
      'data' => array(
        '#markup' => implode('<br/>', $this->examples($entity))
      )
    );
    $operations = $this->buildOperations($entity);
    $row['operations']['data'] = $operations;
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function examples(EntityInterface $entity) {
    $examples = array();
    foreach ($this->nameExamples() as $index => $example_name) {
      $formatted = SafeMarkup::checkPlain(NameFormatParser::parse($example_name, $entity->get('pattern')));
      if (empty($formatted)) {
        $formatted = '<em>&lt;&lt;empty&gt;&gt;</em>';
      }
      $examples[] = $formatted . " <sup>{$index}</sup>";
    }
    return $examples;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render['list'] = parent::render();
    $render['help'] = $this->nameFormatHelp();
    return $render;
  }

  /**
   * Help box.
   *
   * @return array
   */
  public function nameFormatHelp() {
    module_load_include('inc', 'name', 'name.admin');
    return _name_get_name_format_help_form();
  }

  /**
   * Example names.
   *
   * @return null
   */
  public function nameExamples() {
    module_load_include('inc', 'name', 'name.admin');
    return name_example_names();
  }

}
