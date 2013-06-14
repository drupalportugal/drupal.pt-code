<?php
/**
 * @file
 * Override of preprocess functions.
 */

/**
 * Override or insert variables into the html template.
 */
function nucleus_preprocess_html(&$vars) {
  global $theme, $base_url;

  $grid = theme_get_setting('grid');
  $grid_type = drupal_substr($grid, 0, 5);

  $layout_width = $grid_type == 'fixed' ? theme_get_setting('layout_width_fixed') : theme_get_setting('layout_width');

  $layout_width_int = intval($layout_width);
  if ($layout_width_int > 100) {
    $layout_width = $layout_width_int . "px";
  }
  elseif ($layout_width_int > 0) {
    $layout_width = $layout_width_int . "%";
  }
  else {
    $layout_width = '960px';
  }

  $layout_css = 'div.container{width:' . $layout_width . ';}';

  drupal_add_css($layout_css, array(
    'group' => CSS_THEME, 'type' => 'inline',
  ));

  $vars['classes_array'][] = drupal_html_class($theme);

  $fonts_arr = nucleus_default_fonts_arr();
  $used_gwf_list = array();
  $typo_keys_map = nucleus_typo_key_map();

  foreach ($fonts_arr as $font_setting_key => $value) {
    $key = $value['key'];
    $font_type = theme_get_setting($key . "_type");
    $font_value = theme_get_setting($key . (!empty($font_type) ? ('_' . $font_type) : ""));
    if ($font_type == '') {
      if (!empty($font_value)) {
        $vars['classes_array'][] = $font_value;
      }
    }
    else {
      if ($font_type == 'gwf') {
        if (!empty($font_value)) {
          $used_gwf_list[$font_value] = $font_value;
        }
      }

      if (!empty($font_value)) {
        $font_value = nucleus_format_font_family($font_value);
        $style_name = nucleus_get_class_font($font_setting_key, $font_type, $font_value);
        $vars['classes_array'][] = $style_name;

        switch ($font_setting_key) {
          case 'bf':
            drupal_add_css("body.$style_name, .$style_name .form-text { font-family: " . $font_value . "; }", array(
              'type' => 'inline',
            ));
            break;
          default:
            drupal_add_css("body.$style_name " . $typo_keys_map[$font_setting_key] . " { font-family: " . $font_value . "; }", array(
              'type' => 'inline',
            ));
            break;
        }
      }
    }
  }
  $vars['classes_array'][] = theme_get_setting('font_size');

  if (count($used_gwf_list)) {
    $font_arr = array();
    foreach ($used_gwf_list as $key) {
      $parts = explode(",", $key);
      foreach ($parts as $part) {
        $font_arr[] = trim($part);
      }
    }
    $font_str = implode("|", $font_arr);
    $font_str = str_replace(" ", "+", $font_str);
    $url = 'http://fonts.googleapis.com/css?family=' . $font_str . "&v2";
    drupal_add_css($url);
  }

  if (theme_get_setting('load_extra_typo_styles')) {
    drupal_add_css(drupal_get_path('theme', 'nucleus') . "/css/typo.css");
    if (file_exists(path_to_theme() . "/css/typo.css")) {
      drupal_add_css(path_to_theme() . "/css/typo.css");
    }
  }

  global $_nucleus_body_extend_class;
  if ($_nucleus_body_extend_class && is_array($_nucleus_body_extend_class)) {
    foreach ($_nucleus_body_extend_class as $class_name) {
      $vars['classes_array'][] = $class_name;
    }
  }
  if ($grid_type == 'fluid') {
    $vars['classes_array'][] = 'fluid';
  }
}

/**
 * Override or insert variables into the page template.
 */
function nucleus_preprocess_page(&$vars) {
  $vars['layout_path'] = drupal_get_path('theme', 'nucleus') . '/tpl/layouts/';
  $grid = theme_get_setting('grid');
  $layout_width = theme_get_setting('layout_width');
  $sidebar_regions = nucleus_get_sidebar_regions();
  $grid_type = drupal_substr($grid, 0, 5);
  $grid = drupal_substr($grid, -7);
  $grid_int = drupal_substr($grid, -2);
  $sidebar_regions_width = nucleus_get_sidebar_regions_width($vars['page'], $grid_int);
  $vars['grid'] = $grid;
  foreach ($sidebar_regions_width as $region_key => $region_width) {
    if ($region_width) {
      $vars[$region_key . "_width"] = "grid grid-" . $region_width;
    }
    else {
      $vars[$region_key . "_width"] = 0;
    }
  }

  if ($grid_type == 'fluid') {
    drupal_add_css(drupal_get_path('theme', 'nucleus') . '/css/grid/fluid/grid-' . $grid_int . '.css', array(
      'group' => CSS_THEME,
    ));
    if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE) {
      drupal_add_css(drupal_get_path('theme', 'nucleus') . '/css/grid/fluid/grid-' . $grid_int . '-opera.css', array(
        'group' => CSS_THEME,
      ));
    }
  }
  else {
    drupal_add_css(drupal_get_path('theme', 'nucleus') . '/css/grid/fixed/grid-' . $grid_int . '.css', array(
      'group' => CSS_THEME,
    ));
  }

  if (isset($vars['node'])) {
    for ($i = count($vars['theme_hook_suggestions']); $i > 0; $i--) {
      $theme_hook_sugesstion = $vars['theme_hook_suggestions'][$i - 1];
      if ($theme_hook_sugesstion != 'page__node') {
        $vars['theme_hook_suggestions'][$i] = $vars['theme_hook_suggestions'][$i - 1];
      }
      else {
        $vars['theme_hook_suggestions'][$i] = 'page__node__' . str_replace('-', '_', $vars['node']->type);
        break;
      }
    }
  }

  $layout = theme_get_setting('page_layout');
  if ($layout != 'default') {
    $layout = str_replace("-", "_", $layout);
    $tmp_theme_hook_suggestions = array('page__' . $layout);
    foreach ($vars['theme_hook_suggestions'] as $suggestion) {
      $tmp_theme_hook_suggestions[] = $suggestion;
      $tmp_theme_hook_suggestions[] = $suggestion . "__" . $layout;
    }
    $vars['theme_hook_suggestions'] = $tmp_theme_hook_suggestions;
  }

  $vars['back_to_top_display'] = theme_get_setting('back_to_top_display') == 'yes';

  // Render all panel regions.
  $panel_regions = nucleus_panel_regions();
  if (count($panel_regions)) {
    $template_file = nucleus_get_panel_template();
    if ($template_file) {
      $panel_regions_width = nucleus_panel_regions_width();
      foreach ($panel_regions as $panel_name => $panels_list) {
        $panel_markup = theme("render_panel", array(
          'page' => $vars['page'],
          'panels_list' => $panels_list,
          'panel_regions_width' => $panel_regions_width,
          'template_file' => $template_file,
        ));
        $panel_markup = trim($panel_markup);
        $vars[$panel_name] = empty($panel_markup) ? FALSE : $panel_markup;
      }
    }
  }
}

/**
 * Override or insert variables into the panel template.
 */
function nucleus_preprocess_render_panel(&$variables) {
  $page = $variables['page'];
  $panels_list = $variables['panels_list'];
  $panel_regions_width = $variables['panel_regions_width'];
  $variables = array();
  $variables['page'] = array();
  $variables['panel_width'] = $panel_regions_width;
  $variables['panel_classes'] = array();
  $variables['panels_list'] = $panels_list;
  $used_panel = FALSE;
  $panel_keys = array_keys($panels_list);
  $first_key = $last_key = FALSE;
  for ($i = 0; $i < count($panel_keys); $i++) {
    if ($panel_regions_width[$panel_keys[$i]]) {
      $first_key = $first_key ? $first_key : $panel_keys[$i];
      $last_key = $panel_keys[$i];
    }
  }

  foreach ($panels_list as $panel => $panel_label) {
    $variables['page'][$panel] = $page[$panel];
    $panel_width = $panel_regions_width[$panel];
    if (render($page[$panel])) {
      $used_panel = TRUE;
    }
    $variables['panel_grid'][$panel] = "grid grid-" . $panel_width;
    $classes = array();
    $classes[] = "panel-column";
    $classes[] = str_replace("_", "-", $panel);
    if ($panel == $first_key) {
      $classes[] = 'panel-column-first';
    }
    if ($panel == $last_key) {
      $classes[] = 'panel-column-last';
    }

    $variables['panel_classes'][$panel] = implode(" ", $classes);
  }
  $variables['used_panel'] = $used_panel;
}

/**
 * Override or insert variables into the node template.
 */
function nucleus_preprocess_node(&$vars) {
  global $theme, $user;
  $vars['title_attributes_array']['class'][] = 'node-title';
  $vars['content_attributes_array']['class'][] = 'node-content';
  if (module_exists('translation')) {
    if ($vars['node']->language) {
      global $language;
      $vars['classes_array'][] = 'node-lang-' . $vars['node']->language;
    }
  }
  $vars['classes_array'][] = 'node-' . $vars['zebra'];
  $vars['classes_array'][] = drupal_html_class('node-' . $vars['view_mode']);
  $vars['datetime'] = format_date($vars['created'], 'custom', 'c');
  $vars['unpublished'] = '';
  if (!$vars['status']) {
    $vars['unpublished'] = '<div class="unpublished">' . t('Unpublished') . '</div>';
  }
}

/**
 * Override or insert variables into the comment template.
 */
function nucleus_preprocess_comment(&$vars) {
  $vars['title_attributes_array']['class'][] = 'comment-title';
  $vars['content_attributes_array']['class'][] = 'comment-content';
  $uri = entity_uri('comment', $vars['comment']);
  $uri['options'] += array(
    'attributes' => array(
      'rel' => 'bookmark',
    ),
  );
  $vars['title'] = l($vars['comment']->subject, $uri['path'], $uri['options']);
  $vars['permalink'] = l(t('Permalink'), $uri['path'], $uri['options']);
  $vars['created'] = '<span class="date-time permalink">' . l($vars['created'], $uri['path'], $uri['options']) . '</span>';
  $vars['datetime'] = format_date($vars['comment']->created, 'custom', 'c');
  $vars['unpublished'] = '';
  if ($vars['status'] == 'comment-unpublished') {
    $vars['unpublished'] = '<div class="unpublished">' . t('Unpublished') . '</div>';
  }
}

/**
 * Override or insert variables into the block template.
 */
function nucleus_preprocess_block(&$vars) {
  $vars['title_attributes_array']['class'][] = 'block-title';
  $vars['content_attributes_array']['class'][] = 'block-content';
  $vars['content_attributes_array']['class'][] = 'clearfix';
  if ($vars['block']->region == 'menu_bar') {
    $vars['title_attributes_array']['class'][] = 'element-invisible';
  }

  $block = $vars['block'];
  $block_key = $block->module . "_" . $block->delta;
  $region_key = $block->region;

  $page_layout = theme_get_setting('page_layout');
  $page_layout = str_replace("-", "_", $page_layout);

  $custom_style = theme_get_setting($page_layout . '_block_' . $block_key . '_style');
  if (empty($custom_style) || $custom_style == 'default') {
    $custom_style = theme_get_setting($page_layout . '_region_' . $region_key . '_style');
  }
  if (empty($custom_style) || $custom_style == 'default') {
    $custom_style = theme_get_setting($page_layout . '_global_block_style');
  }

  if (!empty($custom_style) && $custom_style != 'default') {
    $tmp_theme_hook_suggestions = array('block__' . $custom_style);
    foreach ($vars['theme_hook_suggestions'] as $suggestion) {
      $tmp_theme_hook_suggestions[] = $suggestion;
      $tmp_theme_hook_suggestions[] = $suggestion . "__" . $custom_style;
    }
    $vars['theme_hook_suggestions'] = $tmp_theme_hook_suggestions;
  }

  $classes = $vars['classes_array'];

  $custom_class = theme_get_setting($page_layout . '_block_' . $block_key . '_extend_class');
  if (empty($custom_class)) {
    $custom_class = theme_get_setting($page_layout . '_region_' . $region_key . '_extend_class');
  }
  if (empty($custom_class)) {
    $custom_class = theme_get_setting($page_layout . '_global_block_extend_class');
  }

  if (!empty($custom_class)) {
    $class_array = explode(' ', $custom_class);
    $parts_count = count($class_array);
    for ($i = 0; $i < $parts_count; $i += 2) {
      $class = ($parts_count > $i + 1) ? trim($class_array[$i + 1]) : "";
      $classes[] = $class;
    }
  }

  if ($block->module == 'views') {
    global $theme;
    drupal_add_css(drupal_get_path('theme', $theme) . "/css/views.css");
  }
  if ($vars['elements']['#block']->module == 'system' && $vars['elements']['#block']->delta == 'main') {
    foreach ($classes as $key => $value) {
      if ($value == 'block') {
        unset($classes[$key]);
      }
    }
  }
  $vars['classes_array'] = $classes;
}

/**
 * Override or insert variables into the aggregator-item template.
 */
function nucleus_preprocess_aggregator_item(&$vars) {
  $item = $vars['item'];
  $vars['datetime'] = format_date($item->timestamp, 'custom', 'c');
}

/**
 * Override or insert variables into the fieldset template.
 */
function nucleus_preprocess_fieldset(&$vars) {
  $element = $vars['element'];
  $vars['attributes'] = $element['#attributes'];
  $vars['attributes']['id'][] = $element['#id'];
  $vars['attributes']['class'][] = 'fieldset';
  $vars['attributes']['class'][] = 'form-wrapper';
  if (!empty($element['#title'])) {
    $vars['attributes']['class'][] = 'titled';
  }
  $description = !empty($element['#description']) ? "<div class='description'>{$element['#description']}</div>" : '';
  $children = !empty($element['#children']) ? $element['#children'] : '';
  $value = !empty($element['#value']) ? $element['#value'] : '';
  $vars['content'] = $description . $children . $value;
  $vars['title'] = !empty($element['#title']) ? $element['#title'] : '';
  $vars['hook'] = 'fieldset';
}
