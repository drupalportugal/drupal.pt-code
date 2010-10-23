<?php
// $Id: naked.inc,v 1.4 2010/10/11 22:56:01 sdboyer Exp $

/**
 * @file
 * Definition of the 'naked' panel style.
 */

// Plugin definition
$plugin = array(
  'title' => t('No markup at all'),
  'description' => t('Display the pane with no markup, not even a title.'),
  'render pane' => 'panels_naked_style_render_pane',
  'weight' => -5,
);

/**
 * Render callback.
 *
 * @ingroup themeable
 */
function theme_panels_naked_style_render_pane($vars) {
  return $vars['content']->content;
}

