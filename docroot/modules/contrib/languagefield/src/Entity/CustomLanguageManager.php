<?php

namespace Drupal\languagefield\Entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Defines the CustomLanguage entity.
 *
 * The CustomLanguage entity stores information about custom languages added to be used by the language field.
 */
class CustomLanguageManager {

  const LANGUAGEFIELD_LANGCODE_MAXLENGTH = 12;

  // Define own variants. Keep away from the LanguageInterface constants.
  // LanguageInterface::STATE_CONFIGURABLE = 1; -> 'en', 'de'
  // LanguageInterface::STATE_LOCKED = 2; -> 'und', 'zxx'
  // LanguageInterface::STATE_ALL = 3; -> 'en', 'de', 'und', 'zxx'
  // LanguageInterface::STATE_SITE_DEFAULT = 4; -> 'en'
  const LANGUAGEFIELD_LANGUAGES_PREDEFINED = 11; // all predefined + custom languages.
  const LANGUAGEFIELD_LANGUAGES_CUSTOM = 12; // all custom languages from languagefield.
  // const LANGUAGEFIELD_LANGUAGES_ENABLED = 13; // disabled languages are no more in D8.

  /**
   * The list of Custom languages.
   */
  protected static $customLanguages;

  /**
   * The unique manager.
   */
  protected static $customLanguageManager;

  /**
   * Gets the unique manager.
   *
   * @return \Drupal\languagefield\Entity\CustomLanguageManager
   */
  public static function getCustomLanguageManager() {
    if (static::$customLanguageManager == NULL) {
        static::$customLanguageManager = new CustomLanguageManager();
    }
    return static::$customLanguageManager;
  }

  /**
   * Gets the list of Custom languages as an array,
   * resembling getStandardLanguageList.
   *
   * @return array
   */
  public static function getCustomLanguageList() {
    $result = [];

    $languages = CustomLanguageManager::getCustomLanguages();
    foreach ($languages as $language) {
      $result[$language->id()] = [$language->label(), $language->getNativeName()];
    }
    return $result;
  }

  /**
   * Gets the list of Custom languages as an array,
   * resembling getStandardLanguageList.
   *
   * @return \Drupal\languagefield\Entity\CustomLanguageInterface[]
   */
  public static function getCustomLanguages() {
    if (static::$customLanguages == NULL) {
      $storage = \Drupal::entityTypeManager()->getStorage('custom_language');
      static::$customLanguages = $storage->loadMultiple(); // JVO
    }
    return static::$customLanguages;
  }

  /**
   * Creates a configurable language object from a langcode.
   *
   * Copy from $language = \Drupal::languageManager()->getLanguage($langcode);
   * Do NOT use the languagemanager, since it only uses installed, not custom languages.
   *
   * @param string $langcode
   *   The language code to use to create the object.
   *
   * @return $this
   *
   * @see \Drupal\Core\Language\LanguageManager::getStandardLanguageList()
   */
  public static function createFromLangcode($langcode) {
    $custom_languages = CustomLanguageManager::getCustomLanguageList();
    if (!isset($custom_languages[$langcode])) {
      // Check if $langcode refers to a standard language.
      return ConfigurableLanguage::createFromLangcode($langcode);
    }
    else {
      // A known predefined language, details will be filled in properly.
      return CustomLanguage::create([
        'id' => $langcode,
        'label' => $custom_languages[$langcode][0],
        'direction' => isset($custom_languages[$langcode][2]) ? $custom_languages[$langcode][2] : ConfigurableLanguage::DIRECTION_LTR,
      ]);
    }
  }

  /**
   * Gets a list of allowed values.
   *
   * @param array $settings
   * @return array|\Drupal\Core\Language\LanguageInterface[]
   */
  public static function allowed_values(array $settings) {
    $languages = [];
    $subsets = $settings['language_range'];

    foreach ($subsets as $subset => $active) {
      $subsettable_languages = [];
      if (!$active) {
        continue;
      }

      switch ($subset) {
        case LanguageInterface::STATE_CONFIGURABLE:
        case LanguageInterface::STATE_LOCKED:
        case LanguageInterface::STATE_ALL:
          $subsettable_languages = \Drupal::languageManager()->getLanguages($subset);
          // Convert to $langcode => $name array.
          foreach ($subsettable_languages as $langcode => $language) {
            $subsettable_languages[$langcode] = $language->isLocked() ? t('- @name -', ['@name' => $language->getName()]) : $language->getName();
          }
          break;

        // The following values are copied from LanguageConfiguration::getDefaultOptions()
        // The following evaluations are copied from function language_get_default_langcode()
        // LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language", array('@language' => \Drupal::languageManager()->getDefaultLanguage()->name)),
        // 'current_interface' => t('Current interface language'),
        // 'authors_default' => t("Author's preferred language"),
        case LanguageInterface::LANGCODE_NOT_SPECIFIED:
          $subsettable_languages = [LanguageInterface::LANGCODE_NOT_SPECIFIED => 'Language neutral'];
          break;
        case CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_CUSTOM:
        case LanguageInterface::LANGCODE_SITE_DEFAULT:
        case 'current_interface':
        case 'authors_default':
          $subsettable_languages = self::_getLanguageConfigurationOptions($subset);
          break;
        case CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_PREDEFINED: // 'All predefined languages'
          $standard_languages = \Drupal::languageManager()->getStandardLanguageList();
          foreach ($standard_languages as $langcode => $language_names) {
            $subsettable_languages[$langcode] = t($language_names[0]);
          }
          asort($subsettable_languages);

          break;
      }
      $languages += $subsettable_languages;
    }

    $included_languages = array_filter($settings['included_languages']);
    if (!empty($included_languages)) {
      $languages = array_intersect_key($languages, $included_languages);
    }
    if (!empty($settings['excluded_languages'])) {
      $languages = array_diff_key($languages, $settings['excluded_languages']);
    }

    if (!empty($settings['groups'])) {
      $grouped_languages = [];
      $found_languages = [];
      $languages += ['other' => t('Other languages')];
      foreach (explode("\n", $settings['groups']) as $line) {
        if (strpos($line, '|') !== FALSE) {
          list($group, $langs) = explode('|', $line, 2);
          $langs = array_filter(array_map('trim', explode(',', $langs)));
          $langs = array_intersect_key($languages, array_combine($langs, $langs));
          $found_languages += $langs;
          $grouped_languages[$group] = $langs;
        }
        else {
          $langs = array_filter(array_map('trim', explode(',', $line)));
          if (!empty($langs)) {
            $langs = array_intersect_key($languages, array_combine($langs, $langs));
            $found_languages += (array) $langs;
            $grouped_languages += (array) $langs;
          }
        }
      }
      $missing_languages = array_diff_key($languages, $found_languages);
      foreach ($grouped_languages as $index => $options) {
        if (is_array($options)) {
          if (isset($options['other'])) {
            unset($options['other']);
            if ($missing_languages) {
              $grouped_languages[$index] = array_merge($grouped_languages[$index], $missing_languages);
              $missing_languages = FALSE;
            }
          }
        }
      }
      if (isset($grouped_languages['other'])) {
        unset($grouped_languages['other']);
        if ($missing_languages) {
          $grouped_languages = array_merge($grouped_languages, $missing_languages);
        }
      }
      return $grouped_languages;
    }

    return $languages;
  }

  /**
   * Helper function to get special languages.
   * The following values are copied from LanguageConfiguration::getDefaultOptions()
   * The following evaluations are copied from function language_get_default_langcode()
   * - LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language", array('@language' => \Drupal::languageManager()->getDefaultLanguage()->name)),
   * - 'current_interface' => t('Current interface language'),
   * - 'authors_default' => t("Author's preferred language"),
   *
   * @param $code
   *   Formatting hint
   * @return array of key-value items
   */
  private static function _getLanguageConfigurationOptions($code) {
    $values = [];

    switch ($code) {
      case LanguageInterface::LANGCODE_SITE_DEFAULT:
        $values = [
          LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language", [
            '@language' => \Drupal::languageManager()
              ->getDefaultLanguage()->getName()
          ])
        ];
        break;

      case 'current_interface':
        $values = ['current_interface' => t('Current interface language')];
        break;

      case 'authors_default':
        $values = ['authors_default' => t("Author's preferred language")];
        break;

      case CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_CUSTOM:
        foreach (CustomLanguageManager::getCustomLanguages() as $key => $labels) {
          $values[$key] = $labels->label();
        }
        break;
    }
    return $values;
  }

}
