<?php

/**
 * @file
 * Adds widget and formatter for a LanguageItem.
 *
 * For now, LanguageItem is not a subclass of
 * core's 'language' entity field item Core\Field.
 *
 * @see Drupal\language\DefaultLanguageItem.
 */

namespace Drupal\languagefield\Plugin\Field\FieldType;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'language' field type.
 *
 * @FieldType(
 *   id = "language_field",
 *   label = @Translation("Language"),
 *   description = @Translation("This field stores a language as a Field."),
 *   no_ui = FALSE,
 *   default_widget = "languagefield_select",
 *   default_formatter = "languagefield_default",
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {"Length" = {"max" = 12}}
 *     }
 *   }
 * )
 */
class LanguageItem extends FieldItemBase implements OptionsProviderInterface {

  const LANGUAGEFIELD_LANGCODE_MAXLENGTH = 12;

  // Define own variants. Keep away from the LanguageInterface constants.
  // LanguageInterface::STATE_CONFIGURABLE = 1; -> 'en', 'de'
  // LanguageInterface::STATE_LOCKED = 2; -> 'und', 'zxx'
  // LanguageInterface::STATE_ALL = 3; -> 'en', 'de', 'und', 'zxx'
  // LanguageInterface::STATE_SITE_DEFAULT = 4; -> 'en'
  const LANGUAGEFIELD_LANGUAGES_PREDEFINED = 11; // all predefined + installed .
  // const LANGUAGEFIELD_LANGUAGES_ENABLED = 12; // disabled languages are no more in D8.

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $maxlenght = $field_definition->getSetting('maxlength');

    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => $maxlenght,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Language'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $defaultStorageSettings = array(
        'maxlength' => self::LANGUAGEFIELD_LANGCODE_MAXLENGTH,
        'language_range' => array(self::LANGUAGEFIELD_LANGUAGES_PREDEFINED => self::LANGUAGEFIELD_LANGUAGES_PREDEFINED),
        'included_languages' => array(),
        'excluded_languages' => array(),
        'groups' => '',
      ) + parent::defaultStorageSettings();

    return $defaultStorageSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    $settings = $this->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSettings();

    $languages = $this->getPossibleOptions();

    $url = (\Drupal::moduleHandler()->moduleExists('language')) ? \Drupal::l(t('Languages'), Url::fromRoute('entity.configurable_language.collection')) : '' ;
    $element['language_range'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enabled languages'),
      '#description' => t("Installed languages can be maintained on the :url
        page, when Language module is installed. Options marked with '*' are
        typically used as default value in a hidden widget.", array(
        ':url' => $url,
      )),
      '#required' => TRUE,
      '#default_value' => $settings['language_range'],
      // TODO: change l() to Url::fromRoute('book.admin'); https://www.drupal.org/node/2346779
      '#options' => array(
        // The following are from Languagefield.
        self::LANGUAGEFIELD_LANGUAGES_PREDEFINED => t('All predefined languages'),
        // self::LANGUAGEFIELD_LANGUAGES_ENABLED => t('Enabled installed languages (not functioning yet)'),
        // The following are from Drupal\Core\Language\LanguageInterface.
        LanguageInterface::STATE_CONFIGURABLE => t('All configurable languages'), // const STATE_CONFIGURABLE = 1; -> 'en', 'de'
        LanguageInterface::STATE_LOCKED => t('All locked languages'), // const STATE_LOCKED = 2; -> 'und', 'zxx'
        // LanguageInterface::STATE_ALL => t('All installed languages'), // const STATE_ALL = 3; -> 'en', 'de', 'und', 'zxx'
        // LanguageInterface::STATE_SITE_DEFAULT => t("The site's default language"), // const STATE_SITE_DEFAULT = 4; -> 'en'
        // The following are copied from LanguageConfiguration::getDefaultOptions()
        LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language (@language)", array(
            '@language' => \Drupal::languageManager()
              ->getDefaultLanguage()->getName()
          )) . '*',
        'current_interface' => t('Current interface language') . '*',
        'authors_default' => t("Author's preferred language") . '*',
      ),
    ];

    $element['included_languages'] = array(
      '#type' => 'select',
      '#title' => t('Restrict by language'),
      '#default_value' => $settings['included_languages'],
      '#options' => array('' => t('- None -')) + $languages,
      '#description' => t('If no languages are selected, this filter will not be used.'),
      '#multiple' => TRUE,
      '#size' => 10,
    );

    $element['excluded_languages'] = array(
      '#type' => 'select',
      '#title' => t('Excluded languages'),
      '#default_value' => $settings['excluded_languages'],
      '#options' => array('' => t('- None -')) + $languages,
      '#description' => t('This removes individual languages from the list.'),
      '#multiple' => TRUE,
      '#size' => 10,
    );

    $element['groups'] = array(
      '#type' => 'textarea',
      '#title' => t('Language groups'),
      '#default_value' => $settings['groups'],
      '#description' => t("Provides a simple way to group common languages. If no groups are provided, no groupings will be used. Enter in the following format:<br/><code>cn,en,ep,ru<br/>African languages|bs,br<br/>Asian languages|cn,km,fil,ja</code>"),
      '#multiple' => TRUE,
      '#size' => 10,
    );
    /*
        $element['groups_help'] = array(
          '#type' => 'fieldset',
          '#title' => t('Group help'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        );

        $items = array();
        foreach ($this->_languagefield_options() as $language) {
          $items[] = t('<strong>@key</strong>: %title', array('@key' => $language['langcode'], '%title' => $language['name']));
        }
        $element['groups_help']['keys'] = array(
          '#type' => 'item',
          '#title' => t('Full language / key listing'),
          '#markup' => theme('item_list', array('items' => $items)),
        );
        $element['groups_help']['all'] = array(
          '#type' => 'item',
          '#title' => t('Available keys'),
          '#markup' => implode(',', array_keys($this->_languagefield_options())),
        );
     */

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = array();
// @todo: when adding parent::getConstraints(), only English is allowed...
//    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()
        ->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Length' => array(
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', array(
              '%name' => $this->getFieldDefinition()
                ->getLabel(),
              '@max' => $max_length
            )),
          ),
        ),
      ));
    }

    return $constraints;

  }

  /** ************************************
   *  Begin of use Drupal\Core\TypedData\OptionsProviderInterface.
   */

  /**
   * {@inheritdoc}
   * @param $format
   *   Extra parameter for formatting options.
   */
  public function getPossibleOptions(AccountInterface $account = NULL, $format = 'en') {
    $select_options = array();

    // No need to cache this data. It is a hardcoded list.
    $standard_languages = \Drupal::languageManager()->getStandardLanguageList();

    // Format the array to Options format.
    foreach ($standard_languages as $langcode => $language_names) {
      $language_name = '';
      switch ($format) {
        case 'en':
          $language_name .= t($language_names[0]);
          break;
        case 'loc':
          $language_name .= $language_names[1];
          break;
        case 'both':
          $language_name .= t($language_names[0]);
          if (Unicode::strlen($language_names[1])) {
            $language_name .= ' (' . $language_names[1] . ')';
          }
          $language_name .= ' [' . $langcode . ']';
          break;
      }

      $select_options[$langcode] = $language_name;
    }

    asort($select_options);

    return $select_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    $options = $this->getPossibleOptions($account);
    return array_keys($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $languages = array();

    // Returns 'all' or 'enabled' languages, according to field settings.
    // This is a D8-port of D7 function _languagefield_options().
    $settings = $this->getFieldDefinition()->getSettings();
    $subsets = $settings['language_range'];

    foreach ($subsets as $subset => $active) {
      $subsettable_languages = array();
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
            $subsettable_languages[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->getName())) : $language->getName();
          }
          break;

        // The following values are copied from LanguageConfiguration::getDefaultOptions()
        // The following evaluations are copied from function language_get_default_langcode()
        // LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language", array('@language' => \Drupal::languageManager()->getDefaultLanguage()->name)),
        // 'current_interface' => t('Current interface language'),
        // 'authors_default' => t("Author's preferred language"),
        case LanguageInterface::LANGCODE_SITE_DEFAULT:
        case 'current_interface':
        case 'authors_default':
          $subsettable_languages = $this->_getLanguageConfigurationOptions($subset);
          break;
        case self::LANGUAGEFIELD_LANGUAGES_PREDEFINED: // => t('All predefined languages'),
          //case self::LANGUAGEFIELD_LANGUAGES_INSTALLED: // => t('All predefined and installed languages'),
          $subsettable_languages = $this->getPossibleOptions($account);
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
      $grouped_languages = array();
      $found_languages = array();
      $languages += array('other' => t('Other languages'));
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
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    $options = $this->getSettableOptions($account);
    return array_keys($options);
  }

  /** ************************************
   *  End of use Drupal\Core\TypedData\OptionsProviderInterface.
   */

  /** ************************************
   *  Start of contrib functions.
   */

  /**
   * Gets the Native name. (should be added to \Drupal\Core\Language\Language.)
   */
  public function getNativeName() {
    $standard_languages = \Drupal::languageManager()->getStandardLanguageList();
    return $standard_languages[$this->value][1];
  }

    /**
     * Gets the unified keys for Formatter and Widget display settings.
     */
  public static function _settingsOptions($usage = 'formatter') {
    $options = array();

    if (\Drupal::moduleHandler()->moduleExists('languageicons')) {
      if ($usage != 'widget') {
        $options += array(
          'icon' => t('Language icon'),
        );
      }
    }
    $options += array(
      'iso' => t('ISO 639-code'),
      'name' => t('Name'),
      'name_native' => t('Native name'),
    );
    return $options;
  }

  // The following values are copied from LanguageConfiguration::getDefaultOptions()
  // The following evaluations are copied from function language_get_default_langcode()
  // LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language", array('@language' => \Drupal::languageManager()->getDefaultLanguage()->name)),
  // 'current_interface' => t('Current interface language'),
  // 'authors_default' => t("Author's preferred language"),
  function _getLanguageConfigurationOptions($code) {
    $values = array();

    switch ($code) {
      case LanguageInterface::LANGCODE_SITE_DEFAULT:
        $values = array(
          LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language", array(
            '@language' => \Drupal::languageManager()
              ->getDefaultLanguage()->getName()
          ))
        );
        break;

      case 'current_interface':
        $values = array('current_interface' => t('Current interface language'));
        break;

      case 'authors_default':
        $values = array('authors_default' => t("Author's preferred language"));
        break;
    }
    return $values;
  }

  /**
   * @param $code
   * @return string
   */
  static function _getLanguageConfigurationValues($code) {
    $value = languageInterface::LANGCODE_NOT_SPECIFIED;

    switch ($code) {
      case LanguageInterface::LANGCODE_SITE_DEFAULT:
        $language = \Drupal::languageManager()->getDefaultLanguage();
        $value = $language->getId();
        break;

      case 'current_interface':
        $language = \Drupal::languageManager()->getCurrentLanguage();
        $value = $language->getId();
        break;

      case 'authors_default':
        $user = \Drupal::currentUser();
        $language_code = $user->getPreferredLangcode();
        $language = !empty($language_code)
          ? \Drupal::languageManager()->getLanguage($language_code)
          : \Drupal::languageManager()->getCurrentLanguage();
        $value = $language->getId();
        break;

      default:
        $value = $code;
    }
    return $value;
  }

}
