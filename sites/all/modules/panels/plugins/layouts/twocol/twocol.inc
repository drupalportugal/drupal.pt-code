<?php
// $Id: twocol.inc,v 1.4 2010/10/11 22:56:01 sdboyer Exp $

// Plugin definition
$plugin = array(
  'title' => t('Two column'),
  'category' => t('Columns: 2'),
  'icon' => 'twocol.png',
  'theme' => 'panels_twocol',
  'css' => 'twocol.css',
  'regions' => array(
    'left' => t('Left side'),
    'right' => t('Right side')
  ),
);
