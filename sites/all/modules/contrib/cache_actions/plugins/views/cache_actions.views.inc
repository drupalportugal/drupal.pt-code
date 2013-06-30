<?php

/**
 * @file
 * This file contains views hooks.
 */

/**
 * Implements hook_views_plugins().
 */
function cache_actions_views_plugins() {
  return array(
    'cache' => array(
      'rules' => array(
        'title' => t('Rules-triggered Cache'),
        'help' => t('Cache This View for an infinite time. This can be used
          when rules are used to invalidate the cache.'),
        'handler' => 'cache_actions_plugin_cache_rules',
        'help topic' => 'cache-rules',
        'path' => drupal_get_path('module', 'cache_actions') . '/plugins/views',
      ),
    ),
  );
}
