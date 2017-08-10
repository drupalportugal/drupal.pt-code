<?php

/**
 * @file
 * Contains \Drupal\name\Tests\NameOptionsProviderTest.
 */

namespace Drupal\name\Tests;

use Drupal\name\NameOptionsProvider;
use Drupal\simpletest\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests NameOptionsProvider class.
 *
 * @group name
 */
class NameOptionsProviderTest extends KernelTestBase {

  use NameTestTrait;

  public static $modules = array(
    'field',
    'name',
    'taxonomy',
    'entity_test',
    'text'
  );

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var NameOptionsProvider
   */
  protected $optionsProvider;

  protected function setUp() {
    parent::setUp();

    $this->installConfig(self::$modules);
    $this->entityManager = \Drupal::entityManager();
    $this->entityManager->onEntityTypeCreate(\Drupal::entityManager()->getDefinition('taxonomy_term'));

    $this->optionsProvider = \Drupal::service('name.options_provider');
  }

  public function testTitleOptionsFromField() {
    $field = $this->createNameField('field_name_test', 'entity_test', 'entity_test');

    /**
     * @var \Drupal\field\Entity\FieldStorageConfig $field_storage
     */
    $field_storage = $field->getFieldStorageDefinition();
    $settings = $field_storage->getSettings();
    $settings['title_options'] = array(
      '-- --',
      'b',
      'a',
      'c'
    );
    $field_storage->set('settings', $settings);
    $field_storage->save();

    $expected = array(
      '' => '--',
      'b' => 'b',
      'a' => 'a',
      'c' => 'c'
    );
    $this->assertEqual($expected, $this->optionsProvider->getOptions($field, 'title'));

    // Enable sorting.
    $settings['sort_options']['title'] = TRUE;
    $field_storage->set('settings', $settings)->save();
    $expected = array(
      '' => '--',
      'a' => 'a',
      'b' => 'b',
      'c' => 'c'
    );
    $this->assertEqual($expected, $this->optionsProvider->getOptions($field, 'title'));
  }

  public function testTitleOptionsFromTaxonomy() {
    $field = $this->createNameField('field_name_test', 'entity_test', 'entity_test');

    $vocabulary = Vocabulary::create(array(
      'vid' => 'title_options',
      'name' => 'Title options'
    ));
    $vocabulary->save();

    foreach (array('foo', 'bar', 'baz') as $name) {
      $term = Term::create(array(
        'name' => $name,
        'vid' => $vocabulary->id()
      ));
      $term->save();
    }

    /**
     * @var \Drupal\field\Entity\FieldStorageConfig $field_storage
     */
    $field_storage = $field->getFieldStorageDefinition();
    $settings = $field_storage->getSettings();
    $settings['title_options'] = array(
      '-- --',
      '[vocabulary:title_options]'
    );
    $settings['sort_options']['title'] = TRUE;
    $field_storage->set('settings', $settings);
    $field_storage->save();

    $expected = array (
      '' => '--',
      'bar' => 'bar',
      'baz' => 'baz',
      'foo' => 'foo',
    );
    $this->assertEqual($expected, $this->optionsProvider->getOptions($field, 'title'));
  }

}
