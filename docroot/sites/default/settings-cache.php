<?php

/**
 * Cache: Constants
 */
define('CACHE_DRUPAL_FAST',       FALSE); // Fast loading (TRUE, FALSE)
define('CACHE_DRUPAL_CONF',       FALSE); // Configuration (TRUE, FALSE)
define('CACHE_BACKEND_DEFAULTS',  TRUE);  // Backend defaults (TRUE, FALSE)
define('CACHE_BACKEND_DATABASE',  FALSE); // Database backend (TRUE, FALSE)
define('CACHE_BACKEND_MEMCACHE',  TRUE);  // Memcache backend (TRUE, FALSE)

/**
 * Cache: Fast loading
 */
if (CACHE_DRUPAL_FAST) {
$conf['page_cache_invoke_hooks']      = FALSE;  // Load bootstrap (TRUE, FALSE)
$conf['page_cache_without_database']  = TRUE;   // Avoid database (TRUE, FALSE)
}

/**
 * Cache: Configuration
 */
if (CACHE_DRUPAL_CONF) {
$conf['cache']                  = TRUE;   // Page caching (TRUE, FALSE)
$conf['block_cache']            = TRUE;   // Block caching (TRUE, FALSE)
$conf['cache_lifetime']         = 900;    // Minimum life time (0-... seconds, 900s = 15m)
$conf['page_cache_maximum_age'] = 604800; // Maximum life time (0-... seconds, 3600s = 1h)
$conf['page_compression']       = TRUE;   // Page compression (TRUE, FALSE)
$conf['preprocess_css']         = TRUE;   // Agregate CSS (TRUE, FALSE)
$conf['preprocess_js']          = TRUE;   // Agregate JavaScript (TRUE, FALSE)
}

/**
 * Cache: Defaults
 */
if (CACHE_BACKEND_DEFAULTS) {
/* default cache backend */
$conf['cache_class_cache_form']   = 'DrupalDatabaseCache';
$conf['cache_class_cache_update'] = 'DrupalDatabaseCache';
}

/**
 * Cache: Database
 */
if (CACHE_BACKEND_DATABASE) {
/* default cache backend */
$conf['cache_default_class'] = 'DrupalDatabaseCache';
}

/**
 * Cache: Memcache Storage
 */
if (CACHE_BACKEND_MEMCACHE) {
/* drupal backends */
$conf['cache_backends'][] = 'sites/all/modules/contrib/memcache_storage/memcache_storage.inc';
#$conf['lock_inc']         = 'sites/all/modules/contrib/memcache_storage/includes/lock.inc';
#$conf['session_inc']      = 'sites/all/modules/contrib/memcache_storage/includes/session.inc';
/* module configuration */
$conf['memcache_storage_debug'] = FALSE;                        // Debug mode (TRUE, FALSE)
$conf['memcache_storage_key_prefix'] = 'drupalpt_';             // Key prefix (mysite)
$conf['memcache_storage_wildcard_invalidate'] = 604800;         // Wildcard expiration (60 * 60 * 24 * 7 = 7 days)
$conf['memcache_storage_page_cache_custom_expiration'] = TRUE;  // Custom page cache expiration for Page Cache submodule (TRUE, FALSE)
$conf['memcache_storage_page_cache_expire'] = 0;                // Page cache expiration for Page Cache submodule (0 = never expire)
$conf['memcache_storage_persistent_connection'] = FALSE;        // Persistent connection (TRUE, FALSE)
/* backend configuration */
$conf['memcache_extension'] = 'Memcache';                       // Interface (Memcache, Memcached)
if ($conf['memcache_extension'] == 'Memcached') {
  $conf['memcache_options'] = array(
    Memcached::OPT_TCP_NODELAY => TRUE,                                 // No delay on TCP (TRUE, FALSE)
    Memcached::OPT_BINARY_PROTOCOL => TRUE,                             // Binary protocol (TRUE, FALSE)
    Memcached::OPT_COMPRESSION => FALSE,                                // Compression (TRUE, FALSE)
    Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,  // Consistent distribution
  );
}
/* backend hosts */
$conf['memcache_servers']['127.0.0.1:11211'] = 'default';
/* backend bins */
$conf['memcache_bins']['cache']        = 'default';
$conf['memcache_bins']['cache_form']   = 'database';
$conf['memcache_bins']['cache_update'] = 'database';
/* default cache backend */
$conf['cache_default_class'] = 'MemcacheStorage';
}

