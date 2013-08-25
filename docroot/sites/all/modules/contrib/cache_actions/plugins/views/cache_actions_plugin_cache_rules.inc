<?php

/**
 * @file
 * This file contains a cache that just implements the views cache plugin.
 */

/**
 * Plugin that caches views infinitely. This is not really
 * that useful unless you want rules to invalidate your cache.
 */
class cache_actions_plugin_cache_rules extends views_plugin_cache {
  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  function summary_title() {
    return t('Rules');
  }

  /**
   * Initialize the plugin.
   *
   * @param view $view
   *   The view object.
   * @param views_plugin_display $display
   *   The display handler.
   */
  function  init(&$view, &$display) {
    parent::init($view, $display);
  }

  /**
   * Since rules will be triggering our cache invalidation,
   * the cache will never expire.
   *
   * @param string $type
   *   Not used in this plugin.
   *
   * @return FALSE
   *   Returns always false.
   */
  function cache_expire($type) {
    return FALSE;
  }

  /**
   * We override the default caching mechanism, since it nukes everything.
   * We are just going to flush the actual display.
   */
  function cache_flush() {
    cache_clear_all($this->view->name . ':' . $this->display->id, $this->table, TRUE);
  }

  /**
   * @see views_object::option_definition()
   */
  function  option_definition() {
    return array();
  }

}
