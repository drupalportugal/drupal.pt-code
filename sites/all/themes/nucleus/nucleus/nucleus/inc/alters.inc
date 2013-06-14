<?php
/**
 * @file
 * Override of the hook alter.
 */

/**
 * Implements hook_html_head_alter().
 */
function nucleus_html_head_alter(&$head_elements) {
  $head_elements['system_meta_content_type']['#attributes'] = array(
    'http-equiv' => 'Content-Type',
    'content' => 'text/html; charset=utf-8',
  );
}

/**
 * Implements hook_css_alter().
 */
function nucleus_css_alter(&$css) {
  $path = drupal_get_path('theme', 'nucleus') . "/css/base.css";
  $css = drupal_add_css($path, array(
    'group' => CSS_SYSTEM,
    'every_page' => TRUE,
    'weight' => 0,
  ));

  foreach ($css as $key => &$value) {
    if ($key == $path) {
      $value['weight'] = 0;
    }
    else {
      $value['weight'] += 0.001;
    }
  }
  nucleus_add_conditional_styles($css);
  global $theme_key;
  $skin = theme_get_setting("skin");
  if (!empty($skin)) {
    $css = drupal_add_css(drupal_get_path('theme', $theme_key) . '/skins/' . $skin . '/style.css', array(
      'group' => CSS_THEME,
      'basename' => 'nucleus-' . $skin . '-styles.css',
    ));
  }
}
