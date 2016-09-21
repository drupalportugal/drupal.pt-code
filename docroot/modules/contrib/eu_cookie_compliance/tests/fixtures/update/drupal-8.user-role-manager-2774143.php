<?php

/**
 * @file
 * Contains database additions to drupal-8.eu-cookie-compliance-beta5.minimal.php.gz
 * for testing the upgrade path of https://www.drupal.org/node/2774143.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// A custom role with 'administer EU Cookie Compliance popup' permissions.
$role_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/user.role.testfor2774143.yml'));

// A custom role with 'display EU Cookie Compliance popup' permissions.
$role_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/user.role.secondtestfor2774143.yml'));

foreach ($role_configs as $role_config) {
  $connection->insert('config')
    ->fields(array(
      'collection',
      'name',
      'data',
    ))
    ->values(array(
      'collection' => '',
      'name' => 'user.role.' . $role_config['id'],
      'data' => serialize($role_config),
    ))
    ->execute();
}

// Update the config entity query "index".
$existing_roles = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'config.entity.key_store.user_role')
  ->execute()
  ->fetchField();
$existing_roles = unserialize($existing_roles);

$connection->update('key_value')
  ->fields([
    'value' => serialize(array_merge($existing_roles, ['user.role.testfor2774143', 'user.role.secondtestfor2774143']))
  ])
  ->condition('collection', 'config.entity.key_store.user_role')
  ->condition('name', 'theme:bartik')
  ->execute();
