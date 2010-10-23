<?php
// $Id: onecol.inc,v 1.4 2010/10/11 22:56:01 sdboyer Exp $

/**
 * implementation of hook_panels_layouts()
 */
// Plugin definition
$plugin = array(
  'title' => t('Single column'),
  'category' => t('Columns: 1'),
  'icon' => 'onecol.png',
  'theme' => 'panels_onecol',
  'css' => 'onecol.css',
  'regions' => array('middle' => t('Middle column')),
);
