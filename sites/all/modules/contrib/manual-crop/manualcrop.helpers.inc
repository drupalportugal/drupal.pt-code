<?php
/**
 * @file
 * Helper functions for the Manual Crop module.
 */

/**
 * Returns an array of supported widget types or checks if a type is supported.
 *
 * @param $widget_type
 *   If set, this function will return a boolean indicating if $widget_type
 *   is supported.
 * @param $settings
 *   Only include widgets that support these setting(s).
 *
 * @return
 *   Array of widget types.
 */
function manualcrop_supported_widgets($widget_type = NULL, $settings = array()) {
  // List settings that can differ from widget to widget.
  $optional_settings = array(
    'thumblist',
    'inline_crop',
    'instant_crop',
  );

  // List all widgets and the optional settings they support.
  $widgets = array(
    'image_image' => $optional_settings,
    'media_generic' => array('thumblist'),
  );

  // Make sure $settings contains only valid entries.
  if (!empty($settings)) {
    if (!is_array($settings)) {
      $settings = array($settings);
    }

    $settings = array_intersect($settings, $optional_settings);
  }

  if (empty($settings)) {
    // No settings required.
    $result = array_keys($widgets);
  }
  else {
    // Filter all widgets that don't support the required settings.
    $result = array();
    foreach ($widgets as $name => $widget_settings) {
      if (!count(array_diff($settings, $widget_settings))) {
        $result[] = $name;
      }
    }
  }

  if (!empty($widget_type)) {
    return in_array($widget_type, $result);
  }
  else {
    return $result;
  }
}

/**
 * Returns the default widget settings.
 *
 * @return
 *   Array of default widget settings.
 */
function manualcrop_default_widget_settings() {
  $defaults = array(
    // Enable Manual Crop?
    'manualcrop_enable' => FALSE,
    // Show a list of thubnails instead of a selection list or button?
    'manualcrop_thumblist' => FALSE,
    // Enable inline cropping?
    'manualcrop_inline_crop' => FALSE,
    // Show the crop info (width, height...)?
    'manualcrop_crop_info' => TRUE,
    // Automatically update the preview image?
    'manualcrop_instant_preview' => TRUE,
    // Open the crop tool after uploading?
    'manualcrop_instant_crop' => FALSE,
    // Show a default crop area when opening an uncropped image?
    'manualcrop_default_crop_area' => TRUE,
    // Exclude or include the selected styles?
    'manualcrop_styles_mode' => 'exclude',
    // List of selected styles.
    'manualcrop_styles_list' => array(),
    // List of required crop selections.
    'manualcrop_require_cropping' => array(),
  );

  if (module_exists('insert')) {
    // Filter all styles without a Manual Crop effect?
    $defaults['manualcrop_filter_insert'] = TRUE;
  }

  return $defaults;
}

/**
 * Get the unique javascript crop settings identifier.
 *
 * @param $data
 *   Field instance array (preferred) or file object.
 *
 * @return
 *   Unique javascript crop settings identifier.
 */
function manualcrop_js_identifier($data) {
  if (is_array($data) && !empty($data['field_name'])) {
    return drupal_clean_css_identifier($data['field_name']);
  }
  elseif (is_object($data) && isset($data->fid)) {
    return 'manualcrop-file-' . $data->fid;
  }
  else {
    return 'manualcrop-' . md5(serialize($data));
  }
}

/**
 * Gets the crop area for an image.
 *
 * @param $file
 *   Path to an image file.
 * @param $style_name
 *   Image style machine name, or empty for all styles.
 *
 * @return
 *   An object defining the cropping area with following items:
 *   - "style_name": The machine name of the image style this cropping area applies on.
 *   - "x": An integer representing the top left corner's x-position in pixels.
 *   - "y": An integer representing the top left corner's y-position in pixels.
 *   - "width": An integer representing the width in pixels.
 *   - "height": An integer representing the height in pixels.
 *   If the style machine name was empty an object with all the styles is returned.
 */
function manualcrop_load_crop_selection($file, $style_name = NULL) {
  if (empty($style_name)) {
    $result = db_query('SELECT c.style_name, c.x, c.y, c.width, c.height FROM {manualcrop} c INNER JOIN {file_managed} f ON c.fid = f.fid WHERE f.uri = :uri', array(
      ':uri' => $file,
    ));

    return $result->fetchAll();
  }
  else {
    $result = db_query('SELECT c.x, c.y, c.width, c.height FROM {manualcrop} c INNER JOIN {file_managed} f ON c.fid = f.fid WHERE c.style_name = :name AND f.uri = :uri', array(
      ':name' => $style_name,
      ':uri' => $file,
    ));

    return $result->fetchObject();
  }

  return NULL;
}

/**
 * Returns the styles that have crop settings.
 *
 * @param $include_reuse
 *   Set to TRUE to include styles with a Manual Crop reuse effect.
 *
 * @return
 *   An array of styles with the style name as key and the effect data as value.
 */
function manualcrop_styles_with_crop($include_reuse = FALSE) {
  $hascrop = &drupal_static(__FUNCTION__);

  if (!is_array($hascrop)) {
    $hascrop = array(array(), array());

    foreach (image_styles() as $style_name => $style) {
      if (!empty($style['effects'])) {
        // Check if the first effect is a Manual Crop cropping effect.
        $effect = reset($style['effects']);

        if (_manualcrop_is_own_effect($effect)) {
          $hascrop[1][$style_name] = $effect;

          if (_manualcrop_is_own_effect($effect, TRUE)) {
            $hascrop[0][$style_name] = $effect;
          }
        }
      }
    }
  }

  return $hascrop[(int) $include_reuse];
}

/**
 * Get the list of required image styles from the widget settings.
 *
 * @param $settings
 *   Widget settings array.
 */
function manualcrop_instance_required_styles($settings) {
  // Array of required styles.
  $required = array_values($settings['manualcrop_require_cropping']);

  // Make sure to exclude unavailable styles.
  if (!empty($required) && !empty($settings['manualcrop_styles_list'])) {
    if ($settings['manualcrop_styles_mode'] == 'include') {
      $required = array_intersect($required, array_values($settings['manualcrop_styles_list']));
    }
    else {
      $required = array_diff($required, array_values($settings['manualcrop_styles_list']));
    }
  }

  return $required;
}

/**
 * Update or remove a style name in all Manual Crop field widgets.
 *
 * @param $style_name
 *   Current image style name.
 * @param $new_style_name
 *   New image style name if renamed, a NULL value will remove the style from the settings.
 */
function _manualcrop_field_widget_update_names_in_settings($style_name, $new_style_name = NULL) {
  foreach (field_info_fields() as $field) {
    if ($field['module'] == 'image') {
      foreach ($field['bundles'] as $entity_type => $bundles) {
        foreach ($bundles as $bundle) {
          // Check each instance for processing.
          $instance = field_info_instance($entity_type, $field['field_name'], $bundle);
          $settings = &$instance['widget']['settings'];

          if (manualcrop_supported_widgets($instance['widget']['type']) && (!empty($settings['manualcrop_require_cropping']) || !empty($settings['manualcrop_styles_list']))) {
            $list = array();

            // Add all existing settings to the list.
            if (!empty($settings['manualcrop_require_cropping'])) {
              $list['manualcrop_require_cropping'] = &$settings['manualcrop_require_cropping'];
            }

            if (!empty($settings['manualcrop_styles_list'])) {
              $list['manualcrop_styles_list'] = &$settings['manualcrop_styles_list'];
            }

            // Process all settings.
            foreach ($list as $key => &$item) {
              if (isset($item[$style_name])) {
                unset($item[$style_name]);

                if (!is_null($new_style_name)) {
                  $item[$new_style_name] = $new_style_name;
                }
                elseif (empty($require)) {
                  unset($settings[$key]);
                }
              }
              else {
                // Not processed, so remove it from the list.
                unset($list[$key]);
              }
            }

            if (!empty($list)) {
              // Settings where updated, save the instance.
              field_update_instance($instance);
            }
          }
        }
      }
    }
  }
}

/**
 * Update or remove a style name in all Manual Crop reuse image effects.
 *
 * @param $style_name
 *   Current image style name.
 * @param $new_style_name
 *   New image style name if renamed, a NULL value will remove the effect from the style.
 */
function _manualcrop_reuse_effect_update_names_in_settings($style_name, $new_style_name = NULL) {
  foreach (image_styles() as $style) {
    if (!empty($style['effects'])) {
      // Check if the first effect is a Manual Crop effect.
      $effect = reset($style['effects']);

      // Check if this is a Manual Crop reuse effect that needs an update.
      if (_manualcrop_is_own_effect($effect, FALSE) && $effect['data']['reusestyle'] == $style_name) {
        if (is_null($new_style_name)) {
          image_effect_delete($effect);
        }
        else {
          $effect['data']['reusestyle'] = $new_style_name;
          image_effect_save($effect);
        }
      }
    }
  }
}

/**
 * Transform a style name into a more readable variant.
 *
 * @param $style_name
 *   Image style name.
 *
 * @return
 *   Cleaned-up image style name.
 */
function _manualcrop_image_style_name($style_name) {
  global $language;
  static $custom_strings;

  $langcode = (isset($language->language) ? $language->language : 'en');

  // Load custom string for overriding.
  if (!isset($custom_strings[$langcode])) {
    $custom_strings[$langcode] = variable_get('locale_custom_strings_' . $langcode, array());
  }

  // Get the human readable name from the custom strings or make it ourself.
  if (isset($custom_strings[$langcode]['']['image-style-' . $style_name])) {
    return $custom_strings[$langcode]['']['image-style-' . $style_name];
  }
  else {
    return ucwords(str_replace('_', ' ', $style_name));
  }
}

/**
 * Checks if the effect is a Manual Crop effect.
 *
 * @param $effect
 *   Image style effect information array.
 * @param $crop_effect
 *   Set to TRUE to require a cropping effect or set to FALSE to require the
 *   reuse effect.
 *
 * @return
 *   TRUE if this is a Manual Crop (cropping/reuse) effect, FALSE otherwise.
 */
function _manualcrop_is_own_effect($effect, $crop_effect = NULL) {
  return ($effect['module'] == 'manualcrop' && (is_null($crop_effect) || ($crop_effect ^ ($effect['name'] == 'manualcrop_reuse'))));
}
