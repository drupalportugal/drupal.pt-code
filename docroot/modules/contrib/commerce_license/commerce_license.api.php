<?php

/**
 * @file
 * Hooks provided by the commerce_license module.
 */

/**
 * @defgroup commerce_license_api Commerce License API
 * @{
 * Information about the Commerce License API.
 *
 * TODO: Maybe some general background information can be provided here.
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the list of available License Type plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\commerce_license\LicenseTypeManager
 */
function hook_commerce_license_type_info_alter(array &$plugins) {
  $plugins['someplugin']['label'] = t('Better name');
}

/**
 * @} End of "addtogroup hooks".
 */
