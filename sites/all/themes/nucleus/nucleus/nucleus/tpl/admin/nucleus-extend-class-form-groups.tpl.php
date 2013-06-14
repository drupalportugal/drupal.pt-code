<?php

/**
 * @file
 * Default theme implementation to display a group in extend class popup.
 * This is only a feature in backend.
 *
 * @see nucleus_create_popup_extend_classes()
 */
?>
<?php foreach ($groups as $group_key => $group): ?>
  <div id="<?php print $name; ?>-<?php print $group_key; ?>-group" class="extend-class-group clearfix" onclick="Drupal.Nucleus.nucleusOnClickGroupExtendClass(event)"<?php print $group['show'] ? '' : ' style="display: none;"'; ?>>
    <h4 class="popup-title extend-class-group-title"><?php print $group['group_title']; ?></h4>
    <?php print $group['classes_content']; ?>
  </div>
<?php endforeach; ?>
