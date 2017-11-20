<?php

namespace Drupal\languagefield\Plugin\Field\FieldType;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\languagefield\Entity\CustomLanguageManager;

/**
 * Plugin implementation of the 'language' field type.
 *
 * @FieldType(
 *   id = "language_field",
 *   label = @Translation("Language"),
 *   description = @Translation("This field stores a language as a Field."),
 *   default_widget = "languagefield_select",
 *   default_formatter = "languagefield_default",
 *   no_ui = FALSE,
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Length" = {"max" = 12},
 *       }
 *     }
 *   }
 * )
 */
class LanguageItem extends \Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return parent::propertyDefinitions($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $maxlenght = $field_definition->getSetting('maxlength');

    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => $maxlenght,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to no default value.
    $this->setValue(NULL, $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $defaultStorageSettings = [
        'maxlength' => CustomLanguageManager::LANGUAGEFIELD_LANGCODE_MAXLENGTH,
        'language_range' => [CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_PREDEFINED => CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_PREDEFINED],
        'included_languages' => [],
        'excluded_languages' => [],
        'groups' => '',
        // @see callback_allowed_values_function()
        'allowed_values_function' => 'languagefield_allowed_values',
      ] + parent::defaultStorageSettings();

    return $defaultStorageSettings;
  }

  /**
   * Gets the unified keys for Formatter and Widget display settings.
   */
  public static function _settingsOptions($usage = 'formatter') {
    $options = [];
    $t = \Drupal::translation();

    if (\Drupal::moduleHandler()->moduleExists('languageicons')) {
      if ($usage != 'widget') {
        $options += [
          'icon' => $t->translate('Language icon'),
        ];
      }
    }
    $options += [
      'iso' => $t->translate('ISO 639-code'),
      'name' => $t->translate('Name'),
      'name_native' => $t->translate('Native name'),
    ];
    return $options;
  }

  /**
   * @param $code
   * @return string
   */
  public static function _getLanguageConfigurationValues($code) {
    $value = LanguageInterface::LANGCODE_NOT_SPECIFIED;

    switch ($code) {
      case LanguageInterface::LANGCODE_SITE_DEFAULT:
        $language = \Drupal::languageManager()->getDefaultLanguage();
        $value = $language->getId();
        break;

      case LanguageInterface::LANGCODE_NOT_SPECIFIED:
        $value = LanguageInterface::LANGCODE_NOT_SPECIFIED;
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

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $settings = $this->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSettings();

    $languages = $this->getPossibleOptions();

    $url_1 = (\Drupal::moduleHandler()->moduleExists('language'))
      ? Url::fromRoute('entity.configurable_language.collection', [], [] )->toString()
      : '';
    $url_2 = Url::fromRoute('languagefield.custom_language.collection', [], [])->toString();
    $element['language_range'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled languages'),
      '#description' => $this->t("Installed languages can be maintained on the
        <a href=':url_1'>Languages</a> page, when Language module is installed. Custom languages can
        be maintained on the <a href=':url_2'>Custom Languages</a> page. (Options marked with '*' are
        typically used as default value in a hidden widget.)", [
        ':url_1' => $url_1,
        ':url_2' => $url_2,
      ]),
      '#required' => TRUE,
      '#default_value' => $settings['language_range'],
      '#options' => [
        // The following are from Languagefield.
        CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_PREDEFINED => $this->t('All predefined languages'),
        // self::LANGUAGEFIELD_LANGUAGES_ENABLED => $this->t('Enabled installed languages (not functioning yet)'),
        // The following are from Drupal\Core\Language\LanguageInterface.
        LanguageInterface::STATE_CONFIGURABLE => $this->t('All installed (enabled) languages (from /admin/config/regional/language)'), // const STATE_CONFIGURABLE = 1; -> 'en', 'de'
        CustomLanguageManager::LANGUAGEFIELD_LANGUAGES_CUSTOM => $this->t('All custom languages (from /admin/config/regional/custom_language)'),
        LanguageInterface::STATE_LOCKED => $this->t('All locked languages'), // const STATE_LOCKED = 2; -> 'und', 'zxx'
        // LanguageInterface::STATE_ALL => $this->t('All installed languages'), // const STATE_ALL = 3; -> 'en', 'de', 'und', 'zxx'
        // LanguageInterface::STATE_SITE_DEFAULT => $this->t("The site's default language"), // const STATE_SITE_DEFAULT = 4; -> 'en'
        // The following are copied from LanguageConfiguration::getDefaultOptions()
        LanguageInterface::LANGCODE_SITE_DEFAULT => $this->t("Site's default language (@language)", [
            '@language' => \Drupal::languageManager()
              ->getDefaultLanguage()->getName()
          ]) . '*',
        LanguageInterface::LANGCODE_NOT_SPECIFIED => $this->t('Language neutral'),
        'current_interface' => $this->t('Current interface language') . '*',
        'authors_default' => $this->t("Author's preferred language") . '*',
      ],
    ];

    $element['included_languages'] = [
      '#type' => 'select',
      '#title' => $this->t('Restrict by language'),
      '#default_value' => $settings['included_languages'],
      '#options' => ['' => $this->t('- None -')] + $languages,
      '#description' => $this->t('If no languages are selected, this filter will not be used.'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];

    $element['excluded_languages'] = [
      '#type' => 'select',
      '#title' => $this->t('Excluded languages'),
      '#default_value' => $settings['excluded_languages'],
      '#options' => ['' => $this->t('- None -')] + $languages,
      '#description' => $this->t('This removes individual languages from the list.'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];

    $element['groups'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Language groups'),
      '#default_value' => $settings['groups'],
      '#description' => $this->t("Provides a simple way to group common languages. If no groups are provided, no groupings will be used. Enter in the following format:<br/><code>cn,en,ep,ru<br/>African languages|bs,br<br/>Asian languages|cn,km,fil,ja</code>"),
      '#multiple' => TRUE,
      '#size' => 10,
    ];
    /*
        $element['groups_help'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Group help'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        );

        $items = array();
        foreach ($this->_languagefield_options() as $language) {
          $items[] = $this->t('<strong>@key</strong>: %title', array('@key' => $language['langcode'], '%title' => $language['name']));
        }
        $element['groups_help']['keys'] = array(
          '#type' => 'item',
          '#title' => $this->t('Full language / key listing'),
          '#markup' => theme('item_list', array('items' => $items)),
        );
        $element['groups_help']['all'] = array(
          '#type' => 'item',
          '#title' => $this->t('Available keys'),
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
    $constraints = [];
// @todo: when adding parent::getConstraints(), only English is allowed...
//    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()
        ->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()
                ->getLabel(),
              '@max' => $max_length
            ]),
          ],
        ],
      ]);
    }

    return $constraints;

  }

  /* *************************************
 *  Begin of use Drupal\Core\TypedData\OptionsProviderInterface.
 */

  /**
   * {@inheritdoc}
   *
   * @param $format
   *   Extra parameter for formatting options.
   */
  public function getPossibleOptions(AccountInterface $account = NULL, $format = 'en') {
    $select_options = [];

    // No need to cache this data. It is a hardcoded list.
    $languages = \Drupal::languageManager()->getStandardLanguageList();
    // Add the custom languages to the list.
    $languages += CustomLanguageManager::getCustomLanguageList();

    // Format the array to Options format.
    foreach ($languages as $langcode => $language_names) {
      $language_name = '';
      switch ($format) {
        case 'en':
          $language_name .= $this->t($language_names[0]);
          break;
        case 'loc':
          $language_name .= $language_names[1];
          break;
        case 'both':
          $language_name .= $this->t($language_names[0]);
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
  public function getSettableValues(AccountInterface $account = NULL) {
    $options = $this->getSettableOptions($account);
    return array_keys($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    // Returns 'all' or 'enabled' languages, according to field settings.
    // This is a D8-port of D7 function _languagefield_options().
    $settings = $this->getFieldDefinition()->getSettings();
    return CustomLanguageManager::allowed_values($settings);
  }

  /* ************************************
   *  End of use Drupal\Core\TypedData\OptionsProviderInterface.
   */

  /* ************************************
   *  Start of contrib functions.
   */

  /**
   * Gets the Native name. (should be added to \Drupal\Core\Language\Language.)
   */
  public function getNativeName() {
    switch ($this->value) {
      case 'und':
        $name = '';
        break;

      default:
        $standard_languages = \Drupal::languageManager()->getStandardLanguageList();
        $standard_languages += CustomLanguageManager::getCustomLanguageList();
        $name = $standard_languages[$this->value][1];
        break;
    }

    return $name;
  }

}
