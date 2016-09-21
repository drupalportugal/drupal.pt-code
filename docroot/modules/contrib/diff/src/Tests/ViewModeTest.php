<?php

/**
 * @ingroup diff
 */

namespace Drupal\diff\Tests;

/**
 * Tests field visibility when using a custom view mode.
 *
 * @group diff
 */
class ViewModeTest extends DiffTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Tests field visibility using a cutom view mode.
   */
  public function testViewMode() {
    $this->drupalLogin($this->rootUser);
    // Set the Article content type to use the diff view mode.
    $edit = [
      'view_mode' => 'diff',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->assertText('The content type Article has been updated.');

    // Specialize the 'diff' view mode, check that the field is displayed the same.
    $edit = array(
      "display_modes_custom[diff]" => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));

    // Set the Body field to hidden in the diff view mode.
    $edit = array(
      'fields[body][type]' => 'hidden',
    );
    $this->drupalPostForm('admin/structure/types/manage/article/display/diff', $edit, t('Save'));

    // Create a node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Sample node',
      'body' => [
        'value' => 'Foo',
      ],
    ]);

    // Edit the article and change the email.
    $edit = array(
      'body[0][value]' => 'Fighters',
      'revision' => TRUE,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check the difference between the last two revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, NULL, t('Compare'));
    $this->assertNoText('Body');
    $this->assertNoText('Foo');
    $this->assertNoText('Fighters');
  }

}
