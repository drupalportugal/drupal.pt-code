<?php
/**
 * @file
 * Override of the hook theme.
 */

/**
 * Override of theme_field().
 */
function nucleus_field(&$vars) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$vars['label_hidden']) {
    $output .= '<h3 class="field-label"' . $vars['title_attributes'] . '>' . $vars['label'] . ':&nbsp;</h3>';
  }

  // Render the items.
  $output .= '<div class="field-items"' . $vars['content_attributes'] . '>';
  foreach ($vars['items'] as $delta => $item) {
    $classes = 'field-item ' . ($delta % 2 ? 'even' : 'odd');
    $output .= '<div class="' . $classes . '"' . $vars['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
  }
  $output .= '</div>';

  // Render the top-level DIV.
  $output = '<div class="section ' . $vars['classes'] . '"' . $vars['attributes'] . '>' . $output . '</div>';

  return $output;
}

/**
 * Override of theme_breadcrumb().
 */
function nucleus_breadcrumb($vars) {
  $show_breadcrumb = theme_get_setting('breadcrumb_display');
  if ($show_breadcrumb == 'yes') {
    $breadcrumb = $vars['breadcrumb'];
    $show_breadcrumb_home = theme_get_setting('breadcrumb_home');
    $home_class = 'crumb-home';
    if (!$show_breadcrumb_home) {
      array_shift($breadcrumb);
      $home_class = "";
    }
    if (!empty($breadcrumb)) {
      $heading = '<h2 class="element-invisible">' . t('You are here:') . '</h2>';
      $separator = filter_xss(theme_get_setting('breadcrumb_separator'));
      $output = '';
      foreach ($breadcrumb as $key => $val) {
        if ($key == 0) {
          $output .= '<li class="crumb ' . $home_class . '">' . $val . '</li>';
        }
        else {
          $output .= '<li class="crumb"><span>' . $separator . '</span>' . $val . '</li>';
        }
      }
      return $heading . '<ol id="crumbs">' . $output . '</ol>';
    }
  }
  return '';
}

/**
 * Override of theme_button().
 */
function nucleus_button($vars) {
  $element = $vars['element'];
  $element['#attributes']['class'][] = 'form-' . $element['#button_type'];
  return '<span class="button ' . $element['#id'] . '"><input type="submit" ' . (empty($element['#name']) ? '' : 'name="' . $element['#name'] . '" ') . 'id="' . $element['#id'] . '" value="' . check_plain($element['#value']) . '" ' . drupal_attributes($element['#attributes']) . " /></span>\n";
}

/**
 * Override of theme_superfish().
 */
function nucleus_superfish(&$vars) {
  global $theme_key;
  $style = theme_get_setting('superfish_extend_style_' . $vars['id']);
  if (!empty($style)) {
    $vars['sfsettings']['style'] = $style;
    $path = drupal_get_path('theme', $theme_key) . "/css/superfish/" . $style . ".css";
    drupal_add_css($path, array('group' => CSS_THEME, 'every_page' => TRUE, 'basename' => 'sf_' . $style . ".css"));
  }
  global $_nucleus_body_extend_class;
  if (!$_nucleus_body_extend_class) {
    $_nucleus_body_extend_class = array();
  }

  $delta = $vars['id'];
  if (variable_get('superfish_type_' . $delta, 'horizontal') == 'navbar') {
    if (!isset($_nucleus_body_extend_class['tb-sf-navbar'])) {
      $_nucleus_body_extend_class['tb-sf-navbar'] = 'tb-sf-navbar';
    }
  }
  return theme_superfish($vars);
}

/**
 * Override of theme_qt_quicktabs().
 */
function nucleus_qt_quicktabs(&$vars) {
  global $theme_key;
  $attributes = $vars['element']['#options']['attributes'];
  $id = drupal_substr($attributes['id'], 10);
  $style = theme_get_setting('quicktabs_extend_style_' . $id);
  if (!empty($style)) {
    $classes = $attributes['class'];
    $class_parts = explode(' ', $classes);
    $new_classes = array();
    foreach ($class_parts as $class) {
      if (strpos($class, 'quicktabs-style') === FALSE) {
        $new_classes[] = $class;
      }
    }
    $new_classes[] = 'quicktabs-style-' . $style;
    $vars['element']['#options']['attributes']['class'] = implode(' ', $new_classes);
    $vars['element']['tabs']['#options']['style'] = $style;
    $vars['element']['container']['#prefix'] = '<div id="quicktabs-container-' . $id . '" class="quicktabs_main quicktabs-style-' . $style . '">';

    $path = drupal_get_path('theme', $theme_key) . "/css/quicktabs/" . $style . ".css";
    drupal_add_css($path, array('group' => CSS_THEME, 'every_page' => TRUE, 'basename' => 'quicktabs_' . $style . ".css"));
  }
  return theme_qt_quicktabs($vars);
}

/**
 * Override of theme_qt_ui_tabs().
 */
function nucleus_qt_ui_tabs(&$vars) {
  global $theme_key;
  $attributes = $vars['element']['#options']['attributes'];
  $id = drupal_substr($attributes['id'], 10);
  $style = theme_get_setting('quicktabs_extend_style_' . $id);
  if (!empty($style)) {
    $vars['element']['#options']['attributes']['class'] .= ' quicktabs-ui-' . $style;
    $path = drupal_get_path('theme', $theme_key) . "/css/quicktabs/" . $style . ".css";
    drupal_add_css($path, array('group' => CSS_THEME, 'every_page' => TRUE, 'basename' => 'quicktabs_' . $style . ".css"));
  }
  return theme_qt_ui_tabs($vars);
}

/**
 * Override of theme_qt_accordion().
 */
function nucleus_qt_accordion($vars) {
  global $theme_key;
  $attributes = $vars['element']['#options']['attributes'];
  $id = drupal_substr($attributes['id'], 9);
  $style = theme_get_setting('quicktabs_extend_style_' . $id);
  if (!empty($style)) {
    $vars['element']['#options']['attributes']['class'][] = 'quick-accordion-' . $style;
    $path = drupal_get_path('theme', $theme_key) . "/css/quicktabs/" . $style . ".css";
    drupal_add_css($path, array('group' => CSS_THEME, 'every_page' => TRUE, 'basename' => 'quicktabs_' . $style . ".css"));
  }
  return theme_qt_accordion($vars);
}
