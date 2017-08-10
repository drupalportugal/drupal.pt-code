<?php

/**
 * @file
 * Contains \Drupal\name\Tests\NameFieldTest.
 *
 * Tests for the name module.
 */

namespace Drupal\name\Tests;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests for the admin settings and custom format page.
 *
 * @group name
 */
class NameFieldTest extends NameTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'node',
    'name',
    'taxonomy'
  );

  public static function getInfo() {
    return array(
      'name' => 'Node Name Field',
      'description' => 'Various tests on creating a name field on a node.' ,
      'group' => 'Name',
    );
  }

  function setUp() {
    parent::setUp();

    // Create content-type: page
    $page = entity_create('node_type', array('type' => 'page', 'name' => 'Basic page'));
    $page->save();
  }

  /**
   * The most basic test. This should only fail if there is a change to the
   * Drupal API.
   */
  function testFieldEntry() {
    $this->drupalLogin($this->admin_user);

    $new_name_field = array(
      'label' => 'Test name',
      'field_name' => 'name_test',
      'new_storage_type' => 'name',
    );

    $this->drupalPostForm('admin/structure/types/manage/page/fields/add-field', $new_name_field, t('Save and continue'));
    $this->resetAll();

    // Required test.
    $field_settings = array();
    foreach ($this->name_getFieldStorageSettings() as $key => $value) {
      $field_settings[$key] = '';
    }
    foreach ($this->name_getFieldStorageSettingsCheckboxes() as $key => $value) {
      $field_settings[$key] = FALSE;
    }

    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $field_settings, t('Save field settings'));

    $n = _name_translations();
    $required_messages = array(
      t('Label for @field field is required.', array('@field' => $n['title'])),
      t('Label for @field field is required.', array('@field' => $n['given'])),
      t('Label for @field field is required.', array('@field' => $n['middle'])),
      t('Label for @field field is required.', array('@field' => $n['family'])),
      t('Label for @field field is required.', array('@field' => $n['generational'])),
      t('Label for @field field is required.', array('@field' => $n['credentials'])),

      t('Maximum length for @field field is required.', array('@field' => $n['title'])),
      t('Maximum length for @field field is required.', array('@field' => $n['given'])),
      t('Maximum length for @field field is required.', array('@field' => $n['middle'])),
      t('Maximum length for @field field is required.', array('@field' => $n['family'])),
      t('Maximum length for @field field is required.', array('@field' => $n['generational'])),
      t('Maximum length for @field field is required.', array('@field' => $n['credentials'])),
      t('@field options are required.', array('@field' => $n['title'])),
      t('@field options are required.', array('@field' => $n['generational'])),

      t('@field field is required.', array('@field' => t('Components'))),
      t('@field must have one of the following components: @components', array('@field' => t('Minimum components'), '@components' => SafeMarkup::checkPlain(implode(', ', array($n['given'], $n['family']))))),
    );
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }
    $field_settings = array(
      'settings[components][title]' => FALSE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => FALSE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => FALSE,
      'settings[components][credentials]' => FALSE,

      'settings[minimum_components][title]' => TRUE,
      'settings[minimum_components][given]' => FALSE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => FALSE,
      'settings[minimum_components][generational]' => TRUE,
      'settings[minimum_components][credentials]' => TRUE,

      'settings[max_length][title]' => 0,
      'settings[max_length][given]' => -456,
      'settings[max_length][middle]' => 'asdf',
      'settings[max_length][family]' => 3454,
      'settings[max_length][generational]' => 4.5,
      'settings[max_length][credentials]' => 'NULL',

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.\n[vocabulary:machine]",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX\n[vocabulary:123]",
    );
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $field_settings, t('Save field settings'));

    $required_messages = array(
      /*
      t('@components can not be selected for !label when they are not selected for !label2.',
              array('!label' => t('Minimum components'), '!label2' => t('Components'),
              '@components' => SafeMarkup::checkPlain(implode(', ', array($n['title'], $n['generational'], $n['credentials']))))),
      */

      t('@field must be higher than or equal to 1.', array('@field' => $n['title'])),
      t('@field must be higher than or equal to 1.', array('@field' => $n['given'])),
      t('@field must be a number.', array('@field' => $n['middle'])),
      t('@field must be lower than or equal to 255.', array('@field' => $n['family'])),
      t('@field is not a valid number.', array('@field' => $n['generational'])),
      t('@field must be a number.', array('@field' => $n['credentials'])),

      t('@field must have one of the following components: @components', array('@field' => t('Minimum components'), '@components' => SafeMarkup::checkPlain(implode(', ', array($n['given'], $n['family']))))),

      t("The vocabulary 'machine' in @field could not be found.", array('@field' => t('@title options', array('@title' => $n['title'])))),
      t("The vocabulary '123' in @field could not be found.", array('@field' => t('@generational options', array('@generational' => $n['generational'])))),
    );
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Make sure option lengths do not exceed the title lengths
    $field_settings = array(
      'settings[max_length][title]' => 5,
      'settings[max_length][generational]' => 3,
      'settings[title_options]' => "Aaaaa.\n-- --\nMr.\nMrs.\nBbbbbbbb\nMiss\nMs.\nDr.\nProf.\nCcccc.",
      'settings[generational_options]' => "AAAA\n-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX\nBBBB",
    );
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $field_settings, t('Save field settings'));
    $required_messages = array(
      t('The following options exceed the maximum allowed @field length: Aaaaa., Bbbbbbbb, Ccccc.', array('@field' => t('@title options', array('@title' => $n['title'])))),
      t('The following options exceed the maximum allowed @field length: AAAA, VIII, BBBB', array('@field' => t('@generational options', array('@generational' => $n['generational'])))),
    );

    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Make sure option have at least one valid option.
    $field_settings = array(
      'settings[title_options]' => " \n-- --\n ",
      'settings[generational_options]' => " \n-- --\n ",
    );
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $field_settings, t('Save field settings'));
    $required_messages = array(
      t('@field are required.', array('@field' => t('@title options', array('@title' => $n['title'])))),
      t('@field are required.', array('@field' => t('@generational options', array('@generational' => $n['generational'])))),
    );
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Make sure option have at least one valid only have one default value.
    $field_settings = array(
      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\n-- Bob\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\n--",
    );
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $field_settings, t('Save field settings'));
    $required_messages = array(
      t('@field can only have one blank value assigned to it.', array('@field' => t('@title options', array('@title' => $n['title'])))),
      t('@field can only have one blank value assigned to it.', array('@field' => t('@generational options', array('@generational' => $n['generational'])))),
    );
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Save the field again with the default values
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $this->name_getFieldStorageSettings(), t('Save field settings'));

    $this->assertText(t('Updated field Test name field settings.'));

    // Now the widget settings...
    // First, check that field validation is working... cut n paste from above test
    $field_settings = array(
      'settings[components][title]' => FALSE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => FALSE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => FALSE,
      'settings[components][credentials]' => FALSE,

      'settings[minimum_components][title]' => TRUE,
      'settings[minimum_components][given]' => FALSE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => FALSE,
      'settings[minimum_components][generational]' => TRUE,
      'settings[minimum_components][credentials]' => TRUE,

      'settings[max_length][title]' => 0,
      'settings[max_length][given]' => -456,
      'settings[max_length][middle]' => 'asdf',
      'settings[max_length][family]' => 3454,
      'settings[max_length][generational]' => 4.5,
      'settings[max_length][credentials]' => 'NULL',

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.\n[vocabulary:machine]",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX\n[vocabulary:123]",

    );
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $field_settings, t('Save field settings'));

    $required_messages = array(
      /*
      t('@components can not be selected for !label when they are not selected for !label2.',
              array('!label' => t('Minimum components'), '!label2' => t('Components'),
              '@components' => SafeMarkup::checkPlain(implode(', ', array($n['title'], $n['generational'], $n['credentials']))))),
      */

      t('Maximum length for @field must be higher than or equal to 1.', array('@field' => $n['title'])),
      t('Maximum length for @field must be higher than or equal to 1.', array('@field' => $n['given'])),
      t('Maximum length for @field must be a number.', array('@field' => $n['middle'])),
      t('Maximum length for @field must be lower than or equal to 255.', array('@field' => $n['family'])),
      t('Maximum length for @field is not a valid number.', array('@field' => $n['generational'])),
      t('Maximum length for @field must be a number.', array('@field' => $n['credentials'])),

      t('@field must have one of the following components: @components', array('@field' => t('Minimum components'), '@components' => SafeMarkup::checkPlain(implode(', ', array($n['given'], $n['family']))))),

      t("The vocabulary 'machine' in @field could not be found.", array('@field' => t('@title options', array('@title' => $n['title'])))),
      t("The vocabulary '123' in @field could not be found.", array('@field' => t('@generational options', array('@generational' => $n['generational'])))),
    );
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    $widget_settings = array(
      'settings[title_display][title]' => 'description', // title, description, none
      'settings[title_display][given]' => 'description',
      'settings[title_display][middle]' => 'description',
      'settings[title_display][family]' => 'description',
      'settings[title_display][generational]' => 'description',
      'settings[title_display][credentials]' => 'description',

      'settings[size][title]' => 6,
      'settings[size][given]' => 20,
      'settings[size][middle]' => 20,
      'settings[size][family]' => 20,
      'settings[size][generational]' => 5,
      'settings[size][credentials]' => 35,

      'settings[inline_css][title]' => '',
      'settings[inline_css][given]' => '',
      'settings[inline_css][middle]' => '',
      'settings[inline_css][family]' => '',
      'settings[inline_css][generational]' => '',
      'settings[inline_css][credentials]' => '',
    );

    $this->resetAll();
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');

    foreach ($widget_settings as $name => $value) {
      $this->assertFieldByName($name, $value);
    }

    // Check help text display
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');
    $edit = [
      'description' => 'This is a description.'
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test/storage');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('node/add/page');
    $this->assertUniqueText('This is a description.', 'Field description is shown once when field cardinality is 1.');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test/storage');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('node/add/page');
    $this->assertUniqueText('This is a description.', 'Field description is shown once when field cardinality is 3.');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test/storage');
    $edit = [
      'cardinality' => '-1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('node/add/page');
    $this->assertUniqueText('This is a description.', 'Field description is shown once when field cardinality is unlimited.');
  }

  function name_getFieldStorageSettings() {
    $field_settings = array(
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,

      'settings[minimum_components][title]' => FALSE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => TRUE,
      'settings[minimum_components][generational]' => FALSE,
      'settings[minimum_components][credentials]' => FALSE,

      'settings[max_length][title]' => 31,
      'settings[max_length][given]' => 63,
      'settings[max_length][middle]' => 127,
      'settings[max_length][family]' => 63,
      'settings[max_length][generational]' => 15,
      'settings[max_length][credentials]' => 255,

      'settings[labels][title]' => t('Title'),
      'settings[labels][given]' => t('Given'),
      'settings[labels][middle]' => t('Middle name(s)'),
      'settings[labels][family]' => t('Family'),
      'settings[labels][generational]' => t('Generational'),
      'settings[labels][credentials]' => t('Credentials'),

      'settings[sort_options][title]' => TRUE,
      'settings[sort_options][generational]' => FALSE,

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX",

    );
    return $field_settings;
  }

  function name_getFieldStorageSettingsCheckboxes() {
    $field_settings = array(
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,

      'settings[minimum_components][title]' => FALSE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => TRUE,
      'settings[minimum_components][generational]' => FALSE,
      'settings[minimum_components][credentials]' => FALSE,

      'settings[sort_options][title]' => TRUE,
      'settings[sort_options][generational]' => FALSE,
    );
    return $field_settings;
  }
}

