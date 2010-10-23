<?php
// $Id: threecol_33_34_33.inc,v 1.4 2010/10/11 22:56:02 sdboyer Exp $

// Plugin definition
$plugin = array(
  'title' => t('Three column 33/34/33'),
  'category' => t('Columns: 3'),
  'icon' => 'threecol_33_34_33.png',
  'theme' => 'panels_threecol_33_34_33',
  'css' => 'threecol_33_34_33.css',
  'regions' => array(
    'left' => t('Left side'),
    'middle' => t('Middle column'),
    'right' => t('Right side')
   ),
);
