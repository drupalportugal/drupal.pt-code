<?php

/**
 * @file
 * Contains \Drupal\name\Plugin\Field\FieldType\NameItem.
 */

namespace Drupal\name\Plugin\Field\FieldType;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'name' field type.
 *
 * @FieldType(
 *   id = "name",
 *   label = @Translation("Name"),
 *   description = @Translation("Stores real name."),
 *   default_widget = "name_default",
 *   default_formatter = "name_default"
 * )
 */
class NameItem extends FieldItemBase {

  /**
   * Definition of name field components
   *
   * @var array
   */
  protected static $components = array(
    'title',
    'given',
    'middle',
    'family',
    'generational',
    'credentials'
  );

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = array(
      'components' => array(
        'title' => TRUE,
        'given' => TRUE,
        'middle' => TRUE,
        'family' => TRUE,
        'generational' => TRUE,
        'credentials' => TRUE,
      ),
      'minimum_components' => array(
        'title' => FALSE,
        'given' => TRUE,
        'middle' => FALSE,
        'family' => TRUE,
        'generational' => FALSE,
        'credentials' => FALSE,
      ),
      'allow_family_or_given' => FALSE,
      'labels' => array(
        'title' => t('Title'),
        'given' => t('Given'),
        'middle' => t('Middle name(s)'),
        'family' => t('Family'),
        'generational' => t('Generational'),
        'credentials' => t('Credentials')
      ),
      'max_length' => array(
        'title' => 31,
        'given' => 63,
        'middle' => 127,
        'family' => 63,
        'generational' => 15,
        'credentials' => 255
      ),
      'autocomplete_source' => array(
        'title' => array(
          'title',
        ),
        'given' => array(),
        'middle' => array(),
        'family' => array(),
        'generational' => array(
          'generation',
        ),
        'credentials' => array(),
      ),
      'autocomplete_separator' => array(
        'title' => ' ',
        'given' => ' -',
        'middle' => ' -',
        'family' => ' -',
        'generational' => ' ',
        'credentials' => ', ',
      ),
      'title_options' => array(
        t('-- --'),
        t('Mr.'),
        t('Mrs.'),
        t('Miss'),
        t('Ms.'),
        t('Dr.'),
        t('Prof.')
      ),
      'generational_options' => array(
        t('-- --'),
        t('Jr.'),
        t('Sr.'),
        t('I'),
        t('II'),
        t('III'),
        t('IV'),
        t('V'),
        t('VI'),
        t('VII'),
        t('VIII'),
        t('IX'),
        t('X')
      ),
      'sort_options' => array(
        'title' => FALSE
      )
    );

    return $settings + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = array(
      'component_css' => '',
      'component_layout' => 'default',
      'show_component_required_marker' => FALSE,
      'credentials_inline' => FALSE,
      'override_format' => 'default',
      'field_type' => array(
        'title' => 'select',
        'given' => 'text',
        'middle' => 'text',
        'family' => 'text',
        'generational' => 'select',
        'credentials' => 'text'
      ),
      'size' => array(
        'title' => 6,
        'given' => 20,
        'middle' => 20,
        'family' => 20,
        'generational' => 5,
        'credentials' => 35
      ),
      'title_display' => array(
        'title' => 'description',
        'given' => 'description',
        'middle' => 'description',
        'family' => 'description',
        'generational' => 'description',
        'credentials' => 'description'
      ),
      'inline_css' => array(
        'title' => '',
        'given' => '',
        'middle' => '',
        'family' => '',
        'generational' => '',
        'credentials' => ''
      )
    );

    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'));

    $properties['given'] = DataDefinition::create('string')
      ->setLabel(t('Given'));

    $properties['middle'] = DataDefinition::create('string')
      ->setLabel(t('Middle name(s)'));

    $properties['family'] = DataDefinition::create('string')
      ->setLabel(t('Family'));

    $properties['generational'] = DataDefinition::create('string')
      ->setLabel(t('Generational'));

    $properties['credentials'] = DataDefinition::create('string')
      ->setLabel(t('Credentials'));

    return $properties;
  }

  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $field = $this->getFieldDefinition();
    $settings = $field->getSettings();

    $element = array(
      '#tree' => TRUE,
    );

    $components = _name_translations();
    $element['components'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Components'),
      '#default_value' => array_keys(array_filter($settings['components'])),
      '#required' => TRUE,
      '#description' => t('Only selected components will be activated on this field. All non-selected components / component settings will be ignored.'),
      '#options' => $components,
    );

    $element['minimum_components'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Minimum components'),
      '#default_value' => array_keys(array_filter($settings['minimum_components'])),
      '#required' => TRUE,
      '#description' => t('The minimal set of components required before the field is considered completed enough to save.'),
      '#options' => $components,
      '#element_validate' => array(array(get_class($this), 'validateMinimumComponents')),
    );
    $element['labels'] = array();
    $element['max_length'] = array();
    $element['autocomplete_sources'] = array();
    $autocomplete_sources_options = array();
    if   (\Drupal::moduleHandler()->moduleExists('namedb')) {
      $autocomplete_sources_options['namedb'] = t('Names DB');
    }
    $autocomplete_sources_options['title'] = t('Title options');
    $autocomplete_sources_options['generational'] = t('Generational options');
    // TODO: Placing in the to hard basket for the time being!
    //$autocomplete_sources_options['data'] = t('Data');

    foreach ($components as $key => $title) {
      $min_length = 1;
      if ($has_data) {
        $min_length = $settings['max_length'][$key];
        // @todo: Port this feature to Drupal 8
        /*
        if ($field['storage']['type'] == 'field_sql_storage') {
          try {
            $table = 'field_data_' . $field['field_name'];
            $column = $field['storage']['details']['sql'][FIELD_LOAD_CURRENT]
            [$table][$key];
            $min_length = db_query("SELECT MAX(CHAR_LENGTH({$column})) AS len FROM {$table}")->fetchField();
            if ($min_length < 1) {
              $min_length = 1;
            }
          } catch (Exception $e) {
          }
        }
        */
      }
      $element['max_length'][$key] = array(
        '#type' => 'number',
        '#min' => $min_length,
        '#max' => 255,
        '#title' => t('Maximum length for @title', array('@title' => $title)),
        '#default_value' => $settings['max_length'][$key],
        '#required' => TRUE,
        '#size' => 10,
        '#description' => t('The maximum length of the field in characters. This must be between !min and 255.', array('!min' => $min_length)),

      );
      $element['labels'][$key] = array(
        '#type' => 'textfield',
        '#title' => t('Label for @title', array('@title' => $title)),
        '#default_value' => $settings['labels'][$key],
        '#required' => TRUE,
      );
      $element['autocomplete_source'][$key] = array(
        '#type' => 'checkboxes',
        '#title' => t('Autocomplete options'),
        '#default_value' => $settings['autocomplete_source'][$key],
        '#description' => t("This defines what autocomplete sources are available to the field."),
        '#options' => $autocomplete_sources_options,
      );
      if ($key != 'title') {
        unset($element['autocomplete_source'][$key]['#options']['title']);
      }
      if ($key != 'generational') {
        unset($element['autocomplete_source'][$key]['#options']['generational']);
      }
      $element['autocomplete_separator'][$key] = array(
        '#type' => 'textfield',
        '#title' => t('Autocomplete separator for @title', array('@title' => $title)),
        '#default_value' => $settings['autocomplete_separator'][$key],
        '#size' => 10,
      );
    }

    $element['allow_family_or_given'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow a single valid given or family value to fulfill the minimum component requirements for both given and family components.'),
      '#default_value' => !empty($settings['allow_family_or_given']),
    );

    // TODO - Grouping & grouping sort
    // TODO - Allow reverse free tagging back into the vocabulary.
    $title_options = implode("\n", array_filter($settings['title_options']));
    $element['title_options'] = array(
      '#type' => 'textarea',
      '#title' => t('@title options', array('@title' => $components['title'])),
      '#default_value' => $title_options,
      '#required' => TRUE,
      '#description' => t("Enter one @title per line. Prefix a line using '--' to specify a blank value text. For example: '--Please select a @title'.", array('@title' => $components['title'])),
      '#element_validate' => array(array(get_class($this), 'validateTitleOptions')),
    );
    $generational_options = implode("\n", array_filter($settings['generational_options']));
    $element['generational_options'] = array(
      '#type' => 'textarea',
      '#title' => t('@generational options', array('@generational' => $components['generational'])),
      '#default_value' => $generational_options,
      '#required' => TRUE,
      '#description' => t("Enter one @generational suffix option per line. Prefix a line using '--' to specify a blank value text. For example: '----'.", array('@generational' => $components['generational'])),
      '#element_validate' => array(array(get_class($this), 'validateGenerationalOptions')),
    );
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      // TODO - Make the labels more generic.
      // Generational suffixes may be also imported from one or more vocabularies
      // using the tag '[vocabulary:xxx]', where xxx is the vocabulary id. Terms
      // that exceed the maximum length of the generational suffix are not added
      // to the options list.
      $element['title_options']['#description'] .= ' ' . t("%label_plural may be also imported from one or more vocabularies using the tag '[vocabulary:xxx]', where xxx is the vocabulary machine-name or id. Terms that exceed the maximum length of the %label are not added to the options list.",
          array('%label_plural' => t('Titles'), '%label' => t('Title')));
      $element['generational_options']['#description'] .= ' ' . t("%label_plural may be also imported from one or more vocabularies using the tag '[vocabulary:xxx]', where xxx is the vocabulary machine-name or id. Terms that exceed the maximum length of the %label are not added to the options list.",
          array(
            '%label_plural' => t('Generational suffixes'),
            '%label' => t('Generational suffix')
          ));
    }
    $sort_options = is_array($settings['sort_options']) ? $settings['sort_options'] : array(
      'title' => 'title',
      'generational' => '',
    );
    $element['sort_options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select field sort options'),
      '#default_value' => $sort_options,
      '#description' => t("This enables sorting on the options after the vocabulary terms are added and duplicate values are removed."),
      '#options' => _name_translations(array(
        'title' => '',
        'generational' => ''
      )),
    );

    $element['#pre_render'][] = 'name_field_storage_settings_pre_render';
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function isEmpty() {
    foreach ($this->properties as $property) {
      $definition = $property->getDataDefinition();
      if (!$definition->isComputed() && $property->getValue() !== NULL) {
        return FALSE;
      }
    }
    if (isset($this->values)) {
      foreach ($this->values as $name => $value) {
        // Title & generational have no meaning by themselves.
        if ($name == 'title' || $name == 'generational') {
          continue;
        }
        if (isset($value) && !isset($this->properties[$name])) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $components = _name_translations();


    $element = array(
      'size' => array(),
      'title_display' => array(),
    );

    $field_options = array(
      'select' => t('Drop-down'),
      'text' => t('Text field'),
      'autocomplete' => t('Autocomplete')
    );

    foreach ($components as $key => $title) {
      $element['field_type'][$key] = array(
        '#type' => 'radios',
        '#title' => t('@title field type', array('@title' => $components['title'])),
        '#default_value' => $settings['field_type'][$key],
        '#required' => TRUE,
        '#options' => $field_options,
      );

      if (!($key == 'title' || $key == 'generational')) {
        unset($element['field_type'][$key]['#options']['select']);
      }

      $element['size'][$key] = array(
        '#type' => 'number',
        '#min' => 1,
        '#max' => 255,
        '#title' => t('HTML size property for @title', array('@title' => $title)),
        '#default_value' => $settings['size'][$key],
        '#required' => FALSE,
        '#size' => 10,
        '#description' => t('The maximum length of the field in characters. This must be between 1 and 255.'),
      );

      $element['title_display'][$key] = array(
        '#type' => 'radios',
        '#title' => t('Label display for @title', array('@title' => $title)),
        '#default_value' => $settings['title_display'][$key],
        '#options' => array(
          'title' => t('above'),
          'description' => t('below'),
          'none' => t('hidden'),
        ),
        '#description' => t('This controls how the label of the component is displayed in the form.'),
      );

      $element['inline_css'][$key] = array(
        '#type' => 'textfield',
        '#title' => t('Additional inline styles for @title input element.', array('@title' => $title)),
        '#default_value' => $settings['inline_css'][$key],
        '#size' => 8,
      );
    }

    $element['component_css'] = array(
      '#type' => 'textfield',
      '#title' => t('Component separator CSS'),
      '#default_value' => $this->getSetting('component_css'),
      '#description' => t('Use this to override the default CSS used when rendering each component. Use "&lt;none&gt;" to prevent the use of inline CSS.'),
    );

    $items = array(
      t('The order for Asian names is Family Middle Given Title'),
      t('The order for Eastern names is Title Family Given Middle'),
      t('The order for Western names is Title First Middle Surname'),
    );
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $layout_description = t('<p>This controls the order of the widgets that are displayed in the form.</p>')
      . drupal_render($item_list)
      . t('<p>Note that when you select the Asian names format, the Generational field is hidden and defaults to an empty string.</p>');
    $element['component_layout'] = array(
      '#type' => 'radios',
      '#title' => t('Language layout'),
      '#default_value' => $this->getSetting('component_layout'),
      '#options' => array(
        'default' => t('Western names'),
        'asian' => t('Asian names'),
        'eastern' => t('Eastern names'),
      ),
      '#description' => $layout_description,
    );
    $element['show_component_required_marker'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show component required marker'),
      '#default_value' => $this->getSetting('show_component_required_marker'),
      '#description' => t('Appends an asterisk after the component title if the component is required as part of a complete name.'),
    );
    $element['credentials_inline'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the credentials inline'),
      '#default_value' => $this->getSetting('credentials_inline'),
      '#description' => t('The default position is to show the credentials on a line by themselves. This option overrides this to render the component inline.'),
    );

    // Add the overwrite user name option.
    if ($this->getFieldDefinition()->getTargetEntityTypeId() == 'user') {
      $preferred_field = \Drupal::config('name.settings')
        ->get('user_preferred');
      $element['name_user_preferred'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use this field to override the users login name?'),
        '#default_value' => $preferred_field == $this->getName() ? 1 : 0,
      );
      $element['override_format'] = array(
        '#type' => 'select',
        '#title' => t('User name override format to use'),
        '#default_value' => $this->getSetting('override_format'),
        '#options' => name_get_custom_format_options(),
      );
    }
    else {
      // We may extend this feature to Profile2 latter.
      $element['override_format'] = array(
        '#type' => 'value',
        '#value' => $this->getSetting('override_format'),
      );
    }

    $element['#pre_render'][] = 'name_field_settings_pre_render';
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array();
    foreach (static::$components as $key) {
      $columns[$key] = array(
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
      );
    }
    return array(
      'columns' => $columns,
      'indexes' => array(
        'given' => array('given'),
        'family' => array('family'),
      ),
    );
  }

  public static function validateMinimumComponents($element, FormStateInterface $form_state) {
    $minimum_components = $form_state->getValue(['settings', 'minimum_components']);
    $diff = array_intersect(array_keys(array_filter($minimum_components)), array('given', 'family'));
    if (count($diff) == 0) {
      $components = array_intersect_key(_name_translations(), array_flip(array('given', 'family')));
      $form_state->setError($element, t('%label must have one of the following components: %components', array(
        '%label' => t('Minimum components'),
        '%components' => implode(', ', $components)
      )));
    }

    $components = $form_state->getValue(['settings', 'components']);
    $minimum_components = $form_state->getValue(['settings', 'minimum_components']);
    $diff = array_diff_key(array_filter($minimum_components), array_filter($components));
    if (count($diff)) {
      $components = array_intersect_key(_name_translations(), $diff);
      $form_state->setError($element, t('%components can not be selected for %label when they are not selected for %label2.', array(
        '%label' => t('Minimum components'),
        '%label2' => t('Components'),
        '%components' => implode(', ', $components)
      )));
    }
  }

  public static function validateTitleOptions($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);
    $max_length = $form_state->getValue(['settings', 'max_length', 'title']);
    static::validateOptions($element, $form_state, $values, $max_length);
  }

  public static function validateGenerationalOptions($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);
    $max_length = $form_state->getValue(['settings', 'max_length', 'generational']);
    static::validateOptions($element, $form_state, $values, $max_length);
  }

  protected static function validateOptions($element, FormStateInterface $form_state, $values, $max_length) {
    $label = $element['#title'];

    $long_options = array();
    $valid_options = array();
    $default_options = array();
    foreach ($values as $value) {
      $value = trim($value);
      // Blank option - anything goes!
      if (strpos($value, '--') === 0) {
        $default_options[] = $value;
      }
      // Simple checks on the taxonomy includes.
      elseif (preg_match('/^\[vocabulary:([0-9a-z\_]{1,})\]/', $value, $matches)) {
        if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
          $form_state->setError($element, t("The taxonomy module must be enabled before using the '%tag' tag in %label.", array(
            '%tag' => $matches[0],
            '%label' => $label
          )));
        }
        elseif ($value !== $matches[0]) {
          $form_state->setError($element, t("The '%tag' tag in %label should be on a line by itself.", array(
            '%tag' => $matches[0],
            '%label' => $label
          )));
        }
        else {
          $vocabulary = entity_load('taxonomy_vocabulary', $matches[1]);
          if ($vocabulary) {
            $valid_options[] = $value;
          }
          else {
            $form_state->setError($element, t("The vocabulary '%tag' in %label could not be found.", array(
              '%tag' => $matches[1],
              '%label' => $label
            )));
          }
        }
      }
      elseif (Unicode::strlen($value) > $max_length) {
        $long_options[] = $value;
      }
      elseif (!empty($value)) {
        $valid_options[] = $value;
      }
    }
    if (count($long_options)) {
      $form_state->setError($element, t('The following options exceed the maximum allowed %label length: %options', array(
        '%options' => implode(', ', $long_options),
        '%label' => $label
      )));
    }
    elseif (empty($valid_options)) {
      $form_state->setError($element, t('%label are required.', array(
        '%label' => $label
      )));
    }
    elseif (count($default_options) > 1) {
      $form_state->setError($element, t('%label can only have one blank value assigned to it.', array(
        '%label' => $label
      )));
    }

    $form_state->setValueForElement($element, array_merge($default_options, $valid_options));
  }

  protected static function extractAllowedValues($string) {
    return array_filter(array_map('trim', explode("\n", $string)));
  }

}
