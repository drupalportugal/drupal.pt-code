<?php

/**
 * @file
 * Contains \Drupal\name\Plugin\views\filter\Fulltext.
 */

namespace Drupal\name\Plugin\views\filter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by fulltext search.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("name_fulltext")
 */
class Fulltext extends FilterPluginBase {

  /**
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  protected function operators() {
    return array(
      'contains' => array(
        'title' => t('Contains'),
        'short' => t('contains'),
        'method' => 'op_contains',
        'values' => 1,
      ),
      'word' => array(
        'title' => t('Contains any word'),
        'short' => t('has word'),
        'method' => 'op_word',
        'values' => 1,
      ),
      'allwords' => array(
        'title' => t('Contains all words'),
        'short' => t('has all'),
        'method' => 'op_word',
        'values' => 1,
      ),
    );
  }


  /**
   * Build strings from the operators() for 'select' options.
   */
  public function operatorOptions($which = 'title') {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Provide a simple textfield for equality.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $this->value,
      '#attributes' => array('title' => t('Enter the name you wish to search for.')),
      '#title' => $this->isExposed() ? '' : t('Value'),
    );
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $fulltext_field = "LOWER(CONCAT(' ', COALESCE({$field}_title, ''), ' ', COALESCE({$field}_given, ''), ' ', COALESCE({$field}_middle, ''), ' ', COALESCE({$field}_family, ''), ' ', COALESCE({$field}_generational, ''), ' ', COALESCE({$field}_credentials, '')))";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($fulltext_field);
    }
  }

  function op_contains($fulltext_field) {
    $value = Unicode::strtolower($this->value[0]);
    $value = str_replace(' ', '%', $value);
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$fulltext_field LIKE $placeholder", array($placeholder => '% ' . $value . '%'));
  }

  function op_word($fulltext_field) {
    $where = $this->operator == 'word' ? db_or() : db_and();
    // Don't filter on empty strings.
    if (empty($this->value[0])) {
      return;
    }

    $value = Unicode::strtolower($this->value[0]);

    $words = preg_split('/ /', $value, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words as $word) {
      $placeholder = $this->placeholder();
      $where->where("$fulltext_field LIKE $placeholder", array($placeholder => '% ' . db_like($word) . '%'));
    }

    $this->query->addWhere($this->options['group'], $where);
  }
}
