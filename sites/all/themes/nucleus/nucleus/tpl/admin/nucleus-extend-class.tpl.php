<?php

/**
 * @file
 * Default theme implementation to display a extend class popup.
 * This is only a feature in backend.
 *
 * @see nucleus_create_popup_extend_classes()
 */
?>
<div class="form-item tb-extend-class" id="<?php print $name; ?>-tb-extend-class"<?php print $show_extend_class_popup ? "" : ' style="display: none;"'; ?>>
  <span id="<?php print $name; ?>-edit-btn" class="tb-form-btn edit-btn" onclick="Drupal.Nucleus.nucleusShowExtendClassPopup(event, '<?php print $name; ?>')"><?php print t('Classes'); ?></span>
  <span id="<?php print $name; ?>-shower" class="tb-extend-class-shower"><?php print $default_value; ?></span>
  <div id="<?php print $name; ?>-tb-form-popup" class="tb-form-popup-wrap" style="display: none;">
    <div id="<?php print $name; ?>-dialog" class="tb-form-popup">
      <div id="<?php print $name; ?>-groups" class="tb-form-popup-ct tb-extend-class-groups clearfix">
        <?php print $nucleus_extend_class_form_groups; ?>
      </div>
      <div class="tb-form-popup-actions clearfix">
        <a href="javascript:void(0);" id="<?php print $name; ?>-save" class="tb-form-btn save-btn" onclick="Drupal.Nucleus.nucleusSaveExtendClassPopup(event, '<?php print $key; ?>', '<?php print $type; ?>')"/><?php print t('Save'); ?></a>
        <a href="javascript:void(0);" id="<?php print $name; ?>-close" class="tb-form-btn close-btn" onclick="Drupal.Nucleus.nucleusCancelExtendClassPopup(event, '<?php print $name; ?>', '<?php print $key; ?>')"/><?php print t('Cancel'); ?></a>
      </div>
    </div>
  </div>
</div>
