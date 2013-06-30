<?php

/**
 * @file
 * Memcache Status menu callbacks.
 */


/**
 * Menu callback; display the memcache status page.
 */
function memcache_status_memcache() {
  // apc.php is broken without clean URLs.
  if (!variable_get('clean_url', '0')) {
    return t('<a href="@clean-url">Clean URLs</a> are required to access the memcache status page.', array('@clean-url' => url('admin/settings/clean-urls')));
  }

  // Locate apc.php.inc.
  $memcache = libraries_get_path('memcache') . '/memcache.php.inc';
  if (!is_file($memcache)) {
    $output = t('<code>memcache.php.inc</code> not found.  Please download and extract <a href="@memcache-url">memcache</a>, rename <code>memcache.php</code> to <code>memcache.php.inc</code> and place the file in a directory named <code>memcache</code> that <a href="@libraries-api-url">Libraries API</a> can find (i.e., in <code>sites/all/libraries/memcache</code>).', array('@memcache-url' => "http://pecl.php.net/package/memcache", '@libraries-api-url' => "http://drupal.org/project/libraries"));
    return $output;
  }

  // Hacks to get the memcache.php file working.
  global $MEMCACHE_SERVERS, $PHP_SELF;
  $_SERVER['PHP_SELF'] = url('admin/reports/status/memcache');
  $PHP_SELF = $_SERVER['PHP_SELF'];
  $_SERVER['PHP_AUTH_USER'] = 'memcache';
  $_SERVER['PHP_AUTH_PW'] = 'password';
  $MEMCACHE_SERVERS = array_keys(variable_get('memcache_servers', array()));

  // We cannot use module_load_include as otherwise the above variables
  // will not be in the global scope in the included PHP file.
  require_once $memcache;
  exit();
}
