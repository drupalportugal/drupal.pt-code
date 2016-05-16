<?php
/**
 * @file
 * Contains \Drupal\colorbox\Tests\Colorbox.
 */

namespace Drupal\colorbox\Tests;

use Drupal\image\Tests\ImageFieldTestBase;

/**
 * Test the colorbox module.
 *
 * @group colorbox
 */
class Colorbox extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'colorbox',
    'field_test',
    'node',
    'field_ui',
    'image'
  ];

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'field_colorbox_test';

  /**
   * The content type used in the test.
   *
   * @var string
   */
  protected $contentType = 'article';

  /**
   * The node ID with an uploaded image, ready for testing.
   *
   * @var int
   */
  protected $testNid;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createImageField($this->fieldName, $this->contentType, ['uri_scheme' => 'public'], ['alt_field_required' => 0]);
    entity_get_display('node', $this->contentType, 'default')
      ->setComponent($this->fieldName, [
        'type' => 'colorbox',
        'settings' => [],
      ])
      ->save();
    $this->drupalGet('node/add/article');
    $test_image = current($this->drupalGetTestFiles('image'));
    $this->testNid = $this->uploadNodeImage($test_image, $this->fieldName, 'article', '');
  }

  /**
   * Test the colorbox formatter.
   */
  public function testColorboxFormatter() {
    $this->drupalGet('node/' . $this->testNid);
    // Ensure all the relevant colorbox JavaScript is loaded on the page.
    $this->assertScript('libraries/colorbox/jquery.colorbox-min.js');
    $this->assertScript('modules/colorbox/styles/default/colorbox_style.js');
    $this->assertScript('modules/colorbox/js/colorbox.js');
    // Ensure the image appears with the relevant colorbox markup.
    $this->assertRaw('rel="gallery-article-1-');

    // The script should not appear by default on the homepage.
    $this->drupalGet('<front>');
    $this->assertScript('libraries/colorbox/jquery.colorbox-min.js', FALSE);
  }

  /**
   * Assert a script is appearing in the document.
   *
   * @param string $script_url
   *   The script URL to assert
   * @pararm bool $exists
   *   A flag to indicate if the script should exist or not exist for a pass.
   */
  public function assertScript($script_url, $exists = TRUE) {
    $version = \Drupal::VERSION;
    $this->{$exists ? 'assertRaw' : 'assertNoRaw'}("<script src=\"{$GLOBALS['base_url']}/$script_url?v=$version\"></script>");
  }

}
