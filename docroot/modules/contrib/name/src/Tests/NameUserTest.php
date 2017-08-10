<?php

/**
 * @file
 * Contains \Drupal\name\Tests\NameUserTest.
 */

namespace Drupal\name\Tests;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the integration with user module.
 *
 * @group name
 */
class NameUserTest extends KernelTestBase {

  public static $modules = array(
    'field',
    'name',
    'user',
    'system'
  );

  /**
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installSchema('system', array('sequences'));

    $this->entityManager = \Drupal::entityManager();
    $this->entityManager->onEntityTypeCreate(\Drupal::entityManager()->getDefinition('user'));
  }

  public function testUserHooks() {
    FieldStorageConfig::create(array(
      'field_name' => 'field_text',
      'type' => 'string',
      'entity_type' => 'user',
    ))->save();
    FieldConfig::create(array(
      'field_name' => 'field_text',
      'type' => 'string',
      'entity_type' => 'user',
      'bundle' => 'user',
    ))->save();
    $this->assertIdentical('', \Drupal::config('name.settings')->get('user_preferred'));

    FieldStorageConfig::create(array(
      'field_name' => 'field_name_test',
      'type' => 'name',
      'entity_type' => 'user',
    ))->save();

    FieldStorageConfig::create(array(
      'field_name' => 'field_name_test2',
      'type' => 'name',
      'entity_type' => 'user',
    ))->save();

    $field = FieldConfig::create(array(
      'field_name' => 'field_name_test',
      'type' => 'name',
      'entity_type' => 'user',
      'bundle' => 'user',
    ));
    $field->save();

    $field2 = FieldConfig::create(array(
      'field_name' => 'field_name_test2',
      'type' => 'name',
      'entity_type' => 'user',
      'bundle' => 'user',
    ));
    $field2->save();

    $this->assertEqual($field->getName(), \Drupal::config('name.settings')->get('user_preferred'));

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', $field2->getName())
      ->save();

    $field2->delete();
    $this->assertEqual('', \Drupal::config('name.settings')->get('user_preferred'));

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', $field->getName())
      ->save();

    $account = User::create(array(
      'name' => 'test',
    ));
    $account->field_name_test[0] = array(
      'given' => 'Max',
      'family' => 'Mustermann'
    );
    $account->save();

    $account = User::load($account->id());
    $this->assertEqual('Max Mustermann', $account->realname);
    $this->assertEqual('Max Mustermann', user_format_name($account));
    $this->assertEqual('test', $account->getUsername());
    $this->assertEqual('Max Mustermann', $account->getDisplayName());
  }

}
