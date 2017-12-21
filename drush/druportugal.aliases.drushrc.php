<?php

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site druportugal, environment dev.
$aliases['dev'] = array(
  'root' => '/var/www/html/druportugal.dev/docroot',
  'ac-site' => 'druportugal',
  'ac-env' => 'dev',
  'ac-realm' => 'prod',
  'uri' => 'druportugaldev.prod.acquia-sites.com',
  'remote-host' => 'druportugaldev.ssh.prod.acquia-sites.com',
  'remote-user' => 'druportugal.dev',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['dev.livedev'] = array(
  'parent' => '@druportugal.dev',
  'root' => '/mnt/gfs/druportugal.dev/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site druportugal, environment prod.
$aliases['prod'] = array(
  'root' => '/var/www/html/druportugal.prod/docroot',
  'ac-site' => 'druportugal',
  'ac-env' => 'prod',
  'ac-realm' => 'prod',
  'uri' => 'druportugal.prod.acquia-sites.com',
  'remote-host' => 'druportugal.ssh.prod.acquia-sites.com',
  'remote-user' => 'druportugal.prod',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['prod.livedev'] = array(
  'parent' => '@druportugal.prod',
  'root' => '/mnt/gfs/druportugal.prod/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site druportugal, environment test.
$aliases['test'] = array(
  'root' => '/var/www/html/druportugal.test/docroot',
  'ac-site' => 'druportugal',
  'ac-env' => 'test',
  'ac-realm' => 'prod',
  'uri' => 'druportugalstg.prod.acquia-sites.com',
  'remote-host' => 'druportugalstg.ssh.prod.acquia-sites.com',
  'remote-user' => 'druportugal.test',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['test.livedev'] = array(
  'parent' => '@druportugal.test',
  'root' => '/mnt/gfs/druportugal.test/livedev/docroot',
);
