<?php
/**
 * @file
 * Theme setting callbacks for the nucleus theme.
 */

require_once drupal_get_path('theme', 'nucleus') . '/inc/custom_functions.inc';

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function nucleus_form_system_theme_settings_alter(&$form, $form_state) {
  if (theme_get_setting('nucleus_use_default_settings')) {
    nucleus_reset_settings();
  }

  if (!isset($form['#attached']['css'])) {
    $form['#attached']['css'] = array();
  }
  if (!isset($form['#attached']['js'])) {
    $form['#attached']['js'] = array();
  }
  $form['#attached']['js'][] = array(
    'data' => drupal_get_path('theme', 'nucleus') . '/js/nucleus.js',
    'type' => 'file',
  );
  $form['#attached']['css'][] = array(
    'data' => drupal_get_path('theme', 'nucleus') . '/css/theme-settings.css',
    'type' => 'file',
  );

  $theme_data = list_themes();
  global $theme_key;
  // Build a form for the custom theme settings using Drupals Form API.
  // Layout settings.
  $form['nucleus'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => -10,
    '#default_tab' => theme_get_setting('nucleus__active_tab'),
  );

  $form['nucleus']['layout'] = array(
    '#type' => 'fieldset',
    '#title' => t('Layout'),
    '#weight' => 0,
  );

  $form['nucleus']['layout']['grid'] = array(
    '#type' => 'select',
    '#title' => t('Grid'),
    '#default_value' => theme_get_setting('grid'),
    '#options' => array(
      'fluid-grid-24' => t('24 fluid grids'),
      'fixed-grid-24' => t('24 fixed grids for width 960px'),
      'fluid-grid-16' => t('16 fluid grids'),
      'fixed-grid-16' => t('16 fixed grids for width 960px'),
      'fluid-grid-12' => t('12 fluid grids'),
      'fixed-grid-12' => t('12 fixed grids for width 960px'),
    ),
  );

  $form['nucleus']['layout']['layout_width'] = array(
    '#type' => 'textfield',
    '#size' => 8,
    '#maxlength' => 10,
    '#title' => t('Page width'),
    '#default_value' => theme_get_setting('layout_width'),
    '#description' => t("Width of page, should be string like '960px' or '90%'"),
  );
  $skins = nucleus_get_predefined_param('skins', array('' => t("-- Select skin --")));
  if (count($skins) > 1) {
    $form['nucleus']['layout']["skin"] = array(
      '#type' => 'select',
      '#title' => t('Skin'),
      '#default_value' => theme_get_setting("skin"),
      '#options' => $skins,
    );
  }
  $form['nucleus']['layout']['layout_width_fixed'] = array(
    '#type' => 'hidden',
    '#default_value' => '960px',
  );

  $form['nucleus']['layout']['page_layout'] = array(
    '#type' => 'radios',
    '#title' => t('Layout type'),
    '#description' => t('Select the predefine layout page'),
    '#default_value' => theme_get_setting('page_layout'),
    '#options' => nucleus_pages(),
  );

  $grid = theme_get_setting('grid');
  $grid_int = intval(drupal_substr($grid, -2));
  $grid_options = nucleus_grid_options($grid_int);
  $grid_24_options = nucleus_grid_options(24);

  $sidebar_regions = nucleus_get_sidebar_regions();
  $width_selects = array();

  $form['nucleus']['layout']["sidebar_width_wrapper"] = array(
    '#type' => 'fieldset',
    '#title' => t("Sidebar width config"),
    '#attributes' => array('class' => array('panel-settings-wrapper')),
  );
  foreach ($sidebar_regions as $key => $value) {
    $form['nucleus']['layout']["sidebar_width_wrapper"][$key . '_width'] = array(
      '#type' => 'select',
      '#title' => t("@value width", array("@value" => $value)),
      '#default_value' => theme_get_setting($key . '_width'),
      '#options' => $grid_24_options,
    );
    $width_selects[] = 'edit-' . str_replace('_', '-', $key) . '-width';
  }

  $panel_regions = nucleus_panel_regions();
  $panel_regions_width = nucleus_panel_regions_width();

  foreach ($panel_regions as $key => $panels_list) {
    $form['nucleus']['layout'][$key . "_wrapper"] = array(
      '#type' => 'fieldset',
      '#title' => $key,
      '#attributes' => array('class' => array('panel-settings-wrapper')),
    );

    foreach ($panels_list as $panel => $panel_title) {
      $form['nucleus']['layout'][$key . "_wrapper"][$panel . "_width"] = array(
        '#type' => 'select',
        '#title' => t($panel_title),
        '#default_value' => $panel_regions_width[$panel],
        '#options' => $grid_24_options,
      );
      $width_selects[] = 'edit-' . str_replace('_', '-', $panel) . '-width';
    }
  }

  $form['#attached']['js'][] = array(
    'data' => 'var grid_24_options = ' . json_encode($grid_24_options) . ';',
    'type' => 'inline',
  );
  $form['#attached']['js'][] = array(
    'data' => 'var nucleus_width_selects = ' . json_encode($width_selects) . ';',
    'type' => 'inline',
  );
  $form['#attached']['js'][] = array(
    'data' => 'var current_grid_int = 24;',
    'type' => 'inline',
  );

  // Navigation.
  $form['nucleus']['navigation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Navigation'),
    '#weight' => 10,
  );
  $form['nucleus']['navigation']['breadcrumb_display'] = array(
    '#type' => 'select',
    '#title' => t('Display breadcrumb'),
    '#default_value' => theme_get_setting('breadcrumb_display'),
    '#options' => array(
      'yes' => t('Yes'),
      'no' => t('No'),
    ),
  );
  $form['nucleus']['navigation']['breadcrumb_separator'] = array(
    '#type' => 'textfield',
    '#title' => t('Breadcrumb separator'),
    '#description' => t('Text only. Dont forget to include spaces.'),
    '#default_value' => theme_get_setting('breadcrumb_separator'),
    '#size' => 8,
    '#maxlength' => 10,
    '#states' => array(
      'visible' => array(
        '#edit-breadcrumb-display' => array(
          'value' => 'yes',
        ),
      ),
    ),
  );
  $form['nucleus']['navigation']['breadcrumb_home'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show the "Home" link in breadcrumbs.'),
    '#default_value' => theme_get_setting('breadcrumb_home'),
    '#states' => array(
      'visible' => array(
        '#edit-breadcrumb-display' => array(
          'value' => 'yes',
        ),
      ),
    ),
  );

  $form['nucleus']['navigation']['back_to_top_display'] = array(
    '#type' => 'select',
    '#title' => t('To Top'),
    '#default_value' => theme_get_setting('back_to_top_display'),
    '#options' => array(
      'yes' => t('Yes'),
      'no' => t('No'),
    ),
    '#description' => t('Show button back to top'),
  );

  if (module_exists('superfish') && module_exists('quicktabs')) {
    $form['nucleus']['navigation']['contributed_modules_description'] = array(
      '#markup' => '<div class="description">' . t('We can choose one of the pre-defined styles for any Superfish or Quicktabs block you have created. Read <a href="http://www.themebrain.com/guide" target="_blank">Nucleus Quick Guide</a> for more information.') . '</div>',
    );
  }

  if (module_exists('superfish')) {
    $superfish_styles = isset($theme_data[$theme_key]->info['superfish_styles']) ? $theme_data[$theme_key]->info['superfish_styles'] : FALSE;

    if ($superfish_styles) {
      $superfish_blocks = superfish_block_info();
      if (!empty($superfish_blocks)) {
        $form['nucleus']['navigation']['superfish_extend_title'] = array(
          '#markup' => '<div class="form-header">' . t('Superfish extend style') . '</div>',
        );

        $form['nucleus']['navigation']['superfish_container'] = array(
          '#type' => 'fieldset',
        );

        $superfish_styles = array('' => t("Use Superfish style setting")) + $superfish_styles;
        foreach ($superfish_blocks as $delta => $superfish_block) {
          $form['nucleus']['navigation']['superfish_container']['superfish_extend_style_' . $delta] = array(
            '#type' => 'select',
            '#title' => variable_get('superfish_name_' . $delta, 'Superfish ' . $delta),
            '#default_value' => theme_get_setting('superfish_extend_style_' . $delta),
            '#options' => $superfish_styles,
          );
        }
      }
    }
  }

  if (module_exists('quicktabs')) {
    $quicktabs_styles = isset($theme_data[$theme_key]->info['quicktabs_styles']) ? $theme_data[$theme_key]->info['quicktabs_styles'] : FALSE;
    if ($quicktabs_styles) {
      $quicktabs_blocks = quicktabs_block_info();
      if (!empty($quicktabs_blocks)) {
        $form['nucleus']['navigation']['quicktabs_extend_title'] = array(
          '#markup' => '<div class="form-header">' . t('Quicktabs extend style') . '</div>',
        );

        $form['nucleus']['navigation']['quicktabs_container'] = array(
          '#type' => 'fieldset',
        );

        $quicktabs_styles = array('' => t("Use quicktabs style setting")) + $quicktabs_styles;
        foreach ($quicktabs_blocks as $delta => $quicktabs_block) {
          $form['nucleus']['navigation']['quicktabs_container']['quicktabs_extend_style_' . $delta] = array(
            '#type' => 'select',
            '#title' => $quicktabs_block['info'],
            '#default_value' => theme_get_setting('quicktabs_extend_style_' . $delta),
            '#options' => $quicktabs_styles,
          );
        }
      }
    }
  }

  // Typography.
  $form['nucleus']['typography'] = array(
    '#type' => 'fieldset',
    '#title' => t('Typography'),
    '#weight' => 20,
  );

  $form['nucleus']['typography']['description'] = array(
    '#markup' => '<div class="description">' . t('Nucleus allows users to choose the font for all default text element on a Drupal site. You have the option to select from a predefined font family, Google web font list or any custom font you have at hand. Read <a href="http://www.themebrain.com/guide" target="_blank">Nucleus Quick Guide</a> for more information.') . '</div>',
  );

  $fonts_arr = nucleus_default_fonts_arr();
  $default_fonts_list = isset($theme_data[$theme_key]->info['default_fonts']) ? $theme_data[$theme_key]->info['default_fonts'] : array();

  $use_base_font = 0;
  foreach ($fonts_arr as $font_setting_key => $value) {
    $use_base_font++;
    $key = $value['key'];
    $title = $value['title'];
    $form['nucleus']['typography'][$key . "_wrapper"] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('typo-element-wrapper')),
    );

    $form['nucleus']['typography'][$key . "_wrapper"]['typo_fonts'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('typo-fonts')),
    );

    $form['nucleus']['typography'][$key . "_wrapper"]['typo_fonts'][$key] = array(
      '#type' => 'select',
      '#title' => t($title),
      '#states' => array(
        'visible' => array(
          'select[name="' . $key . '_type"]' => array(
            'value' => '',
          ),
        ),
      ),
      '#default_value' => theme_get_setting($key),
      '#options' => nucleus_get_font_settings_options($default_fonts_list, $font_setting_key . "-", $use_base_font),
    );

    $form['nucleus']['typography'][$key . "_wrapper"]['typo_fonts'][$key . "_gwf"] = array(
      '#type' => 'textfield',
      '#title' => t($title),
      '#states' => array(
        'visible' => array(
          'select[name="' . $key . '_type"]' => array(
            'value' => 'gwf',
          ),
        ),
      ),
      '#default_value' => theme_get_setting($key . "_gwf"),
    );

    $form['nucleus']['typography'][$key . "_wrapper"]['typo_fonts'][$key . "_custom"] = array(
      '#type' => 'textfield',
      '#title' => t($title),
      '#states' => array(
        'visible' => array(
          'select[name="' . $key . '_type"]' => array(
            'value' => 'custom',
          ),
        ),
      ),
      '#default_value' => theme_get_setting($key . "_custom"),
    );

    $form['nucleus']['typography'][$key . "_wrapper"]['typo_type'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('typo-type')),
    );

    $form['nucleus']['typography'][$key . "_wrapper"]['typo_type'][$key . "_type"] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('Default list'),
        'gwf' => t('Google webfont'),
        'custom' => t('Custom font'),
      ),
      '#default_value' => theme_get_setting($key . "_type"),
    );
  }

  $form['nucleus']['typography']["font_size_wrapper"] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('typo-element-wrapper')),
  );

  $form['nucleus']['typography']["font_size_wrapper"]['font_size'] = array(
    '#type' => 'select',
    '#title' => t('Base Font Size'),
    '#default_value' => theme_get_setting('font_size'),
    '#description' => t('This sets a base font-size on the body element - all text will scale relative to this value.'),
    '#options' => array(
      'fs-smallest' => t('Smallest'),
      'fs-small'    => t('Small'),
      'fs-medium'   => t('Medium'),
      'fs-large'    => t('Large'),
      'fs-largest'  => t('Largest'),
    ),
  );

  // Blocks style.
  $form['nucleus']['block_styles'] = nucleus_block_styles_form($form);

  // About Nucleus.
  $form['nucleus']['about_nucleus'] = array(
    '#type' => 'fieldset',
    '#title' => t('About Nucleus'),
    '#weight' => 40,
  );

  $form['nucleus']['about_nucleus']['about_nucleus_wrapper'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('about-nucleus-wrapper')),
  );

  $form['nucleus']['about_nucleus']['about_nucleus_wrapper']['about_nucleus_content'] = array(
    '#markup' => '<iframe width="100%" height="650" scrolling="no" class="nucleus_frame" frameborder="0" src="http://themebrain.com/static/about/"></iframe>',
  );

  $form['theme_settings']['#collapsible'] = TRUE;
  $form['theme_settings']['#collapsed'] = TRUE;
  $form['logo']['#collapsible'] = TRUE;
  $form['logo']['#collapsed'] = TRUE;
  $form['favicon']['#collapsible'] = TRUE;
  $form['favicon']['#collapsed'] = TRUE;

  $form['theme_settings']['toggle_logo']['#default_value'] = theme_get_setting('toggle_logo');
  $form['theme_settings']['toggle_name']['#default_value'] = theme_get_setting('toggle_name');
  $form['theme_settings']['toggle_slogan']['#default_value'] = theme_get_setting('toggle_slogan');
  $form['theme_settings']['toggle_node_user_picture']['#default_value'] = theme_get_setting('toggle_node_user_picture');
  $form['theme_settings']['toggle_comment_user_picture']['#default_value'] = theme_get_setting('toggle_comment_user_picture');
  $form['theme_settings']['toggle_comment_user_verification']['#default_value'] = theme_get_setting('toggle_comment_user_verification');
  $form['theme_settings']['toggle_favicon']['#default_value'] = theme_get_setting('toggle_favicon');
  $form['theme_settings']['toggle_secondary_menu']['#default_value'] = theme_get_setting('toggle_secondary_menu');

  $form['logo']['default_logo']['#default_value'] = theme_get_setting('default_logo');
  $form['logo']['settings']['logo_path']['#default_value'] = theme_get_setting('logo_path');
  $form['favicon']['default_favicon']['#default_value'] = theme_get_setting('default_favicon');
  $form['favicon']['settings']['favicon_path']['#default_value'] = theme_get_setting('favicon_path');

  $form['nucleus']['nucleus_use_default_settings'] = array(
    '#type' => 'hidden',
    '#default_value' => 0,
  );

  $form['actions']['nucleus_use_default_settings_wrapper'] = array(
    '#markup' => '<input type="submit" value="Reset default settings" class="form-submit form-reset" onclick="return Drupal.Nucleus.nucleusOnClickResetDefaultSettings();">',
  );
}
