<?php

/**
 * @file
 * Provides a simple time-based caching option for panel panes.
 */

// Plugin definition.
$plugin = array(
  'title' => t('Rules-based cache'),
  'description' => t('Use rules and Cache Actions to determine when to clear the cache.'),
  'cache get' => 'cache_actions_rules_cache_get_cache',
  'cache set' => 'cache_actions_rules_cache_set_cache',
  'cache clear' => 'cache_actions_rules_cache_clear_cache',
  'cache clear pane' => 'cache_actions_rules_cache_clear_pane_cache',
  'settings form' => 'cache_actions_rules_cache_settings_form',
  'settings form validate' => 'cache_actions_rules_cache_settings_form_validate',
  'settings form submit' => 'cache_actions_rules_cache_settings_form_submit',
  'defaults' => array(
    'granularity' => 'none',
    'language' => 1,
    'language_content' => 1,
    'cache_key' => '',
    'substitute' => 0,
  ),
);

/**
 * Get cached content.
 */
function cache_actions_rules_cache_get_cache($conf, $display, $args, $contexts, $pane = NULL) {
  if (!empty($conf['new'])) {
    $cid = cache_actions_rules_cache_get_id($conf, $display, $args, $contexts, $pane);
    $cache = cache_get($cid, 'cache');
    if (!$cache) {
      return FALSE;
    }
    return $cache->data;
  }
  $message = 'The panel pane %pane is using the old cache actions caching mechanism,
      and won\'t be cached until you resave it.';
  $parameters = array('%pane' => $pane->subtype);
  watchdog('cache_actions', $message, $parameters, WATCHDOG_WARNING);
  // Be very verbose for people who can control panels.
  if (user_access('use panels caching features') && user_access('administer panels layouts')) {
    drupal_set_message(t($message, $parameters), 'warning');
  }
}

/**
 * Set cached content.
 */
function cache_actions_rules_cache_set_cache($conf, $content, $display, $args, $contexts, $pane = NULL) {
  if (!empty($conf['new'])) {
    $cid = cache_actions_rules_cache_get_id($conf, $display, $args, $contexts, $pane);
    cache_set($cid, $content);
  }
}

/**
 * Clear cached content.
 *
 * @param stdClass $display
 *   The display object. If this object has a property named clear_pane, then
 *   that pane will be cleared.
 */
function cache_actions_rules_cache_clear_cache($display) {
  $cid = 'rc';
  // Add some text if this is a pane, so that we don't clear all panes when
  // we clear a panel pane.
  if (isset($display->clear_pane)) {
    $cid .= ':p';
  }
  if (is_numeric($display->did) && $display->did) {
    $cid .= ':' . $display->did;
  }
  else {
    // Add the cache key if this is an in-code display.
    $cid .= ':' . $display->cache_key;
  }
  // If this is a mini panel then we have the owner property. Let's
  // use the machine name for those.
  if (isset($display->owner->name)) {
    $cid .= ':' . $display->owner->name;
  }
  // If we have a pane specified, then append that to the key as well.
  if (isset($display->clear_pane)) {
    $cid .= ':' . $display->clear_pane->pid;
  }
  cache_clear_all($cid, 'cache', TRUE);
}

/**
 * Figure out an id for our cache based upon input and settings.
 */
function cache_actions_rules_cache_get_id($conf, $display, $args, $contexts, $pane) {
  $id = $conf['cache_key'];
  if (!empty($display->context) && !empty($conf['substitute'])) {
    $id = ctools_context_keyword_substitute($id, array(), $display->context);
  }
  if (user_access('view pane admin links')) {
    $id .= ':adm';
  }
  switch ($conf['granularity']) {
    case 'args':
      foreach ($args as $arg) {
        // Arguments can be many things, but we only consider the ones that are
        // string values.
        if (is_string($arg)) {
          $id .= ':' . $arg;
        }
      }
      break;

    case 'context':
      if (!is_array($contexts)) {
        $contexts = array($contexts);
      }
      foreach ($contexts as $context) {
        if (isset($context->argument)) {
          $id .= ':' . $context->argument;
        }
      }
      break;
  }
  // Add cache for individual language.
  if ($conf['language']) {
    global $language;
    $id .= ':' . $language->language;
  }
  // Add cache for individual content language.
  if (!empty($conf['language_content'])) {
    global $language_content;
    $id .= ':' . $language_content->language;
  }
  $cache = new stdClass();
  $cache->conf = $conf;
  $cache->display = $display;
  $cache->args = $args;
  $cache->contexts = $contexts;
  $cache->pane = $pane;
  $cache->key = $id;

  // Let other modules alter the cache key.
  drupal_alter('cache_actions_panels_cache_key', $cache);
  return $cache->key;
}

/**
 * Provide a form for the cache settings.
 *
 * @param array $conf
 *   The current configuration.
 * @param stdClass $display
 *   The display object
 * @param string $pid
 *   The pane pid.
 */
function cache_actions_rules_cache_settings_form(&$conf, $display, $pid) {
  $form = array();

  $form['granularity'] = array(
    '#title' => t('Granularity'),
    '#type' => 'select',
    '#options' => array(
      'args' => t('Arguments'),
      'context' => t('Context'),
      'none' => t('None'),
    ),
    '#description' => t('If "arguments" are selected, this content will be cached per individual argument to the entire display; if "contexts" are selected, this content will be cached per unique context in the pane or display; if "neither" there will be only one cache for this pane.'),
    '#default_value' => $conf['granularity'],
  );
  $form['language'] = array(
    '#title' => t('Cache per interface language'),
    '#type' => 'checkbox',
    '#default_value' => $conf['language'],
    '#description' => t('Select this if you want to cache content per language'),
  );
  $form['language_content'] = array(
    '#title' => t('Cache per content language'),
    '#type' => 'checkbox',
    '#default_value' => $conf['language_content'],
    '#description' => t('Select this if you want to cache content per language'),
  );
  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  // If we encounter an old value, we should convert it to a unique id instead.
  if (!empty($conf['cache_key']) && $conf['cache_key'] == $display->cache_key) {
    $conf['cache_key'] = uniqid();
  }
  $form['advanced']['cache_key'] = array(
    '#type' => 'textfield',
    '#required' => TRUE,
    '#title' => t('Cache key'),
    '#default_value' => empty($conf['cache_key']) ? uniqid() : $conf['cache_key'],
    '#description' => t('This is the key that will be used.
      The granularity and language settings will be appended after this key.
      You can use substitutions here.'),
  );
  $form['advanced']['substitute'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use context keywords'),
    '#description' => t('If checked, context keywords will be substituted in this content.
      This is not compatible with the standard cache actions clear pane action!'),
    '#default_value' => !empty($conf['substitute']),
  );
  $form['advanced']['contexts'] = array(
    '#title' => t('Substitutions'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $rows = array();

  foreach ($display->context as $context) {
    foreach (ctools_context_get_converters('%' . check_plain($context->keyword) . ':', $context) as $keyword => $title) {
      $rows[] = array(
        check_plain($keyword),
        t('@identifier: @title', array('@title' => $title, '@identifier' => $context->identifier)),
      );
    }
  }
  $header = array(t('Keyword'), t('Value'));
  $form['advanced']['contexts']['context'] = array('#markup' => theme('table', array('header' => $header, 'rows' => $rows)));
  // Store the old cache key so we can look up any rules that needs changing.
  $form['old_cache_key'] = array(
    '#type' => 'value',
    '#value' => $conf['cache_key'],
  );
  // Store the display cache key so we can use it in the submit callback.
  $form['display_cache_key'] = array(
    '#type' => 'value',
    '#value' => $display->cache_key,
  );
  return $form;
}

/**
 * Validates the cache settings.
 */
function cache_actions_rules_cache_settings_form_validate($form, $values) {
  $args = func_get_args();
  $display = $form['display']['#value'];
  $pid = $form['pid']['#value'];
  foreach ($display->content as $pane) {
    if (!empty($pane->cache['method']) && $pane->cache['method'] == 'rules' && $pid != $pane->pid && $pane->cache['settings']['cache_key'] == $values['advanced']['cache_key']) {
      form_set_error('cache_key', t('The cache key must be unique'));
    }
  }
}

/**
 * Saves the cache settings.
 *
 * @param array $values
 *   The values to store.
 */
function cache_actions_rules_cache_settings_form_submit(&$values) {
  $values['cache_key'] = $values['advanced']['cache_key'];
  $values['substitute'] = $values['advanced']['substitute'];
  // Add an indicator that this is stored with the new caching mechanism.
  $values['new'] = TRUE;

  // Store cache key updates in a variable,
  // so that we can update any rules that may be using the old cache key.
  if (!empty($values['old_cache_key']) && $values['old_cache_key'] != $values['cache_key']) {
    $cache_actions_updated_panes = variable_get('cache_actions_updated_panes', array());
    // Store the oldest cache key for this session under the newest key.
    if (isset($cache_actions_updated_panes[$values['display_cache_key']][$values['old_cache_key']])) {
      $cache_actions_updated_panes[$values['display_cache_key']][$values['cache_key']] = $cache_actions_updated_panes[$values['display_cache_key']][$values['old_cache_key']];
      unset($cache_actions_updated_panes[$values['display_cache_key']][$values['old_cache_key']]);
    }
    else {
      $cache_actions_updated_panes[$values['display_cache_key']][$values['cache_key']] = $values['old_cache_key'];
    }
    variable_set('cache_actions_updated_panes', $cache_actions_updated_panes);
  }
  unset($values['advanced']['cache_key'], $values['advanced']['substitute'], $values['old_cache_key'], $values['display_cache_key']);
}
