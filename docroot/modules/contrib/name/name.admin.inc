<?php

/**
 * @file
 * General administration functions.
 */
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Helper function to provide a list of example name components.
 *
 * @param array $excluded_components
 *   This will empty (set to "") any specified values.
 */
function name_example_names($excluded_components = array(), $field_name = NULL) {
  $example_names = array();
  if (isset($field_name)) {
    $example_names = \Drupal::config('name.examples.' . $field_name)->get('examples');
  }
  if (empty($example_name)) {
    $example_names = \Drupal::config('name.examples.default')->get('examples');
  }
  foreach ($example_names as $delta => $example_name) {
    foreach ($example_name as $component => $value) {
      if (in_array($component, $excluded_components)) {
        $example_names[$delta][$component] = '';
      }
    }
  }
  return $example_names;
}

/**
 * A helper function to generate the format string parameter help fieldset.
 */
function _name_get_name_format_help_form() {
  $parameter = array('#theme' => 'name_format_parameter_help');
  $form = array(
    '#type' => 'details',
    '#title' => t('Format string help'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#parents' => array(),
    'format_parameters' => array(
      '#markup' => \Drupal::service('renderer')->render($parameter),
    ),
  );
  return $form;
}

/**
 * Themes the instance settings of the name component into a nice table, rather
 * than a long list of individual elements.
 */
function _name_field_settings_pre_render($form) {
  $form['styled_settings'] = array(
    '#prefix' => '<table>',
    '#suffix' => '</table>',
    '#weight' => 1,
    'thead' => array(
      '#prefix' => '<thead><tr><th>' . t('Field') . '</th>',
      '#suffix' => '</tr></thead>',
      '#weight' => 0,
    ),
    'tbody' => array(
      '#prefix' => '<tbody>',
      '#suffix' => '</tbody>',
      '#weight' => 1,
      'title_display' => array(
        '#prefix' => '<tr><td><strong>' . t('Title display') . '<sup>1</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 1,
      ),
      'field_type' => array(
        '#prefix' => '<tr><td><strong>' . t('Field type') . '<sup>2</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 2,
      ),
      'size' => array(
        '#prefix' => '<tr><td><strong>' . t('HTML size') . '<sup>3</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 3,
      ),
      'inline_css' => array(
        '#prefix' => '<tr><td><strong>' . t('Inline styles') . '<sup>4</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 4,
      ),
    ),
    'tfoot' => array(
      '#markup' => '<tfoot><tr><td colspan="7"><ol>'
      . '<li>' . t('The title display controls how the label of the name component is displayed in the form. "%above" is the standard title; "%below" is the standard description; "%hidden" removes the label.', array('%above' => t('above'), '%below' => t('below'), '%hidden' => t('hidden'))) . '</li>'
      . '<li>' . t('The Field type controls how the field is rendered. Autocomplete is a text field with autocomplete, and the behaviour of this is controlled by the field settings.') . '</li>'
      . '<li>' . t('The HTML size property tells the browser what the width of the field should be when it is rendered. This gets overriden by the themes CSS properties. This must be between 1 and 255.') . '</li>'
      . '<li>' . t('Additional inline styles for the input element. For example, "width: 45px; background-color: #f3f3f3"') . '</li>'
      . '</ol></td></tr></tfoot>',
      '#weight' => 2,
    ),
    'extra_fields' => array(
      '#weight' => 3,
    ),
  );

  $i = 0;
  foreach (_name_translations() as $key => $title) {
    // Adds the table header for the particullar field.
    $form['styled_settings']['thead'][$key]['#markup'] = '<th>' . $title . '</th>';
    $form['styled_settings']['thead'][$key]['#weight'] = ++$i;

    // Strip the title & description.
    unset($form['size'][$key]['#description']);
    unset($form['size'][$key]['#title']);
    $form['size'][$key]['#size'] = 5;

    unset($form['title_display'][$key]['#description']);
    unset($form['title_display'][$key]['#title']);

    unset($form['field_type'][$key]['#description']);
    unset($form['field_type'][$key]['#title']);

    unset($form['inline_css'][$key]['#description']);
    unset($form['inline_css'][$key]['#title']);

    // Moves the size element into the table.
    $form['styled_settings']['tbody']['size'][$key] = $form['size'][$key];
    $form['styled_settings']['tbody']['size'][$key]['#prefix'] = '<td>';
    $form['styled_settings']['tbody']['size'][$key]['#suffix'] = '</td>';
    $form['styled_settings']['tbody']['size'][$key]['#weight'] = $i;

    $form['styled_settings']['tbody']['title_display'][$key] = $form['title_display'][$key];
    $form['styled_settings']['tbody']['title_display'][$key]['#prefix'] = '<td>';
    $form['styled_settings']['tbody']['title_display'][$key]['#suffix'] = '</td>';
    $form['styled_settings']['tbody']['title_display'][$key]['#weight'] = $i;

    $form['styled_settings']['tbody']['field_type'][$key] = $form['field_type'][$key];
    $form['styled_settings']['tbody']['field_type'][$key]['#prefix'] = '<td>';
    $form['styled_settings']['tbody']['field_type'][$key]['#suffix'] = '</td>';
    $form['styled_settings']['tbody']['field_type'][$key]['#weight'] = $i;

    $form['styled_settings']['tbody']['inline_css'][$key] = $form['inline_css'][$key];
    $form['styled_settings']['tbody']['inline_css'][$key]['#prefix'] = '<td>';
    $form['styled_settings']['tbody']['inline_css'][$key]['#suffix'] = '</td>';
    $form['styled_settings']['tbody']['inline_css'][$key]['#weight'] = $i;

    // Clean up the leftovers.
    unset($form['size'][$key]);
    $form['size']['#access'] = FALSE;

    unset($form['title_display'][$key]);
    $form['title_display']['#access'] = FALSE;

    unset($form['field_type'][$key]);
    $form['field_type']['#access'] = FALSE;

    unset($form['inline_css'][$key]);
    $form['inline_css']['#access'] = FALSE;
  }

  return $form;
}

/**
 * Themes the global field settings of the name component into a nice table,
 * rather than a long list of individual elements.
 */
function _name_field_storage_settings_pre_render($form) {
  // $warning = t('<strong>Warning! Changing this setting after data has been created could result in the loss of data!</strong>');
  $extra_max_info = '<div>' . t('This can not be less than the longest value in the database. The minimum values are:') . '</div>';
  $extra_max_items = array();
  foreach (_name_translations() as $key => $title) {
    $extra_max_items[] = t('@title is @size', array('@title' => $title,
      '@size' => $form['max_length'][$key]['#min']));
  }
  $item_list = array(
    '#theme' => 'item_list',
    '#items' => $extra_max_items,
  );
  $extra_max_info .= drupal_render($item_list);
  $form['field_properties'] = array(
    '#prefix' => '<table>',
    '#suffix' => '</table>',
    '#weight' => 1,
    'thead' => array(
      '#prefix' => '<thead><tr><th>' . t('Field') . '</th>',
      '#suffix' => '</tr></thead>',
      '#weight' => 0,
    ),
    'tbody' => array(
      '#prefix' => '<tbody>',
      '#suffix' => '</tbody>',
      '#weight' => 1,
      'components' => array(
        '#prefix' => '<tr><td><strong>' . t('Components') . '<sup>1</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 1,
      ),
      'minimum_components' => array(
        '#prefix' => '<tr><td><strong>' . t('Minimum components') . '<sup>2</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 2,
      ),
      'allow_family_or_given' => array(
        '#prefix' => '<tr><td>&nbsp;</td><td colspan="6">',
        '#suffix' => '</td></tr>',
        '#weight' => 2.1,
      ),
      'max_length' => array(
        '#prefix' => '<tr><td><strong>' . t('Maximum length') . '<sup>3</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 3,
      ),
      'labels' => array(
        '#prefix' => '<tr><td><strong>' . t('Labels') . '<sup>4</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 4,
      ),
      'sort_options' => array(
        '#prefix' => '<tr><td><strong>' . t('Sort options') . '<sup>5</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 5,
      ),
      'autocomplete_source' => array(
        '#prefix' => '<tr><td><strong>' . t('Autocomplete sources') . '<sup>6</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 6,
      ),
      'autocomplete_separator' => array(
        '#prefix' => '<tr><td><strong>' . t('Autocomplete separator') . '<sup>7</sup></strong></td>',
        '#suffix' => '</tr>',
        '#weight' => 7,
      ),
    ),
    'tfoot' => array(
      '#markup' => '<tfoot><tr><td colspan="7"><ol>'
      . '<li>' . t('Only selected components will be activated on this field. All non-selected components / component settings will be ignored.') . '</li>'
      . '<li>' . t('The minimal set of components required before the field is considered completed enough to save.') . '</li>'
      . '<li>' . t('The maximum length of the field in characters. This must be between 1 and 255.')
      . $extra_max_info . '</li>'
      . '<li>' . t('The labels are used to distinguish the fields.') . '</li>'
      . '<li>' . t('This enables sorting on the options after the vocabulary terms are added and duplicate values are removed.') . '</li>'
      . '<li>' . t('At least one value must be selected before you can enable the autocomplete option on the input textfields.')
      . '<li>' . t('This allows you to override the default handling that the autocomplete uses to handle separations between components. If empty, this defaults to a single space.')
      . '</ol></td></tr></tfoot>',
      '#weight' => 2,
    ),
  );

  $form['field_properties']['tbody']['allow_family_or_given']['checkbox'] = $form['allow_family_or_given'];
  unset($form['allow_family_or_given']);

  $i = 0;
  foreach (_name_translations() as $key => $title) {
    // Adds the table header for the particullar field.
    $form['field_properties']['thead'][$key]['#markup'] = '<th>' . $title . '</th>';
    $form['field_properties']['thead'][$key]['#weight'] = ++$i;

    // Strip the title & description.
    unset($form['components'][$key]['#description']);
    unset($form['components'][$key]['#title']);

    unset($form['minimum_components'][$key]['#description']);
    unset($form['minimum_components'][$key]['#title']);

    unset($form['max_length'][$key]['#description']);
    unset($form['max_length'][$key]['#title']);
    $form['max_length'][$key]['#size'] = 10;

    unset($form['labels'][$key]['#description']);
    unset($form['labels'][$key]['#title']);
    $form['labels'][$key]['#size'] = 10;

    if (isset($form['sort_options'][$key])) {
      unset($form['sort_options'][$key]['#description']);
      unset($form['sort_options'][$key]['#title']);
    }

    unset($form['autocomplete_source'][$key]['#description']);
    unset($form['autocomplete_source'][$key]['#title']);

    unset($form['autocomplete_separator'][$key]['#description']);
    unset($form['autocomplete_separator'][$key]['#title']);

    // Moves the elements into the table.
    $form['field_properties']['tbody']['components'][$key] = $form['components'][$key];
    $form['field_properties']['tbody']['components'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['components'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['components'][$key]['#weight'] = $i;

    $form['field_properties']['tbody']['minimum_components'][$key] = $form['minimum_components'][$key];
    $form['field_properties']['tbody']['minimum_components'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['minimum_components'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['minimum_components'][$key]['#weight'] = $i;

    $form['field_properties']['tbody']['max_length'][$key] = $form['max_length'][$key];
    $form['field_properties']['tbody']['max_length'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['max_length'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['max_length'][$key]['#weight'] = $i;

    $form['field_properties']['tbody']['labels'][$key] = $form['labels'][$key];
    $form['field_properties']['tbody']['labels'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['labels'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['labels'][$key]['#weight'] = $i;

    if (isset($form['sort_options'][$key])) {
      $form['field_properties']['tbody']['sort_options'][$key] = $form['sort_options'][$key];
    }
    else {
      $form['field_properties']['tbody']['sort_options'][$key] = array('#markup' => '&nbsp;');
    }

    $form['field_properties']['tbody']['sort_options'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['sort_options'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['sort_options'][$key]['#weight'] = $i;

    $form['field_properties']['tbody']['autocomplete_source'][$key] = $form['autocomplete_source'][$key];
    $form['field_properties']['tbody']['autocomplete_source'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['autocomplete_source'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['autocomplete_source'][$key]['#weight'] = $i;

    $form['field_properties']['tbody']['autocomplete_separator'][$key] = $form['autocomplete_separator'][$key];
    $form['field_properties']['tbody']['autocomplete_separator'][$key]['#prefix'] = '<td>';
    $form['field_properties']['tbody']['autocomplete_separator'][$key]['#suffix'] = '</td>';
    $form['field_properties']['tbody']['autocomplete_separator'][$key]['#weight'] = $i;

    // Clean up the leftovers.
    unset($form['components'][$key]);
    $form['components']['#access'] = FALSE;

    unset($form['minimum_components'][$key]);
    $form['minimum_components']['#access'] = FALSE;

    unset($form['max_length'][$key]);
    $form['max_length']['#access'] = FALSE;

    unset($form['labels'][$key]);
    $form['labels']['#access'] = FALSE;

    if (isset($form['sort_options'][$key])) {
      unset($form['sort_options'][$key]);
      $form['sort_options']['#access'] = FALSE;
    }

    unset($form['autocomplete_source'][$key]);
    $form['autocomplete_source']['#access'] = FALSE;

    unset($form['autocomplete_separator'][$key]);
    $form['autocomplete_separator']['#access'] = FALSE;
  }

  // Move the additional options under the table.
  $form['extra_fields'] = array(
    '#weight' => 2,
  );
  $form['title_options']['#weight'] = 0;
  $form['generational_options']['#weight'] = 1;
  $form['extra_fields']['title_options'] = $form['title_options'];
  $form['extra_fields']['generational_options'] = $form['generational_options'];
  unset($form['title_options']);
  unset($form['generational_options']);

  return $form;
}
