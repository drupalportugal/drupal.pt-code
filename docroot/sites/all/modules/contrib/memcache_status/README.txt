Memcache Status
==========

Requirements:
 - Libraries API - http://drupal.org/project/libraries
 - memcache.php - This third-party file is required for APC Status. It may
   already be present on your computer (e.g. in /usr/share/doc/php-apc) or may
   be extracted from the Memcache source (http://pecl.php.net/package/memcache).

Installation:
 - Create a directory named "memcache" somewhere Libraries API can find it,
   e.g. sites/all/libraries/memcache.
 - Rename memcache.php to memcache.php.inc and place it in the memcache
   directory.

   For example:
       $ mkdir -p sites/all/libraries/memcache
       $ cd sites/all/libraries/memcache
       $ curl http://svn.php.net/viewvc/pecl/memcache/trunk/memcache.php?view=co \
              > memcache.php.inc

 - You will also want to remove the $MEMCACHE_SERVERS variable in your 
   memcache.php.inc file. This module will pull this information from your
   configuration settings.

Usage:
 - Log in to your Drupal site as an administrator and visit
   admin/reports/status/memcache.
