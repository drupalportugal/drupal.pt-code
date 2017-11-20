<?php

namespace Drupal\languagefield\Plugin\views\filter;

use Drupal\views\FieldAPIHandlerTrait;

/**
 * Provides filtering by language.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("languagefield")
 */
class LanguageFilter extends \Drupal\views\Plugin\views\filter\LanguageFilter {

  use FieldAPIHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Language');
      // // Pass the current values so options that are already selected do not get
      // // lost when there are changes in the language configuration.
      // $this->valueOptions = $this->listLanguages(LanguageInterface::STATE_ALL | LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED, array_keys($this->value));
      $field_storage = $this->getFieldStorageDefinition();
      $this->valueOptions = options_allowed_values($field_storage);
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Don't filter by language in case the site is not multilingual, because
    // there is no point in doing so.
    if (!$this->languageManager->isMultilingual()) {
      return;
    }

    parent::query();
  }

}
