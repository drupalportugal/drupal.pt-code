<?php
// $Id: googleanalytics.admin.inc,v 1.23 2010/10/01 22:24:16 hass Exp $

/**
 * @file
 * Administrative page callbacks for the googleanalytics module.
 */

/**
 * Implementation of hook_admin_settings() for configuring the module
 */
function googleanalytics_admin_settings_form($form_state) {
  $form['account'] = array(
    '#type' => 'fieldset',
    '#title' => t('General settings'),
  );

  $form['account']['googleanalytics_account'] = array(
    '#title' => t('Web Property ID'),
    '#type' => 'textfield',
    '#default_value' => variable_get('googleanalytics_account', 'UA-'),
    '#size' => 15,
    '#maxlength' => 20,
    '#required' => TRUE,
    '#description' => t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href="@analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href="@webpropertyid">Find more information in the documentation</a>.', array('@analytics' => 'http://www.google.com/analytics/', '@webpropertyid' => 'http://code.google.com/apis/analytics/docs/concepts/gaConceptsAccounts.html#webProperty')),
  );

  // Visibility settings.
  $form['tracking_title'] = array(
    '#type' => 'item',
    '#title' => t('Tracking scope'),
  );
  $form['tracking'] = array(
    '#type' => 'vertical_tabs',
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'googleanalytics') . '/googleanalytics.admin.js'),
    ),
  );

  // Standard tracking configurations.
  $form['tracking']['user_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Users'),
  );
  $t_permission = array('%permission' => t('opt-in or out of tracking'));
  $form['tracking']['user_vis_settings']['googleanalytics_custom'] = array(
    '#type' => 'radios',
    '#title' => t('Allow users to customize tracking on their account page'),
    '#options' => array(
      t('No customization allowed'),
      t('Tracking on by default, users with %permission permission can opt out', $t_permission),
      t('Tracking off by default, users with %permission permission can opt in', $t_permission)
    ),
    '#default_value' => variable_get('googleanalytics_custom', 0),
  );

  // Render the role overview.
  $form['tracking']['role_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles'),
  );

  $role_options = array_map('check_plain', user_roles());
  $form['tracking']['role_vis_settings']['googleanalytics_roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Add tracking for specific roles'),
    '#default_value' => variable_get('googleanalytics_roles', array()),
    '#options' => $role_options,
    '#description' => t('If none are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked.'),
  );

  // Page specific visibility configurations.
  $php_access = user_access('use PHP for tracking visibility');
  $visibility = variable_get('googleanalytics_visibility', 0);
  $pages = variable_get('googleanalytics_pages', '');

  $form['tracking']['page_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Pages'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  if ($visibility == 2 && !$php_access) {
    $form['tracking']['page_vis_settings'] = array();
    $form['tracking']['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
    $form['tracking']['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $pages);
  }
  else {
    $options = array(
      t('Every page except the listed pages'),
      t('The listed pages only')
    );
    $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

    if (module_exists('php') && $php_access) {
      $options[] = t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
      $title = t('Pages or PHP code');
      $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
    }
    else {
      $title = t('Pages');
    }
    $form['tracking']['page_vis_settings']['googleanalytics_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking to specific pages'),
      '#options' => $options,
      '#default_value' => $visibility,
    );
    $form['tracking']['page_vis_settings']['googleanalytics_pages'] = array(
      '#type' => 'textarea',
      '#title' => '<span class="element-invisible">' . $title . '</span>',
      '#default_value' => $pages,
      '#description' => $description,
      '#wysiwyg' => FALSE,
    );
  }

  // Link specific configurations.
  $form['tracking']['linktracking'] = array(
    '#type' => 'fieldset',
    '#title' => t('Links and downloads'),
  );
  $form['tracking']['linktracking']['googleanalytics_trackoutgoing'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track clicks on outgoing links'),
    '#default_value' => variable_get('googleanalytics_trackoutgoing', 1),
  );
  $form['tracking']['linktracking']['googleanalytics_trackmailto'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track clicks on mailto links'),
    '#default_value' => variable_get('googleanalytics_trackmailto', 1),
  );
  $form['tracking']['linktracking']['googleanalytics_trackfiles'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track downloads (clicks on file links) for the following extensions'),
    '#default_value' => variable_get('googleanalytics_trackfiles', 1),
  );
  $form['tracking']['linktracking']['googleanalytics_trackfiles_extensions'] = array(
    '#type' => 'textfield',
    '#default_value' => variable_get('googleanalytics_trackfiles_extensions', GA_TRACKFILES_EXTENSIONS),
    '#description' => t('A file extension list separated by the | character that will be tracked when clicked. Regular expressions are supported. For example: !extensions', array('!extensions' => GA_TRACKFILES_EXTENSIONS)),
    '#maxlength' => 255,
  );

  // Google already have many translations, if not - they display a note to change the language.
  global $language;
  $form['tracking']['search_and_adsense'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search and AdSense'),
  );

  $site_search_dependencies = '<div class="admin-dependencies">';
  $site_search_dependencies .= t('Depends on: !dependencies', array('!dependencies' => (module_exists('search') ? t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => 'Search')) : t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => 'Search')))));
  $site_search_dependencies .= '</div>';

  $form['tracking']['search_and_adsense']['googleanalytics_site_search'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track internal search'),
    '#description' => t('If checked, internal search keywords are tracked. You must configure your Google account to use the internal query parameter <strong>search</strong>. For more information see <a href="@url">How do I set up Site Search for my profile</a>.', array('@url' => 'http://www.google.com/support/analytics/bin/answer.py?hl='. $language->language .'&answer=75817')) . $site_search_dependencies,
    '#default_value' => variable_get('googleanalytics_site_search', FALSE),
    '#disabled' => (module_exists('search') ? FALSE : TRUE),
  );

  $form['tracking']['search_and_adsense']['googleanalytics_trackadsense'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track AdSense ads'),
    '#default_value' => variable_get('googleanalytics_trackadsense', FALSE),
  );

  $profile_enabled = module_exists('profile');
  $form['segmentation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Additional user information'),
    '#collapsible' => TRUE,
  );

  // Compile a list of fields to show.
  $fields = variable_get('googleanalytics_segmentation_default_fields', array('roles' => t('User roles')));
  if ($profile_enabled) {
    $result = db_query('SELECT name, title FROM {profile_field} ORDER BY weight');
    foreach ($result as $record) {
      $fields[$record->name] = $record->title;
    }
  }

  $form['segmentation']['googleanalytics_segmentation'] = array(
    '#type' => 'select',
    '#title' => t('Segment users based on these selected properties'),
    '#description' => t("You can supplement Google Analytics' basic IP address tracking of visitors by segmenting users based on these selected properties.") . (!$profile_enabled ? ' '. t('<a href="@module_list">Enable the profile module</a> to be able to use profile fields for more granular tracking.', array('@module_list' => url('admin/build/modules'))) : '') .' '. t('Section 8.1 of the <a href="@ga_tos">Google Analytics terms of use</a> requires you to make sure you will not associate (or permit any third party to associate) any data gathered from your websites (or such third parties\' websites) with any personally identifying information from any source as part of your use (or such third parties\' use) of the Google Analytics\' service.', array('@ga_tos' => 'http://www.google.com/analytics/en-GB/tos.html')) .' '. t('You can select multiple values.'),
    '#default_value' => variable_get('googleanalytics_segmentation', array()),
    '#options' => $fields,
    '#size' => count($fields)>3 ? 10 : 3,
    '#multiple' => TRUE
  );

  // Advanced feature configurations.
  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['advanced']['googleanalytics_tracker_anonymizeip'] = array(
    '#type' => 'checkbox',
    '#title' => t('Anonymize visitors IP address'),
    '#description' => t('Tell Google Analytics to anonymize the information sent by the tracker objects by removing the last octet of the IP address prior to its storage. Note that this will slightly reduce the accuracy of geographic reporting. In some countries it is not allowed to collect personally identifying information for privacy reasons and this setting may help you to comply with the local laws.'),
    '#default_value' => variable_get('googleanalytics_tracker_anonymizeip', 0),
  );

  $form['advanced']['googleanalytics_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Locally cache tracking code file'),
    '#description' => t("If checked, the tracking code file is retrieved from Google Analytics and cached locally. It is updated daily from Google's servers to ensure updates to tracking code are reflected in the local copy. Do not activate this until after Google Analytics has confirmed that site tracking is working!"),
    '#default_value' => variable_get('googleanalytics_cache', 0),
  );

  // Allow for tracking of the originating node when viewing translation sets.
  if (module_exists('translation')) {
    $form['advanced']['googleanalytics_translation_set'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track translation sets as one unit'),
      '#description' => t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
      '#default_value' => variable_get('googleanalytics_translation_set', 0),
    );
  }

  $form['advanced']['codesnippet'] = array(
    '#type' => 'fieldset',
    '#title' => t('Custom JavaScript code'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('You can add custom Google Analytics <a href="@snippets">code snippets</a> here. These will be added every time tracking is in effect. Before you add your custom code, you should read the <a href="@ga_concepts_overview">Google Analytics Tracking Code - Functional Overview</a> and the <a href="@ga_js_api">Google Analytics Tracking API</a> documentation. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', array('@snippets' => 'http://drupal.org/node/248699', '@ga_concepts_overview' => 'http://code.google.com/apis/analytics/docs/gaConceptsOverview.html', '@ga_js_api' => 'http://code.google.com/apis/analytics/docs/gaJSApi.html'))
  );
  $form['advanced']['codesnippet']['googleanalytics_codesnippet_before'] = array(
    '#type' => 'textarea',
    '#title' => t('Code snippet (before)'),
    '#default_value' => variable_get('googleanalytics_codesnippet_before', ''),
    '#rows' => 5,
    '#wysiwyg' => FALSE,
    '#description' => t("Code in this textarea will be added <strong>before</strong> _gaq.push(['_trackPageview'])."),
  );
  $form['advanced']['codesnippet']['googleanalytics_codesnippet_after'] = array(
    '#type' => 'textarea',
    '#title' => t('Code snippet (after)'),
    '#default_value' => variable_get('googleanalytics_codesnippet_after', ''),
    '#rows' => 5,
    '#wysiwyg' => FALSE,
    '#description' => t("Code in this textarea will be added <strong>after</strong> _gaq.push(['_trackPageview']). This is useful if you'd like to track a site in two accounts."),
  );

  $form['advanced']['googleanalytics_js_scope'] = array(
    '#type' => 'select',
    '#title' => t('JavaScript scope'),
    '#description' => t('Google recommends adding the external JavaScript files to the header for performance reasons.'),
    '#options' => array(
      'footer' => t('Footer'),
      'header' => t('Header'),
    ),
    '#default_value' => variable_get('googleanalytics_js_scope', 'header'),
  );

  return system_settings_form($form);
}

function googleanalytics_admin_settings_form_validate($form, &$form_state) {
  // Trim some text values.
  $form_state['values']['googleanalytics_account'] = trim($form_state['values']['googleanalytics_account']);
  $form_state['values']['googleanalytics_pages'] = trim($form_state['values']['googleanalytics_pages']);
  $form_state['values']['googleanalytics_codesnippet_before'] = trim($form_state['values']['googleanalytics_codesnippet_before']);
  $form_state['values']['googleanalytics_codesnippet_after'] = trim($form_state['values']['googleanalytics_codesnippet_after']);

  if (!preg_match('/^UA-\d{4,}-\d+$/', $form_state['values']['googleanalytics_account'])) {
    form_set_error('googleanalytics_account', t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
  }

  // Clear obsolete local cache if cache has been disabled.
  if (empty($form_state['values']['googleanalytics_cache']) && $form['advanced']['googleanalytics_cache']['#default_value']) {
    googleanalytics_clear_js_cache();
  }

  // This is for the Newbie's who cannot read a text area description.
  if (stristr($form_state['values']['googleanalytics_codesnippet_before'], 'google-analytics.com/ga.js')) {
    form_set_error('googleanalytics_codesnippet_before', t('Do not add the tracker code provided by Google into the javascript code snippets! This module already builds the tracker code based on your Google Analytics account number and settings.'));
  }
  if (stristr($form_state['values']['googleanalytics_codesnippet_after'], 'google-analytics.com/ga.js')) {
    form_set_error('googleanalytics_codesnippet_after', t('Do not add the tracker code provided by Google into the javascript code snippets! This module already builds the tracker code based on your Google Analytics account number and settings.'));
  }
  if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['googleanalytics_codesnippet_before'])) {
    form_set_error('googleanalytics_codesnippet_before', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
  }
  if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['googleanalytics_codesnippet_after'])) {
    form_set_error('googleanalytics_codesnippet_after', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
  }
}
