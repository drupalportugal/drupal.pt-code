<?php

/**
 * @file
 * Contains \Drupal\name\NameTestBase.
 *
 * Tests for the name module.
 */

namespace Drupal\name\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\name\NameFormatParser;

/**
 * Helper test class with some added functions for testing.
 */
abstract class NameTestBase extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'node',
    'name'
  );

  protected $instance;
  protected $web_user;
  protected $admin_user;

  function setUp() {
    parent::setUp();

    // Base set up is done, we can call drupalCreateUser.
    $this->web_user = $this->drupalCreateUser(array());
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer content types', 'access content', 'access administration pages', 'administer node fields'));
  }

  protected function assertNoFieldCheckedByName($name, $message = '') {
    $elements = $this->xpath('//input[@name=:name]', array(':name' => $name));
    return $this->assertTrue(isset($elements[0]) && empty($elements[0]['checked']), $message ? $message : t('Checkbox field @name is not checked.', array('@name' => $name)), t('Browser'));
  }

  protected function assertFieldCheckedByName($name, $message = '') {
    $elements = $this->xpath('//input[@name=:name]', array(':name' => $name));
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), $message ? $message : t('Checkbox field @name is checked.', array('@name' => $name)), t('Browser'));
  }

  function assertNameFormat($name_components, $type, $object, $format, $expected, array $options = array()) {
    $this->assertNameFormats($name_components, $type, $object, array($format => $expected), $options);
  }

  function assertNameFormats($name_components, $type, $object, array $names, array $options = array()) {
    foreach ($names as $format => $expected) {
      $value = NameFormatParser::parse($name_components, $format, array('object' => $object, 'type' => $type));
      $this->assertIdentical($value, $expected,
        t("Name value for '@name' was '@actual', expected value '@expected'. Components were: %components",
        array('@name' => $format, '@actual' => $value, '@expected' => $expected, '%components' => implode(' ', $name_components))));
    }
  }

}



