<?php

/**
 * @file
 * This file provides the rules integration for this module.
 */

/**
 * Implements hook_rules_action_info().
 */
function cache_actions_rules_action_info() {
  // Actions that works for everyone.
  $actions = array(
    'cache_actions_action_clear_cache' => array(
      'label' => t('Clear cache bins'),
      'parameter' => array(
        'tables' => array(
          'type' => 'list<text>',
          'label' => t('Cache Bins'),
          'save' => TRUE,
          'options list' => 'cache_actions_get_cache_bins',
          'restriction' => 'input',
        ),
      ),
      'group' => t('Cache Actions'),
      'base' => 'cache_actions_action_clear_cache',
    ),
    'cache_actions_action_clear_cache_cid' => array(
      'label' => t('Clear a specific cache cid'),
      'parameter' => array(
        'bin' => array(
          'type' => 'text',
          'label' => t('Cache bin'),
          'description' => t('The cache table where the cid is'),
          'options list' => 'cache_actions_get_cache_bins',
          'restriction' => 'input',
          'save' => TRUE,
        ),
        'cid' => array(
          'type' => 'text',
          'label' => t('Cache key'),
          'description' => t('The key to clear'),
          'save' => TRUE,
        ),
        'wildcard' => array(
          'type' => 'boolean',
          'label' => t('Use wildcard'),
          'description' => t('Use wildcards. This means you will be clearing all cache keys that partially matches.'),
          'restriction' => 'input',
          'save' => TRUE,
        ),
      ),
      'group' => t('Cache Actions'),
      'base' => 'cache_actions_action_clear_cache_cid',
    ),
    'cache_actions_action_clear_css_js_cache' => array(
      'label' => t('Clear the css and js cache'),
      'group' => t('Cache Actions'),
      'base' => 'cache_actions_action_clear_css_js_cache',
    ),
  );

  // If the Views module is available, then we can clear the cache of
  // individual views.
  if (module_exists('views')) {
    $actions['cache_actions_action_clear_views_cache'] = array(
      'label' => t('Clear the cache for specific views'),
      'parameter' => array(
        'view' => array(
          'type' => 'list<text>',
          'label' => t('Views'),
          'description' => t('The cache of the selected views will be cleared.'),
          'options list' => '_cache_actions_get_views_list',
          'save' => TRUE,
          'restriction' => 'input',
        ),
      ),
      'group' => t('Cache Actions'),
      'base' => 'cache_actions_action_clear_views_cache',
    );

    $actions['cache_actions_action_clear_views_display_cache'] = array(
      'label' => t('Clear the cache for views displays'),
      'parameter' => array(
        'displays' => array(
          'type' => 'list<text>',
          'label' => t('Displays'),
          'description' => t('The cache of the selected displays will be cleared'),
          'options list' => '_cache_actions_get_views_displays',
          'restriction' => 'input',
          'save' => TRUE,
        ),
      ),
      'group' => t('Cache Actions'),
      'base' => 'cache_actions_action_clear_views_display_cache',
    );
  }

  // If the page manager module is available, then we can clear panels stuff.
  if (module_exists('page_manager')) {
    $actions['cache_actions_action_clear_panels_pane_cache'] = array(
      'label' => t('Clear the cache for panel page panes'),
      'parameter' => array(
        'panes' => array(
          'type' => 'list<text>',
          'label' => t('Panes'),
          'description' => t('Specify the panes to invalidate here.'),
          'options list' => '_cache_actions_get_panes',
          'restriction' => 'input',
          'save' => TRUE,
        ),
      ),
      'group' => t('Cache Actions'),
      'base' => '_cache_actions_clear_panels_cache',
    );
  }

  if (module_exists('panels_mini')) {
    $actions['cache_actions_action_clear_panels_mini_pane_cache'] = array(
      'label' => t('Clear the cache for mini panel panes'),
      'parameter' => array(
        'panes' => array(
          'type' => 'list<text>',
          'label' => t('Panel panes'),
          'options list' => '_cache_actions_get_mini_panel_panes',
          'description' => t('Specify the mini panel panes you want to invalidate.'),
          'save' => TRUE,
          'restriction' => 'input',
        ),
      ),
      'group' => t('Cache Actions'),
      'base' => '_cache_actions_clear_panels_cache',
    );
  }
  return $actions;
}

/**
 * Get all existing handlers from page manager.
 *
 * This is the ugliest function in the whole module =)
 *
 * @return array
 *   The handlers.
 */
function _cache_actions_get_panels_handlers() {
  $available_handlers = array();
  // First, get all tasks. This corresponds to all types of page manager pages
  // that can be used, for for instance pages, node view, node edit...
  $tasks = page_manager_get_tasks();
  foreach ($tasks as $task) {
    // Subtasks are tasks that are created under the primary tasks, for instance
    // a custom page the user has created.
    $subtasks = page_manager_get_task_subtasks($task);
    // If we have subtasks, then that's what we're after.
    if (count($subtasks)) {
      foreach ($subtasks as $subtask) {
        // Subtasks have handlers. These can for instance correspond to a panel
        // variant.
        $handlers = page_manager_load_task_handlers($task, $subtask['name']);
        foreach ($handlers as $handler) {
          // Handlers have plugins, in this case we need to get the plugin for
          // this handler.
          $plugin = page_manager_get_task_handler($handler->handler);
          $title = page_manager_get_handler_title($plugin, $handler, $task, $subtask['name']);
          $key = 'task:' . $handler->name . ':' . $handler->task;
          // Fetch available panes.
          $handler_panes = _cache_actions_load_panes($handler, $title);
          foreach ($handler_panes as $pane_key => $handler_pane) {
            $available_handlers[$title][$pane_key] = $handler_pane;
          }
        }
      }
    }
    else {
      // Otherwise let's use the task.
      $handlers = page_manager_load_task_handlers($task);
      if (count($handlers)) {
        foreach ($handlers as $handler) {
          $plugin = page_manager_get_task_handler($handler->handler);
          $title = page_manager_get_handler_title($plugin, $handler, $task, $task['name']);
          // If not, then we have an in-code display. Save off the name, so we
          // can get it.
          // Fetch available panes.
          $handler_panes = _cache_actions_load_panes($handler, $title);
          foreach ($handler_panes as $pane_key => $handler_pane) {
            $available_handlers[$handler->task . ': ' . $title][$pane_key] = $handler_pane;
          }
        }
      }
    }
  }
  // Otherwise just return the handlers.
  return $available_handlers;
}

/**
 * Get Panel Panes.
 *
 * @return array
 *   Panel panels.
 */
function _cache_actions_get_panes() {
  return _cache_actions_get_panels_handlers();
}

/**
 * Get Mini Panel Panes.
 *
 * @return array
 *   Mini Panel panels.
 */
function _cache_actions_get_mini_panel_panes() {
  ctools_include('plugins', 'panels');
  ctools_include('content');
  $available_panes = array();
  $mini_panels = panels_mini_load_all();
  foreach ($mini_panels as $mini_panel) {
    foreach ($mini_panel->display->content as $pane) {
      // The panes must have rule-based caching on in order for invalidation
      // to work properly.
      if (isset($pane->cache['method']) && $pane->cache['method'] == 'rules') {
        $pane_title = ctools_content_admin_title($pane->type, $pane->subtype, $pane->configuration, $display->context);
        $available_panes[$mini_panel->name][$pane->cache['settings']['cache_key']] = $pane_title;
      }
    }
  }
  return $available_panes;
}

/**
 * Load the panes configured to be handled by rules.
 *
 * @param stdClass $handler
 *   Panel handler.
 *
 * @return array
 *   Available panels.
 */
function _cache_actions_load_panes($handler) {
  ctools_include('plugins', 'panels');
  ctools_include('content');

  $available_panes = array();
  if (isset($handler->conf['did'])) {
    $display = panels_load_display($handler->conf['did']);
  }
  else {
    $display = $handler->conf['display'];
  }
  if (isset($display->content)) {
    foreach ($display->content as $pane) {
      if (isset($pane->cache['method']) && $pane->cache['method'] == 'rules'
        && !empty($pane->cache['settings']['new']) && empty($pane->cache['settings']['substitute'])) {
        $pane_title = ctools_content_admin_title($pane->type, $pane->subtype, $pane->configuration, $display->context);
        $available_panes[$pane->cache['settings']['cache_key']] = $pane_title;
      }
    }
  }
  return $available_panes;
}

/**
 * Flush cached panels panes
 *
 * The flush uses wildcards.
 *
 * @param array $panes
 *   List of pane caching ids to flush.
 */
function _cache_actions_clear_panels_cache($panes) {
  foreach ($panes as $pane) {
    cache_clear_all($pane, 'cache', TRUE);
  }
  return FALSE;
}

/**
 * Get a a keyed array of views with machine name as key and human readable name
 * as value.
 *
 * @return array
 *   An array of views names.
 */
function _cache_actions_get_views_list() {
  $views = views_get_all_views();
  $views_names = array();
  foreach ($views as $view) {
    $views_names[$view->name] = $view->name;
  }
  return $views_names;
}

/**
 * Get all views and their displays. This is a callback
 * for an options list.
 *
 * @return array
 *   List of all views and their displays.
 */
function _cache_actions_get_views_displays() {
  $views = views_get_all_views();
  $displays = array();
  foreach ($views as $view) {
    $displays[$view->name] = array();
    foreach ($view->display as $display) {
      // Only list views that actually is shown.
      if ($display->id != 'default') {
        $displays[$view->name][$view->name . ':' . $display->id] = (!empty($display->title) ? $display->title : $display->id);
      }
    }
  }
  return $displays;
}

/**
 * This action clears the cache of a specific view.
 *
 * @param array $views
 *   The views to clear.
 *
 * @return VOID|FALSE
 *   Returns false if views is disabled.
 */
function cache_actions_action_clear_views_cache($views) {
  if (module_exists('views')) {
    views_include_handlers();
    module_load_include('inc', 'views', 'plugins/views_plugin_cache');
    // Is this an array?
    if (is_array($views)) {
      foreach ($views as $view) {
        _cache_actions_clear_view($view);
      }
    }
    else {
      // Otherwise it's probably an old rule from 1.x, let's just handle that.
      _cache_actions_clear_view($views);
    }
  }
  return FALSE;
}

/**
 * This action clears the cache of a specific view display.
 *
 * @param array $views
 *   The views to clear.
 *
 * @return VOID|FALSE
 *   Returns false if views is disabled.
 */
function cache_actions_action_clear_views_display_cache($views) {
  if (module_exists('views')) {
    $loaded_views = array();
    views_include_handlers();
    module_load_include('inc', 'views', 'plugins/views_plugin_cache');
    if (is_array($views)) {
      foreach ($views as $entry) {
        list($view_name, $display_id) = explode(':', $entry);
        // Let´s make sure we don't load views unnecessarily.
        if (!isset($loaded_views[$view_name])) {
          $loaded_views[$view_name] = views_get_view($view_name);
        }
        // Check to see that the display has a caching plugin. If it doesn't
        // add the default caching plugin.
        $display = $loaded_views[$view_name]->display[$display_id];
        if (!isset($display->display_options['cache']['type'])) {
          $display->display_options['cache']['type'] = $loaded_views[$view_name]->display['default']->display_options['cache']['type'];
        }
        // And then clear the cache.
        _cache_actions_clear_view_display($loaded_views[$view_name], $display);
      }
    }
  }
  return FALSE;
}

/**
 * Clear out a view and it's displays.
 *
 * @param string $view
 *   The view machine name.
 */
function _cache_actions_clear_view($view) {
  $view = views_get_view($view);
  if (is_object($view)) {
    // Go through all displays and clear the cache.
    foreach ($view->display as $display) {
      // If we don't have our own cache plugin, then we need to copy
      // the cache settings from default.
      if (!isset($display->display_options['cache']) && isset($view->display['default'])) {
        $display->display_options['cache'] = $view->display['default']->display_options['cache'];
      }
      _cache_actions_clear_view_display($view, $display);
    }
  }
  else {
    watchdog('cache_actions', "The view %view doesn't exist.
    You need to look over your rules and see if there's any rules
    involving that view.", array('%view' => $handler), WATCHDOG_ERROR);
  }
}
/**
 * Clear out a specific display in a view.
 *
 * @param view $view
 *   The view to clear the display.
 * @param views_plugin_display $display
 *   The views display to clear.
 */
function _cache_actions_clear_view_display($view, $display) {
  // We use the cache plugin to clear the cache.
  $view->set_display($display->id);
  $cache_plugin = views_get_plugin('cache', $display->display_options['cache']['type']);
  // If we have a cache plugin, then initiate it and flush the cache.
  if (isset($cache_plugin)) {
    $cache_plugin->init($view, $display);
    $cache_plugin->cache_flush();
  }
}
/**
 * This action that clears all cache bins specified.
 *
 * @param array $bins
 *   The bins to be cleared
 *
 * @return FALSE
 *   Return false to avoid problems with rules trying to run save.
 */
function cache_actions_action_clear_cache($bins) {
  $cache_bins = cache_actions_get_cache_bins();
  foreach ($bins as $bin) {
    if (in_array($bin, $cache_bins)) {
      cache_clear_all('*', $bin, TRUE);
    }
  }
  // Return False to avoid problems with rules trying to run save.
  return FALSE;
}

/**
 * This action clears a specific cache sid in a cache bin.
 *
 * @param string $bin
 *   The bin where the cid should reside.
 * @param string $cid
 *   The cache cid to clear.
 * @param boolean $wildcard
 *   Wether or not to use wildcard invalidation.
 *
 * @return FALSE
 *   Return False to avoid problems with rules trying to run save.
 */
function cache_actions_action_clear_cache_cid($bin, $cid, $wildcard) {
  cache_clear_all($cid, $bin, $wildcard);
  return FALSE;
}

/**
 * This action clears the css and js cache.
 *
 * @return FALSE
 *   Return False to avoid problems with rules trying to run save.
 */
function cache_actions_action_clear_css_js_cache() {
  // Change query-strings on css/js files to enforce reload for all users.
  _drupal_flush_css_js();
  drupal_clear_js_cache();
  drupal_clear_css_cache();
  return FALSE;
}
