<?php
/**
 * @file
 * Custom funtions.
 */

/**
 * Get class name for selected font setting.
 *
 * @param string $prefix
 *   Briefing name of font setting.
 * @param string $font_type
 *   Type of setting default/google web font/custom/...
 * @param string $font_value
 *   Selected font family.
 *
 * @return string
 *   A string is used to class name.
 */
function nucleus_get_class_font($prefix, $font_type, $font_value) {
  $font_value = trim($font_value);
  $font_value = drupal_strtolower($font_value);
  $font_value = drupal_html_class($font_value);
  return $prefix . (!empty($font_type) ? '-' . $font_type : "") . "-" . $font_value;
}

/**
 * Format font family to be able to show as inline CSS.
 *
 * @param string $font_value
 *   Font-family.
 *
 * @return string
 *   String is formated for css define.
 */
function nucleus_format_font_family($font_value) {
  $parts = explode(',', $font_value);
  $results = array();
  foreach ($parts as $part) {
    $part = trim($part);
    $part = preg_replace('/[^\w\d_ -]/si', '', $part);
    if (strpos($part, ' ') !== FALSE) {
      $part = "'" . $part . "'";
    }
    $results[] = $part;
  }
  return implode(', ', $results);
}

/**
 * Get font-settings options.
 *
 * @param array $fonts_list
 *   A predefine fonts list is gotten from .info.
 * @param string $prefix
 *   Briefing of font setting, such as font-base is fb-.
 * @param bool $use_base_font
 *   Flag if it's base-font.
 *
 * @return array
 *   List of font-settings options.
 */
function nucleus_get_font_settings_options($fonts_list, $prefix, $use_base_font) {
  $results = array();
  if ($use_base_font) {
    $results[''] = t('Use base font');
  }
  foreach ($fonts_list as $key => $font) {
    $results[$prefix . $key] = $font;
  }
  return $results;
}

/**
 * Generate the part of theme setting form about the block styles.
 *
 * @param string $theme
 *   Name of theme to get the blocks list.
 *
 * @return array
 *   List of blocks.
 */
function nucleus_get_active_blocks_by_theme($theme) {
  $query = db_select('block', 'b')
    ->fields('b')
    ->condition('theme', $theme)
    ->condition('status', 1);
  return $query->execute()->fetchAll();
}

/**
 * Generate the part of theme setting form about the block styles.
 *
 * @param array &$form
 *   System_theme_settings form.
 *
 * @return array
 *   Sub-form about the block styles.
 */
function nucleus_block_styles_form(&$form) {
  $classes_info = nucleus_extend_classes_info();
  $style_support_counter = $classes_info['style_support_counter'];
  global $theme_key;

  // Retrieve all active blocks in active theme.
  $blocks = nucleus_get_active_blocks_by_theme($theme_key);

  // Retrieve the theme data to list all available regions.
  $theme_data = list_themes();
  $regions = $theme_data[$theme_key]->info['regions'];
  $pages = isset($theme_data[$theme_key]->info['pages']) ? $theme_data[$theme_key]->info['pages'] : array('default' => 'Default page');
  $block_styles = isset($theme_data[$theme_key]->info['block_styles']) ? $theme_data[$theme_key]->info['block_styles'] : array();
  $block_extend_classes = isset($theme_data[$theme_key]->info['block_extend_classes']) ? $theme_data[$theme_key]->info['block_extend_classes'] : array();

  $block_styles = array("default" => t("-- Select style --")) + $block_styles;

  if (isset($regions[BLOCK_REGION_NONE])) {
    $regions[BLOCK_REGION_NONE] = t('Disabled');
  }

  // Retrieve the list of using regions and their blocks inside.
  $regions_list = nucleus_get_regions_blocks_list($regions, $blocks);

  // Create sub_form for blocks styling.
  $sub_form = array(
    '#type' => 'fieldset',
    '#title' => t('Block styles'),
    '#weight' => 30,
  );

  $sub_form['block_style_description'] = array(
    '#markup' => '<div class="description">' . t('You need to have a flexible mark-up and style for every blocks. With Nucleus block style feature you get these requirements done easily. You can use any defined mark-up, any defined styles for any block you need in the theme settings. In this scope: style means mark-up, classes mean html class. Read <a href="http://www.themebrain.com/guide" target="_blank">Nucleus Quick Guide</a> for more information.') . '</div>',
  );

  $sub_form['layout_block_style_wrapper'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('layout-element-wrapper')),
  );
  $sub_form['layout_block_style_wrapper']['page_block_style'] = array(
    '#type' => 'select',
    '#title' => t('Settings for layout'),
    '#default_value' => theme_get_setting('page_layout'),
    '#options' => $pages,
  );

  foreach ($pages as $page => $page_description) {
    // Create page_form to settings block style for each page.
    $page_form = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(
          'select[name="page_block_style"]' => array(
            'value' => $page,
          ),
        ),
      ),
    );
    $page_key = empty($page) ? "default_" : ($page . "_");
    $page_key = str_replace("-", "_", $page_key);

    // Each page will have a global setting for all blocks with lowest priority.
    $global_form = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('global-block-styles-wrapper', 'form-header')),
    );

    $key = $page_key . 'global_block';
    $type = 'global';
    $style_name = $page_key . 'global_block_style';
    $hidden_name = $page_key . "global_block_extend_class";
    $class_name = str_replace("_", "-", $hidden_name);

    $global_style = theme_get_setting($style_name);
    $global_style = empty($global_style) ? 'default' : $global_style;
    $global_form[$style_name] = array(
      '#type' => 'select',
      '#title' => t('Global block style'),
      '#default_value' => $global_style,
      '#options' => $block_styles,
      '#attributes' => array('onchange' => "Drupal.Nucleus.nucleusOnChangeBlockStyle('" . $key . "', '" . $type . "')"),
    );

    $global_form[$page_key . 'global_block_extend_class_form'] = array(
      '#markup' => nucleus_create_popup_extend_classes($key, $type, $global_style),
    );

    $global_form[$hidden_name] = array(
      '#type' => 'hidden',
      '#default_value' => theme_get_setting($hidden_name),
    );

    $page_form[$page_key . 'global_block_style_wrapper'] = $global_form;

    foreach ($regions_list as $region_key => $region) {
      if ($region_key != BLOCK_REGION_NONE) {
        $subwrapper = array(
          '#type' => 'container',
        );

        // Each page will have some regions setting for all blocks inside with
        // higher priority than global.
        $region_form = array(
          '#type' => 'container',
          '#attributes' => array('class' => array('region-block-styles-wrapper', 'form-header')),
        );

        $key = $page_key . 'region_' . $region_key;
        $type = 'region';
        $style_name = $page_key . 'region_' . $region_key . "_style";
        $hidden_name = $page_key . 'region_' . $region_key . "_extend_class";
        $class_name = str_replace("_", "-", $hidden_name);
        $region_style = theme_get_setting($style_name);
        $region_style = empty($region_style) ? 'default' : $region_style;
        $region_form[$style_name] = array(
          '#type' => 'select',
          '#title' => t('Region: @region_title', array('@region_title' => $region['region_title'])),
          '#default_value' => $region_style,
          '#options' => $block_styles,
          '#attributes' => array('onchange' => "Drupal.Nucleus.nucleusOnChangeBlockStyle('" . $key . "', '" . $type . "')"),
        );
        $apply_style = ($region_style == 'default') ? $global_style : $region_style;

        $region_form[$page_key . 'region_' . $region_key . '_extend_class_form'] = array(
          '#markup' => nucleus_create_popup_extend_classes($key, $type, $apply_style),
        );

        $region_form[$hidden_name] = array(
          '#type' => 'hidden',
          '#default_value' => theme_get_setting($hidden_name),
        );

        $subwrapper[$page_key . 'region_' . $region_key . '_style_wrapper'] = $region_form;

        $blocks = $region['blocks'];
        foreach ($blocks as $block) {
          $block_key = $block->module . "_" . $block->delta;
          // Each page will have some regions setting for all blocks inside
          // with higher priority than global.
          $block_form = array(
            '#type' => 'container',
            '#attributes' => array('class' => array('block-styles-wrapper', 'form-subheader')),
          );

          $key = $page_key . 'block_' . $block_key;
          $type = 'block';
          $style_name = $page_key . 'block_' . $block_key . "_style";
          $hidden_name = $page_key . 'block_' . $block_key . "_extend_class";
          $class_name = str_replace("_", "-", $hidden_name);
          $block_style = theme_get_setting($style_name);
          $block_style = empty($block_style) ? 'default' : $block_style;

          $block_form[$style_name] = array(
            '#type' => 'select',
            '#title' => t('Block: @module--@delta', array('@module' => $block->module, '@delta' => $block->delta)),
            '#default_value' => $block_style,
            '#options' => $block_styles,
            '#attributes' => array('onchange' => "Drupal.Nucleus.nucleusOnChangeBlockStyle('" . $key . "', '" . $type . "')"),
          );

          $apply_style = ($block_style == 'default') ? (($region_style == 'default') ? $global_style : $region_style) : $block_style;
          $block_form[$page_key . 'block_' . $block_key . '_extend_class_form'] = array(
            '#markup' => nucleus_create_popup_extend_classes($key, $type, $apply_style),
          );

          $block_form[$hidden_name] = array(
            '#type' => 'hidden',
            '#default_value' => theme_get_setting($hidden_name),
          );

          $subwrapper[$page_key . 'block_' . $block_key . '_style_wrapper'] = $block_form;
        }
        $page_form[$page_key . 'region_' . $region_key . '_style_wrapper'] = $subwrapper;
      }
    }
    $sub_form[$page_key . "subform_wrapper"] = $page_form;
  }

  $js_regions_blocks_list = nucleus_get_js_regions_blocks_list($regions_list);

  $form['#attached']['css'][] = array(
    'data' => drupal_get_path('theme', 'nucleus') . "/css/popup_extend_class.css",
    'type' => 'file',
  );
  $form['#attached']['js'][] = array(
    'data' => drupal_get_path('theme', 'nucleus') . "/js/popup_extend_class.js",
    'type' => 'file',
  );
  $form['#attached']['js'][] = array(
    'data' => 'Drupal.Nucleus.nucleusStyleSupportCounter = ' . json_encode($style_support_counter) . ';',
    'type' => 'inline',
  );
  $form['#attached']['js'][] = array(
    'data' => 'Drupal.Nucleus.nucleusRegionsBlocksList = ' . json_encode($js_regions_blocks_list) . ';',
    'type' => 'inline',
  );
  $form['#attached']['js'][] = array(
    'data' => 'Drupal.Nucleus.nucleusExtendClassSupportGroups = ' . json_encode($classes_info['support_groups']) . ';',
    'type' => 'inline',
  );
  $form['#attached']['js'][] = array(
    'data' => 'Drupal.Nucleus.nucleusExtendClassGroupsNameList = ' . json_encode($classes_info['groups_name_list']) . ';',
    'type' => 'inline',
  );
  $form['#attached']['js'][] = array(
    'data' => 'Drupal.Nucleus.nucleusExtendClassesList = ' . json_encode($classes_info['classes_list']) . ';',
    'type' => 'inline',
  );
  return $sub_form;
}

/**
 * Get list of regions and their blocks.
 *
 * @param array $regions
 *   An array containing all regions in selected theme.
 * @param array $blocks
 *   An array containing all active blocks in selected theme.
 *
 * @return array
 *   An array containing all regions and their blocks.
 */
function nucleus_get_regions_blocks_list($regions, $blocks) {
  $regions_list = array();
  foreach ($blocks as $block) {
    $region_key = isset($block->region) ? $block->region : BLOCK_REGION_NONE;
    $region_title = isset($regions[$region_key]) ? $regions[$region_key] : "";
    if (!isset($regions_list[$region_key])) {
      $regions_list[$region_key] = array();
    }
    $regions_list[$region_key]['region_title'] = $region_title;
    if (!isset($regions_list[$region_key]['blocks'])) {
      $regions_list[$region_key]['blocks'] = array();
    }
    $regions_list[$region_key]['blocks'][] = $block;
  }
  return $regions_list;
}

/**
 * Get list to show the relationship of the regions and blocks so that help
 * js code run faster.
 *
 * @param array $regions_list
 *   An array containing all regions and their blocks.
 *
 * @return array
 *   List show the relationship of the regions and blocks.
 */
function nucleus_get_js_regions_blocks_list($regions_list) {
  $js_regions_blocks_list = array('regions' => array(), 'blocks' => array());
  foreach ($regions_list as $region_key => $region) {
    $region_key = str_replace('_', '-', $region_key);
    $js_regions_blocks_list['regions'][$region_key] = array();
    foreach ($region['blocks'] as $block) {
      $block_key = $block->module . '-' . $block->delta;
      $block_key = str_replace('_', '-', $block_key);
      $js_regions_blocks_list['regions'][$region_key][$block_key] = 1;
      if (!isset($js_regions_blocks_list['blocks'][$block_key])) {
        $js_regions_blocks_list['blocks'][$block_key] = $region_key;
      }
    }
  }
  return $js_regions_blocks_list;
}

/**
 * Generate extend class popup.
 *
 * @param string $key
 *   Specific key for each block style element setting.
 * @param string $type
 *   Type block style element setting.
 * @param string $applying_style
 *   Style is applied for this block setting.
 *
 * @return string
 *   Return markup for each popup.
 */
function nucleus_create_popup_extend_classes($key, $type, $applying_style) {
  $classes_info = nucleus_extend_classes_info();
  if (!is_array($classes_info) || !isset($classes_info['groups'])) {
    return "";
  }

  $style_name = $key . '_style';
  $hidden_name = $key . "_extend_class";

  $group_class = array();
  $selected_classes = array();

  $style_value = theme_get_setting($style_name);
  $setting_value = theme_get_setting($hidden_name);

  $parts = explode(' ', $setting_value);
  $parts_count = count($parts);
  for ($i = 0; $i < $parts_count; $i += 2) {
    $group = trim($parts[$i]);
    $class = ($parts_count > $i + 1) ? trim($parts[$i + 1]) : "";
    $group_class[$group] = $class;
    if (isset($classes_info['classes_list'][$class])) {
      $selected_classes[] = $classes_info['classes_list'][$class];
    }
  }

  $name = str_replace('_', '-', $hidden_name);
  $groups_vars = array('name' => $name, 'hidden_name' => $hidden_name, 'groups' => array());
  foreach ($classes_info['groups'] as $group_key => $group) {
    $extend_classes = $group['extend_classes'];
    $classes_vars = array(
      'name' => $name,
      'hidden_name' => $hidden_name,
      'classes' => $extend_classes,
      'group' => $group_key,
      'default_value' => isset($group_class[$group_key]) ? $group_class[$group_key] : "",
    );
    $template_file = drupal_get_path('theme', 'nucleus') . "/tpl/admin/nucleus-extend-class-form-classes.tpl.php";
    $groups_vars['groups'][$group_key] = array();
    $groups_vars['groups'][$group_key]['group_title'] = $group['group_title'];
    $groups_vars['groups'][$group_key]['classes_content'] = theme_render_template($template_file, $classes_vars);
    $groups_vars['groups'][$group_key]['show'] = isset($classes_info['support_groups'][$applying_style][$group_key]);
  }

  $template_file = drupal_get_path('theme', 'nucleus') . "/tpl/admin/nucleus-extend-class-form-groups.tpl.php";

  $default_value = implode(', ', $selected_classes);
  if ($default_value == "") {
    $default_value = "&nbsp;";
  }
  $variables = array(
    'key' => $key,
    'type' => $type,
    'name' => $name,
    'default_value' => $default_value,
    'nucleus_extend_class_form_groups' => theme_render_template($template_file, $groups_vars),
    'show_extend_class_popup' => count($classes_info['support_groups'][$applying_style]),
  );
  $template_file = drupal_get_path('theme', 'nucleus') . "/tpl/admin/nucleus-extend-class.tpl.php";
  return theme_render_template($template_file, $variables);
}

/**
 * Get all info related to extend class is defined in .info.
 *
 * @return array
 *   The information about block styles after strutured.
 */
function nucleus_extend_classes_info() {
  static $_nucleus_extend_classes_info = 0;
  if ($_nucleus_extend_classes_info) {
    // Don't need regenerate if this info had been generated.
    return $_nucleus_extend_classes_info;
  }

  // Get settings related to block styles.
  global $theme_key;
  $theme_data = list_themes();
  $block_styles = isset($theme_data[$theme_key]->info['block_styles']) ? $theme_data[$theme_key]->info['block_styles'] : array();
  $block_styles = array('default' => 'Default') + $block_styles;
  $block_extend_classes = isset($theme_data[$theme_key]->info['block_extend_classes']) ? $theme_data[$theme_key]->info['block_extend_classes'] : array();
  $block_extended_class_groups = isset($theme_data[$theme_key]->info['block_extended_class_groups']) ? $theme_data[$theme_key]->info['block_extended_class_groups'] : array();

  $visited = array();
  $block_styles_list = array();
  foreach ($block_styles as $style_key => $style_title) {
    $block_styles_list[] = $style_key;
  }

  // Initial result.
  $_nucleus_extend_classes_info = array();
  $_nucleus_extend_classes_info['support_groups'] = array();
  $_nucleus_extend_classes_info['groups'] = array();
  $_nucleus_extend_classes_info['groups_name_list'] = array();
  $_nucleus_extend_classes_info['classes_list'] = $block_extend_classes;
  $_nucleus_extend_classes_info['style_support_counter'] = array();

  // Update result.
  foreach ($block_styles_list as $block_style_key) {
    $_nucleus_extend_classes_info['support_groups'][$block_style_key] = array();
  }

  // Update result.
  foreach ($block_extended_class_groups as $group_key => $group) {
    $_nucleus_extend_classes_info['groups_name_list'][] = $group_key;
    $group_title = isset($group['group_title']) ? $group['group_title'] : "";
    $supported_styles = isset($group['supported_styles']) ? $group['supported_styles'] : FALSE;
    $classes = isset($group['classes']) ? $group['classes'] : "";

    $supported_styles_arr = $supported_styles ? explode(',', $supported_styles) : $block_styles_list;
    $classes_arr = explode(',', $classes);

    foreach ($supported_styles_arr as $supported_style) {
      $supported_style = trim($supported_style);
      if (isset($block_styles[$supported_style]) && $supported_style != 'raw') {
        $_nucleus_extend_classes_info['support_groups'][$supported_style][$group_key] = $group_key;
      }
    }

    $_nucleus_extend_classes_info['groups'][$group_key] = array();
    $_nucleus_extend_classes_info['groups'][$group_key]['group_title'] = $group_title;
    $_nucleus_extend_classes_info['groups'][$group_key]['extend_classes'] = array();
    foreach ($classes_arr as $class) {
      $class = trim($class);
      if (isset($block_extend_classes[$class])) {
        $_nucleus_extend_classes_info['groups'][$group_key]['extend_classes'][$class] = $block_extend_classes[$class];
        if (!isset($visited[$class])) {
          $visited[$class] = TRUE;
        }
      }
    }
  }

  $has_class_in_default_group = FALSE;
  foreach ($block_extend_classes as $class_key => $class_title) {
    if (!isset($visited[$class_key])) {
      $has_class_in_default_group = TRUE;
      break;
    }
  }

  if ($has_class_in_default_group) {
    $default_group_name = 'default_group';
    $default_group_title = 'Default group';
    while (isset($_nucleus_extend_classes_info['groups'][$default_group_name])) {
      $default_group_name .= '_';
    }

    foreach ($_nucleus_extend_classes_info['support_groups'] as $group_key => &$support_group) {
      if ($group_key != 'raw') {
        $support_group[$default_group_name] = $default_group_name;
      }
    }

    $_nucleus_extend_classes_info['groups'][$default_group_name] = array(
      'group_title' => t($default_group_title),
      'extend_classes' => array(),
    );

    foreach ($block_extend_classes as $class_key => $class_title) {
      if (!isset($visited[$class_key])) {
        $_nucleus_extend_classes_info['groups'][$default_group_name]['extend_classes'][$class_key] = $class_title;
      }
    }

    $_nucleus_extend_classes_info['groups_name_list'][] = $default_group_name;
  }

  $style_support_counter = array();
  foreach ($_nucleus_extend_classes_info['support_groups'] as $style_key => $groups_list) {
    $style_support_counter[$style_key] = count($groups_list);
  }
  $_nucleus_extend_classes_info['style_support_counter'] = $style_support_counter;
  return $_nucleus_extend_classes_info;
}

/**
 * Get all supported layout of current theme.
 *
 * @return array
 *   The list of the layouts that current theme supports.
 */
function nucleus_pages() {
  global $theme_key;
  $theme_data = list_themes();
  $pages = isset($theme_data[$theme_key]->info['pages']) ? $theme_data[$theme_key]->info['pages'] : array();
  if (isset($pages['default'])) {
    return $pages;
  }
  else {
    return array('default' => t('Normal page')) + $pages;
  }
}

/**
 * Generate class name for the group sidebar in page.tpl.php.
 *
 * @param array $regions_list
 *   List of the regions in this group, is a string seperated by comma.
 *
 * @return string
 *   Class name for the group.
 */
function nucleus_group_class($regions_list) {
  $grid = theme_get_setting('grid');
  $grid_type = drupal_substr($grid, 0, 5);

  $regions = explode(",", $regions_list);
  $sidebar_regions_width = nucleus_get_sidebar_regions_width();

  $ggrid = 0;
  foreach ($regions as $region) {
    $region = trim($region);
    if (isset($sidebar_regions_width[$region])) {
      $ggrid += $sidebar_regions_width[$region];
    }
  }
  return 'group-' . $ggrid . " grid grid-" . $ggrid;
}

/**
 * Get all sidebar regions.
 *
 * @return array
 *   List of sidebar regions.
 */
function nucleus_get_sidebar_regions() {
  static $_nucleus_sidebar_regions = 0;
  if (!$_nucleus_sidebar_regions) {
    global $theme_key;
    $theme_data = list_themes();
    $regions = $theme_data[$theme_key]->info['regions'];
    $_nucleus_sidebar_regions = array();
    foreach ($regions as $key => $value) {
      if (strpos($key, "sidebar_") !== FALSE) {
        $_nucleus_sidebar_regions[$key] = $value;
      }
    }
  }
  return $_nucleus_sidebar_regions;
}

/**
 * Calculate width of all sidebar regions.
 *
 * @param string|null $page
 *   $vars['page'] in theme_preprocess_page.
 *
 * @return array
 *   Width of all sidebar and content regions by grid.
 */
function nucleus_get_sidebar_regions_width($page = NULL) {
  static $_nucleus_sidebar_regions_width = 0;
  if (!$_nucleus_sidebar_regions_width && $page) {
    $grid = theme_get_setting('grid');
    $grid_int = drupal_substr($grid, -2);
    $sidebar_regions = nucleus_get_sidebar_regions();
    $_nucleus_sidebar_regions_width = array();
    $_nucleus_sidebar_regions_width['content'] = $grid_int;
    foreach ($sidebar_regions as $key => $value) {
      $content = render($page[$key]);
      $_nucleus_sidebar_regions_width[$key] = !empty($content) ? theme_get_setting($key . "_width") : 0;
      $_nucleus_sidebar_regions_width['content'] -= $_nucleus_sidebar_regions_width[$key];
    }
  }
  return $_nucleus_sidebar_regions_width;
}

/**
 * Get all panel regions.
 *
 * @return array
 *   List of panel regions are used by current theme.
 */
function nucleus_panel_regions() {
  static $_nucleus_panel_regions = 0;
  if (!$_nucleus_panel_regions) {
    global $theme_key;
    $theme_data = list_themes();
    $regions = $theme_data[$theme_key]->info['regions'];
    $_nucleus_panel_regions = array();
    foreach ($regions as $key => $value) {
      if (strpos($key, "panel_") !== FALSE) {
        $parts = explode("_", $key);
        $id = $parts[count($parts) - 1];
        unset($parts[count($parts) - 1]);
        $panel_name = implode("_", $parts);

        if (isset($_nucleus_panel_regions[$panel_name])) {
          $_nucleus_panel_regions[$panel_name][$key] = $value;
        }
        else {
          $_nucleus_panel_regions[$panel_name] = array($key => $value);
        }
      }
    }
  }
  return $_nucleus_panel_regions;
}

/**
 * Calculate width of all panel regions.
 *
 * @return array
 *   Width of all panel regions are used by current theme.
 */
function nucleus_panel_regions_width() {
  static $_nucleus_panel_regions_width = 0;
  if (!$_nucleus_panel_regions_width) {
    $grid = theme_get_setting('grid');
    $grid_int = drupal_substr($grid, -2);
    $_nucleus_panel_regions = nucleus_panel_regions();
    $_nucleus_panel_regions_width = array();
    foreach ($_nucleus_panel_regions as $key => $panels_list) {
      $sum = 0;
      foreach ($panels_list as $panel => $panel_title) {
        $width = theme_get_setting($panel . "_width");
        if ($sum + $width > $grid_int) {
          $width = $grid_int - $sum;
        }
        $sum += $width;
        $_nucleus_panel_regions_width[$panel] = $width;
      }
    }
  }
  return $_nucleus_panel_regions_width;
}

/**
 * Generate briefing list of font settings.
 *
 * @return array
 *   List related to the default list fonts.
 */
function nucleus_default_fonts_arr() {
  return array(
    'bf' => array('key' => 'base_font', 'title' => 'Base font'),
    'snf' => array('key' => 'site_name_font', 'title' => 'Site name'),
    'ssf' => array('key' => 'site_slogan_font', 'title' => 'Site slogan'),
    'ptf' => array('key' => 'page_title_font', 'title' => 'Page title'),
    'ntf' => array('key' => 'node_title_font', 'title' => 'Node title'),
    'ctf' => array('key' => 'comment_title_font', 'title' => 'Comment title'),
    'btf' => array('key' => 'block_title_font', 'title' => 'Block title'),
  );
}

/**
 * Generate grid options list.
 *
 * @param int $grid
 *   Is a number shows the grid (example 12, 16, 24).
 *
 * @return array
 *   Array will be the options in a select box.
 */
function nucleus_grid_options($grid) {
  $options = array(0 => t('Hidden'));
  for ($i = 1; $i <= $grid; $i++) {
    $options[$i] = ($i == 1) ? t('@i grid', array('@i' => $i)) : t('@i grids', array('@i' => $i));
  }
  return $options;
}

/**
 * Reset settings of current theme.
 */
function nucleus_reset_settings() {
  global $theme_key;
  variable_del('theme_' . $theme_key . '_settings');
  variable_del('theme_settings');
  $cache = &drupal_static('theme_get_setting', array());
  $cache[$theme_key] = NULL;
}

/**
 * Get template for panels.
 *
 * @return string
 *   Full path to the panel template is used.
 */
function nucleus_get_panel_template() {
  global $theme_key;
  $cache = cache_get(__FUNCTION__ . ':' . $theme_key);
  if ($cache && !empty($cache->data)) {
    return $cache->data;
  }

  $themes = list_themes();
  $theme_keys = nucleus_get_theme_keys();

  $template_file = FALSE;
  for ($i = count($theme_keys) - 1; $i >= 0; $i--) {
    $theme_name = $theme_keys[$i];
    if (file_exists(drupal_get_path('theme', $theme_name) . "/tpl/panel.tpl.php")) {
      $template_file = drupal_get_path('theme', $theme_name) . "/tpl/panel.tpl.php";
      break;
    }
  }
  cache_set(__FUNCTION__ . ':' . $theme_key, $template_file);
  return $template_file;
}

/**
 * Get array of themes to get settings.
 *
 * @return string
 *   List of themes key.
 */
function nucleus_get_theme_keys() {
  global $theme_key;
  $themes = list_themes();
  $theme_object = $themes[$theme_key];
  $theme_keys = array();
  if (isset($theme_object->base_themes)) {
    $theme_keys = array_keys($theme_object->base_themes);
    $theme_keys[] = $theme_key;
  }
  elseif (isset($theme_object->base_theme)) {
    $theme_keys = array($theme_object->base_theme, $theme_key);
  }
  else {
    $theme_keys = array($theme_key);
  }
  return $theme_keys;
}

/**
 * Add css with rule of Conditional Stylesheets modules.
 *
 * Source code reference from conditional_styles module
 * http://drupal.org/project/conditional_styles.
 *
 * @param array &$css
 *   Drupal css array.
 */
function nucleus_add_conditional_styles(&$css) {
  $themes = list_themes();
  $theme_keys = nucleus_get_theme_keys();

  foreach ($theme_keys as $key) {
    $theme_path = drupal_get_path('theme', $key) . '/';
    if (isset($themes[$key]->info['stylesheets-conditional'])) {
      foreach (array_keys($themes[$key]->info['stylesheets-conditional']) as $condition) {
        foreach (array_keys($themes[$key]->info['stylesheets-conditional'][$condition]) as $media) {
          foreach ($themes[$key]->info['stylesheets-conditional'][$condition][$media] as $stylesheet) {
            $parts = explode("/", $stylesheet);
            $css_filename = $parts[count($parts) - 1];
            // Add each conditional stylesheet.
            $css = drupal_add_css(
              $theme_path . $stylesheet,
              array(
                'group' => CSS_THEME,
                'browsers' => array(
                  'IE' => $condition,
                  '!IE' => FALSE,
                ),
                'every_page' => TRUE,
                'basename' => $key . '-' . $css_filename,
              )
            );
          }
        }
      }
    }
  }
}

/**
 * Get array of typo_keys_map to help source code in html_preprocess is shorter.
 *
 * @return array
 *   List of typo keys match with page elements.
 */
function nucleus_typo_key_map() {
  return array(
    'snf' => '.site-name',
    'ssf' => '.site-slogan',
    'ptf' => '#page-title',
    'ntf' => '.node-title',
    'ctf' => '.comment-title',
    'btf' => '.block-title',
  );
}

/**
 * Get array setting from info by name.
 *
 * @param string $param
 *   Name of setting in infor file.
 * @param array $pre_array
 *   The sub array will be added to before the first.
 * @param array $suf_array
 *   The sub array will be added to after the last.
 *
 * @return array
 *   Presetting.
 */
function nucleus_get_predefined_param($param, $pre_array = array(), $suf_array = array()) {
  global $theme_key;
  $theme_data = list_themes();
  $result = isset($theme_data[$theme_key]->info[$param]) ? $theme_data[$theme_key]->info[$param] : array();
  return $pre_array + $result + $suf_array;
}
