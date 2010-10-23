<?php
// $Id: pane-plain-box.tpl.php,v 1.2 2010/09/07 09:42:12 sdboyer Exp $
/**
 * @file
 *
 * Display the box for rounded corners.
 *
 * - $pane: The pane being rendered
 * - $display: The display being rendered
 * - $content: An object containing the content and title
 * - $output: The result of theme('panels_pane')
 * - $classes: The classes that must be applied to the top divs.
 */
?>
<div class="<?php print $classes ?>">
  <?php print $output; ?>
</div>
