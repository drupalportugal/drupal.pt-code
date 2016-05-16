<?php

namespace Drupal\memcache_storage;

use Drupal\Core\Site\Settings;

class DrupalMemcachedUtils {

  /**
   * Parses the string of memcached server.
   *
   * @param $memcached_server string
   *   Examples of server string:
   *   - unix:///path/to/memcached.socket
   *   - 127.0.0.1:12111
   *
   * @return array
   *   Processed host and port values.
   */
  public static function parseServerInfo($memcached_server) {
    list($host, $port) = explode(':', $memcached_server);

    // Support unix sockets in the format 'unix:///path/to/socket'.
    if ($host == 'unix') {

      // PECL Memcache requires string format like 'unix:///path/to/socket' to
      // establish a connection, while PECL Memcached requires only
      // '/path/to/socket' string for the same purpose.
      $pecl_extension = self::getPeclExtension();
      $host = $pecl_extension == 'Memcached' ? substr($memcached_server, 7) : $memcached_server;

      // For unix sockets port is always 0.
      $port = 0;
    }

    return [$host, $port];
  }

  /**
   * Returns the name of PECL extension to use.
   */
  public static function getPeclExtension() {
    $settings = Settings::get('memcache_storage');
    if (!empty($settings['extension'])) {
      $extension = $settings['extension'];
    }

    // If no extension is set, default to PECL Memcached.
    elseif (class_exists('Memcached')) {
      $extension = 'Memcached';
    }
    elseif (class_exists('Memcache')) {
      $extension = 'Memcache';
    }

    return !empty($extension) ? $extension : FALSE;
  }

  /**
   * Register shutdown callable to log a message.
   * We need to do this on shutdown because in the initial phase
   * loggers might be not available.
   *
   * @param $level
   *   \Psr\Log\LogLevel constant
   *
   * @param $message
   *   A message which should be logged.
   *
   * @param $context
   *   An array with additional information about the event - uid, link, etc.
   */
  public static function log($level, $message, array $context = []) {
    \register_shutdown_function(array('Drupal\memcache_storage\DrupalMemcachedUtils', 'logShutdown'), $level, $message, $context);
  }

  /**
   * Logs a message on the shutdown.
   *
   * @param $level
   *   \Psr\Log\LogLevel constant
   *
   * @param $message
   *   A message which should be logged.
   *
   * @param $context
   *   An array with additional information about the event - uid, link, etc.
   */
  public static function logShutdown($level, $message, array $context = []) {
    \Drupal::logger('memcache_storage')->log($level, $message, $context);
  }

}
