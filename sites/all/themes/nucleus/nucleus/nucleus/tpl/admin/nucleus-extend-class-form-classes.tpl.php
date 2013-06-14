<?php

/**
 * @file
 * Default theme implementation to display a extend class in extend class popup.
 * This is only a feature in backend.
 *
 * @see nucleus_create_popup_extend_classes()
 */
?>
<div id="<?php print $name; ?>-empty-class" class="extend-class-content">
  <label>
    <input type="radio" <?php print ($default_value == '') ? ' checked="checked" ' : ""; ?> name="<?php print $name; ?>-<?php print $group; ?>" value="" class="<?php print $name; ?>-radio" id="<?php print $name; ?>-<?php print $group; ?>" value="" class="<?php print $name; ?>-radio"/>
    <?php print t('Not use this group'); ?>
  </label>
</div>

<?php foreach ($classes as $class_key => $class_title): ?>
  <div id="<?php print $name; ?>-<?php print $class_key; ?>-class" class="extend-class-content">
    <label>
      <input type="radio" <?php print ($default_value == $class_key) ? ' checked="checked" ' : ""; ?> name="<?php print $name; ?>-<?php print $group; ?>" value="<?php print $class_key; ?>" class="<?php print $name; ?>-radio" id="<?php print $name; ?>-<?php print $group; ?>-<?php print $class_key; ?>" value="<?php print $class_key; ?>" class="<?php print $name; ?>-radio"/>
      <?php print $class_title; ?>
    </label>
  </div>
<?php endforeach; ?>
