var ManualCrop = {croptool: null, oldSelection: null, widget: null, output: null};

(function ($) {

/**
 * Mark required image styles and trigger the onchange event of all (hidden) fields that store
 * crop data. This way all css classes for the crop lists/buttons will be updated and the default
 * image preview will be changed to the cropped image.
 */
ManualCrop.init = function() {
  var fields = Drupal.settings.manualCrop.fields;

  for (var identifier in fields) {
    for (var k in fields[identifier].required) {
      $('.manualcrop-identifier-' + identifier + ' option[value="' + fields[identifier].required[k] + '"]')
        .addClass('manualcrop-style-required');
    }
  }

  $('.manualcrop-cropdata').trigger('change');
}

/**
 * Callback triggerd after an image upload.
 *
 * @param context
 *    Container of the uploaded image widget.
 */
ManualCrop.afterUpload = function(context) {
  ManualCrop.init();

  var fields = Drupal.settings.manualCrop.fields;

  for (var identifier in fields) {
    if (fields[identifier].instantCrop) {
      if ($('.manualcrop-cropdata', context).length == 1) {
        $('.manualcrop-style-button, .manualcrop-style-thumb', context).trigger('mousedown');
      }
    }
  }
}

/**
 * Open the cropping tool for an image.
 *
 * @param identifier
 *    Unique crop settings identifier.
 * @param style
 *   The image style name or selection list triggering this event.
 * @param fid
 *   The file id of the image the user is about to crop.
 */
ManualCrop.showCroptool = function(identifier, style, fid) {
  var styleName, styleSelect, cropType, origContainer, conWidth, conHeight;

  if (ManualCrop.croptool) {
    // Close the current croptool.
    ManualCrop.closeCroptool();
  }

  // Get the style name.
  if (typeof style == 'string') {
    styleName = style
  }
  else {
    styleSelect = $(style);
    styleName = styleSelect.val();
  }

  $('.manualcrop-file-' + fid + '-holder img').imagesLoaded(function() {
    // IE executes this callback twice, so we check if the ManualCrop.croptool
    // has already been set and skip the rest if this is the case.
    if (!ManualCrop.croptool) {
      // Determine the croptool type.
      if ($('#manualcrop-overlay-' + fid).length == 1) {
        cropType = 'overlay';
        origContainer = $('#manualcrop-overlay-' + fid);
      }
      else {
        cropType = 'inline';
        origContainer = $('#manualcrop-inline-' + fid);
      }

      // Get the crop settings.
      var settings = Drupal.settings.manualCrop.styles[styleName] || {};

      // Get the destination field and the current selection.
      ManualCrop.output = $('#manualcrop-area-' + fid + '-' + styleName);
      ManualCrop.oldSelection = ManualCrop.parseStringSelection(ManualCrop.output.val());

      // Create the croptool.
      ManualCrop.croptool = origContainer.clone()
        .removeAttr('id')
        .removeClass('element-hidden');

      // Get the container maximum width and height.
      if (cropType == 'overlay') {
        conWidth = $(window).width();
        conHeight = $(window).height();
      }
      else {
        conWidth = origContainer.parent().innerWidth();
        conHeight = $(window).height();
      }

      // Tool width and height.
      ManualCrop.croptool.css('width', conWidth + 'px');

      if (cropType == 'overlay') {
        ManualCrop.croptool.css('height', conHeight + 'px');
      }

      // Get the image and its dimensions.
      var image = $('.manualcrop-image', origContainer);
      var width = ManualCrop.parseInt(image.width()) || ManualCrop.parseInt(image.attr('width'));
      var height = ManualCrop.parseInt(image.height()) || ManualCrop.parseInt(image.attr('height'));

      // Scale the image to fit the maximum width and height (so all is visible).
      var maxWidth = conWidth - ManualCrop.parseInt(image.css('marginLeft')) - ManualCrop.parseInt(image.css('marginRight'));
      var maxHeight = conHeight - ManualCrop.parseInt(image.css('marginTop')) - ManualCrop.parseInt(image.css('marginBottom'));

      // Calculate the clone image dimensions.
      var resized = ManualCrop.resizeDimensions(width, height, maxWidth, maxHeight);

      // Set the new width and height to the cloned image.
      image = $('.manualcrop-image', ManualCrop.croptool)
        .css('width', resized.width + 'px')
        .css('height', resized.height + 'px');

      // Basic imgAreaSelect options; All options are set - also if it's their
      // default value - because IE doesn't seem to use the default values.
      var options = {
        handles: true,
        instance: true,
        keys: true,
        movable: true,
        resizable: true,
        parent: image.parent(),
        imageWidth: width,
        imageHeight: height,
        onSelectChange: ManualCrop.updateSelection
      };

      // Additional options based upon the effect.
      if (settings) {
        switch (settings.effect) {
          // Crop and scale effect.
          case 'manualcrop_crop_and_scale':
            options.aspectRatio = settings.data.width + ':' + settings.data.height;

            if (settings.data.respectminimum) {
              // Crop at least the minimum.
              options.minWidth = ManualCrop.parseInt(settings.data.width);
              options.minHeight = ManualCrop.parseInt(settings.data.height);
            }
            break;

          // Crop effect.
          case 'manualcrop_crop':
            if (settings.data.width) {
              options.minWidth = ManualCrop.parseInt(settings.data.width);
            }

            if (settings.data.height) {
              options.minHeight = ManualCrop.parseInt(settings.data.height);
            }

            if (typeof settings.data.keepproportions != 'undefined' && settings.data.keepproportions) {
              options.aspectRatio = settings.data.width + ':' + settings.data.height;
            }
        }
      }

      // Set the image style name.
      $('.manualcrop-image-style', ManualCrop.croptool).text(styleName);

      if (typeof styleSelect != 'undefined') {
        // Reset the image style selection list.
        styleSelect.val('');
        styleSelect.blur();
      }
      else {
        // Hide the crop button.
        $('.manualcrop-style-button-' + fid).hide();
      }

      // Append the cropping area (last, to prevent that '_11' is undefined).
      if (cropType == 'overlay') {
        $('body').append(ManualCrop.croptool);
      }
      else {
        origContainer.parent().append(ManualCrop.croptool);
      }

      // Create the crop widget.
      ManualCrop.widget = image.imgAreaSelect(options);

      // IE seems to have some issues with the imgAreaSelect $parent variable,
      // so we set the options again to initialize it correctly.
      if ($.browser.msie) {
        ManualCrop.widget.setOptions(options);
      }

      // Insert the instant preview image.
      var instantPreview = $('.manualcrop-instantpreview', ManualCrop.croptool);
      if (instantPreview.length) {
        // Save the current width as maximum width and height.
        instantPreview
          .data('maxWidth', instantPreview.width())
          .data('maxHeight', instantPreview.width())
          .height(instantPreview.width());

        // Calculate the instant preview dimensions.
        resized = ManualCrop.resizeDimensions(width, height, instantPreview.width(), instantPreview.width());

        // Set those dimensions.
        image.clone().appendTo(instantPreview)
          .removeClass()
          .css('width', resized.width + 'px')
          .css('height', resized.height + 'px');
      }

      if (!ManualCrop.oldSelection) {
        var fields = Drupal.settings.manualCrop.fields;

        // Create a default crop area.
        if (typeof fields[identifier] == 'object' && fields[identifier].defaultCropArea) {
          var minWidth = (typeof options.minWidth != 'undefined' ? options.minWidth : 0);
          var minHeight = (typeof options.minHeight != 'undefined' ? options.minHeight : 0)

          // Set a width and height.
          ManualCrop.oldSelection = {
            width: (minWidth ? minWidth * 100 : (width / 2)),
            height: (minHeight ? minHeight * 100 : (height / 2)),
            maxWidth: (width / 2),
            maxHeight: (height / 2)
          };

          // Resize the selection.
          ManualCrop.oldSelection = ManualCrop.resizeDimensions(ManualCrop.oldSelection);

          // Make sure we respect the minimum dimensions.
          if (minWidth || minHeight) {
            if (minWidth && ManualCrop.oldSelection.width < minWidth) {
              ManualCrop.oldSelection.width = minWidth;

              if (minHeight) {
                ManualCrop.oldSelection.height = minHeight;
              }
            }
            else if (minHeight && ManualCrop.oldSelection.height < minHeight) {
              ManualCrop.oldSelection.height = minHeight;

              if (minWidth) {
                ManualCrop.oldSelection.width = minWidth;
              }
            }
          }

          // Center the selection.
          ManualCrop.oldSelection.x1 = (width - ManualCrop.oldSelection.width) / 2;
          ManualCrop.oldSelection.y1 = (height - ManualCrop.oldSelection.height) / 2;
          ManualCrop.oldSelection.x2 = ManualCrop.oldSelection.x1 + ManualCrop.oldSelection.width;
          ManualCrop.oldSelection.y2 = ManualCrop.oldSelection.y1 + ManualCrop.oldSelection.height;
        }
      }

      // Set the initial selection.
      if (ManualCrop.oldSelection) {
        ManualCrop.croptool.imagesLoaded(ManualCrop.resetSelection);
      }

      // Handle keyboard shortcuts.
      $(document).keyup(ManualCrop.handleKeyboard);
    }
  });
}

/**
 * Close the cropping tool.
 *
 * @param reset
 *   Set to true to reset the selection before closing.
 */
ManualCrop.closeCroptool = function(reset) {
  if (ManualCrop.croptool) {
    if (reset) {
      ManualCrop.resetSelection();
    }

    ManualCrop.output.trigger('change');

    ManualCrop.widget.setOptions({remove: true});
    ManualCrop.croptool.remove();
    ManualCrop.croptool = null;
    ManualCrop.oldSelection = null;
    ManualCrop.widget = null;
    ManualCrop.output = null;

    $('.manualcrop-style-button').show();

    $(document).unbind('keyup', ManualCrop.handleKeyboard);
  }
}

/**
 * Reset the selection to the previous state.
 */
ManualCrop.resetSelection = function() {
  if (ManualCrop.croptool) {
    if (ManualCrop.oldSelection) {
      ManualCrop.widget.setSelection(ManualCrop.oldSelection.x1, ManualCrop.oldSelection.y1, ManualCrop.oldSelection.x2, ManualCrop.oldSelection.y2);
      ManualCrop.widget.setOptions({hide: false, show: true});
      ManualCrop.widget.update();
      ManualCrop.updateSelection(null, ManualCrop.oldSelection);

      // Hide reset button.
      $('.manualcrop-reset', ManualCrop.croptool).hide();
    }
    else {
      ManualCrop.clearSelection();
    }
  }
}

/**
 * Maximize the selection to fill the container as much as possible/allowed.
 */
ManualCrop.maximizeSelection = function() {
  if (ManualCrop.croptool) {
    var image = $('img.manualcrop-image', ManualCrop.croptool);

    // Get the original width and height.
    var origWidth = ManualCrop.parseInt(image.get(0).getAttribute('width'));
    var origHeight = ManualCrop.parseInt(image.get(0).getAttribute('height'))
    var options = ManualCrop.widget.getOptions();

    // Check if the ratio should be respected.
    if (typeof options.aspectRatio != 'undefined' && options.aspectRatio != '') {
      // Get the ratio.
      var ratio = options.aspectRatio.match(/([0-9]+):([0-9]+)/);
      var ratioWidth = ManualCrop.parseInt(ratio[1]);
      var ratioHeight = ManualCrop.parseInt(ratio[2]);

      // Crop area defaults.
      var width = origWidth;
      var height = origHeight;
      var x = 0;
      var y = 0;

      if ((ratioWidth / ratioHeight) > (origWidth / origHeight)) {
        // Crop from top and bottom.
        height = Math.floor((width / ratioWidth) * ratioHeight);
        y = Math.floor((origHeight - height) / 2);
      }
      else {
        // Crop from sides.
        width = Math.floor((origHeight / ratioHeight) * ratioWidth);
        x = Math.floor((origWidth - width) / 2);
      }

      // Set the new selection.
      ManualCrop.widget.setSelection(x, y, (x + width), (y + height));
    }
    else {
      // No ratio requirements, just select the whole image.
      ManualCrop.widget.setSelection(0, 0, origWidth, origHeight);
    }

    // Update the widget and stored selection.
    ManualCrop.widget.setOptions({hide: false, show: true});
    ManualCrop.widget.update();
    ManualCrop.updateSelection(null, ManualCrop.widget.getSelection());
  }
}

/**
 * Remove the selection.
 */
ManualCrop.clearSelection = function() {
  if (ManualCrop.croptool) {
    ManualCrop.widget.setOptions({hide: true, show: false});
    ManualCrop.widget.update();
    ManualCrop.updateSelection();

    // Hide clear button.
    $('.manualcrop-clear', ManualCrop.croptool).hide();
  }
}

/**
 * When a selection updates write the position and dimensions to the output field.
 *
 * @param image
 *   Reference to the image that is being cropped.
 * @param selection
 *   Object defining the current selection.
 */
ManualCrop.updateSelection = function(image, selection) {
  if (ManualCrop.croptool) {
    var resized;

    // Update the image reference.
    image = $('img.manualcrop-image', ManualCrop.croptool);

    // Get the original width and height.
    var origWidth = ManualCrop.parseInt(image.get(0).getAttribute('width'));
    var origHeight = ManualCrop.parseInt(image.get(0).getAttribute('height'))

    // Get the instant preview.
    var instantPreview = $('.manualcrop-instantpreview', ManualCrop.croptool);

    if (selection && selection.width && selection.height && selection.x1 >= 0 && selection.y1 >= 0) {
      ManualCrop.output.val(selection.x1 + '|' + selection.y1 + '|' + selection.width + '|' + selection.height);

      // Update the selection details.
      $('.manualcrop-selection-x', ManualCrop.croptool).text(selection.x1);
      $('.manualcrop-selection-y', ManualCrop.croptool).text(selection.y1);
      $('.manualcrop-selection-width', ManualCrop.croptool).text(selection.width);
      $('.manualcrop-selection-height', ManualCrop.croptool).text(selection.height);

      // Update the instant preview.
      if (instantPreview.length) {
        // Calculate the instant preview dimensions.
        resized = ManualCrop.resizeDimensions(selection.width, selection.height, instantPreview.data('maxWidth'), instantPreview.data('maxHeight'));

        // Set the new width and height to the preview container.
        instantPreview.css({
          width: resized.width + 'px',
          height: resized.height + 'px'
        });

        // Calculate the resize scale.
        var scaleX = resized.width / selection.width;
        var scaleY = resized.height / selection.height;

        // Update the image css.
        $('img', instantPreview).css({
          width: Math.round(scaleX * origWidth) + 'px',
          height: Math.round(scaleY * origHeight) + 'px',
          marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px',
          marginTop: '-' + Math.round(scaleY * selection.y1) + 'px'
        });
      }
    }
    else {
      ManualCrop.output.val('');

      $('.manualcrop-selection-x', ManualCrop.croptool).text('-');
      $('.manualcrop-selection-y', ManualCrop.croptool).text('-');
      $('.manualcrop-selection-width', ManualCrop.croptool).text('-');
      $('.manualcrop-selection-height', ManualCrop.croptool).text('-');

      // Reset the instant preview.
      if (instantPreview.length) {
        instantPreview
          .width(instantPreview.data('maxWidth'))
          .height(instantPreview.data('maxHeight'));

        resized = ManualCrop.resizeDimensions(origWidth, origHeight, instantPreview.width(), instantPreview.height());

        $('img', instantPreview).css({
          width: resized.width + 'px',
          height: resized.height + 'px',
          marginLeft: '0px',
          marginTop: '0px'
        });
      }
    }

    if (ManualCrop.oldSelection) {
      // Show reset button.
      $('.manualcrop-reset', ManualCrop.croptool).show();
    }

    // Hide clear button.
    $('.manualcrop-clear', ManualCrop.croptool).show();
  }
}

/**
 * A new cropping area was saved to the hidden field, update the default image
 * preview and find the corresponding select option or button and append a css
 * class and text to indicate the crop status.
 *
 * This is a seperate function so it can be triggered after loading.
 *
 * @param element
 *   The hidden field that stores the selection.
 * @param fid
 *   The file id.
 * @param styleName
 *   The image style name.
 */
ManualCrop.selectionStored = function(element, fid, styleName) {
  var selection = $(element).val();

  $('.manualcrop-file-' + fid + '-holder img').imagesLoaded(function() {
    var previewHolder = $('.manualcrop-preview-' + fid + '-' + styleName + ' .manualcrop-preview-cropped');
    if (!previewHolder.length) {
      previewHolder = $('.manualcrop-preview-' + fid + ' .manualcrop-preview-cropped');
    }

    var defaultPreview = $('.manualcrop-preview-' + fid + '-' + styleName + ' > img');
    if (!defaultPreview.length) {
      defaultPreview = $('.manualcrop-preview-' + fid + ' > img');
    }

    var toolOpener = $('.manualcrop-style-select-' + fid + " option[value='" + styleName + "'], .manualcrop-style-button-" + fid + ', .manualcrop-style-thumb-' + fid + '-' + styleName + ' .manualcrop-style-thumb-label');
    var hasClass = toolOpener.hasClass('manualcrop-style-cropped');

    if (previewHolder.length && previewHolder.children().length) {
      previewHolder.css({
        width: '0px',
        height: '0px'
      }).html('');
      defaultPreview.css('display', 'block');
    }

    if (selection) {
      if (previewHolder.length) {
        // Get the dimensions of the original preview image and hide it again.
        var maxWidth = ManualCrop.parseInt(defaultPreview.width());
        var maxHeight = ManualCrop.parseInt(defaultPreview.height());

        if (maxWidth > 0) {
          defaultPreview.css('display', 'none');

          // Get the selected crop area.
          selection = ManualCrop.parseStringSelection(selection);

          // Calculate the preview dimensions.
          var resized = ManualCrop.resizeDimensions(selection.width, selection.height, maxWidth, maxHeight);

          // Set the new width and height to the cropped preview holder.
          previewHolder.css({
            width: resized.width + 'px',
            height: resized.height + 'px'
          });

          // Calculate the resize scale.
          var scaleX = resized.width / selection.width;
          var scaleY = resized.height / selection.height;

          // Get the original image.
          var originalImage = $('#manualcrop-overlay-' + fid + ' img.manualcrop-image, #manualcrop-inline-' + fid + ' img.manualcrop-image');

          // Calculate the new width and height using the full image.
          resized.width = Math.round(scaleX * ManualCrop.parseInt(originalImage.width()));
          resized.height = Math.round(scaleY * ManualCrop.parseInt(originalImage.height()));

          // Create and insert the cropped preview.
          previewHolder.append(originalImage.clone().removeClass().css({
            width: resized.width + 'px',
            height: resized.height + 'px',
            marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px',
            marginTop: '-' + Math.round(scaleY * selection.y1) + 'px'
          }));
        }
      }

      if (!hasClass) {
        // Style has been cropped.
        toolOpener.addClass('manualcrop-style-cropped');

        if (toolOpener.is('input')) {
          toolOpener.val(toolOpener.val() + ' ' + Drupal.t('(cropped)'));
        }
        else {
          toolOpener.text(toolOpener.text() + ' ' + Drupal.t('(cropped)'));
        }
      }
    } else if (hasClass) {
      // Style not cropped.
      toolOpener.removeClass('manualcrop-style-cropped');

      if (toolOpener.is('input')) {
        toolOpener.val(toolOpener.val().substr(0, (toolOpener.val().length - Drupal.t('(cropped)').length - 1)));
      }
      else {
        toolOpener.text(toolOpener.text().substr(0, (toolOpener.text().length - Drupal.t('(cropped)').length - 1)));
      }
    }
  });
}

/**
 * Keyboard shortcuts handler.
 *
 * @param e
 *    The event object.
 */
ManualCrop.handleKeyboard = function(e) {
  if (ManualCrop.croptool) {
    if(e.keyCode == 13) {
      // Enter
      ManualCrop.closeCroptool();
    }
    else if(e.keyCode == 27) {
      // Escape
      ManualCrop.closeCroptool(true);
    }
  }
}

/**
 * Parse a string defining the selection to an object.
 *
 * @param txtSelection
 *   The selection as a string e.a.: "x|y|width|height".
 * @return
 *   An object containing defining the selection.
 */
ManualCrop.parseStringSelection = function(txtSelection) {
  if (txtSelection) {
    var parts = txtSelection.split('|');
    var selection = {
      x1: ManualCrop.parseInt(parts[0]),
      y1: ManualCrop.parseInt(parts[1]),
      width: ManualCrop.parseInt(parts[2]),
      height: ManualCrop.parseInt(parts[3])
    };

    selection.x2 = selection.x1 + selection.width;
    selection.y2 = selection.y1 + selection.height;

    return selection;
  }

  return null;
}

/**
 * Parse a textual number to an integer.
 *
 * @param integer
 *   The textual integer.
 * @return
 *   The integer.
 */
ManualCrop.parseInt = function(integer) {
  return (parseInt(integer) || 0)
}

/**
 * Calculate new dimensions based upon a maximum width and height.
 *
 * @param width
 *   The current width or an object width all the properties set.
 * @param height
 *   The current height.
 * @param maxWidth
 *   The maximum width.
 * @param maxHeight
 *   The maximum height.
 * @return
 *   An object with the new width and height as properties.
 */
ManualCrop.resizeDimensions = function(width, height, maxWidth, maxHeight) {
  if (typeof width == 'object') {
    if (typeof width.maxWidth != 'undefined' && width.maxWidth) {
      maxWidth = width.maxWidth;
    }
    else {
      maxWidth = 9999999;
    }

    if (typeof width.maxHeight != 'undefined' && width.maxHeight) {
      maxHeight = width.maxHeight;
    }
    else {
      maxHeight = 9999999;
    }

    height = width.height;
    width = width.width;
  }
  else {
    if (!maxWidth) {
      maxWidth = 9999999;
    }

    if (!maxHeight) {
      maxHeight = 9999999;
    }
  }

  // Calculate the new width and height.
  if(width > maxWidth) {
    height = Math.floor((height * maxWidth) / width);
    width = maxWidth;
  }

  if(height > maxHeight) {
    width = Math.floor((width * maxHeight) / height);
    height = maxHeight;
  }

  return {
    'width': width,
    'height': height
  };
}

$(document).ready(function() {
  ManualCrop.init();

  // Add a blur action to all buttons.
  $('.manualcrop-button').live('mousedown', function() {
    this.blur();
  });

  // Attach behaviors to execute after an ajax call.
  Drupal.behaviors.manualCrop = {
    attach: function(context, settings) {
      // After upload function on image upload.
      $('.ajax-new-content', context).once('manualcrop', function() {
        var element = $(this);

        if (!element.html().length) {
          // If the $form['#file_upload_delta'] is not set or invalid the file module
          // will add an empty <span> as .ajax-new-content element, so we need the
          // previous element to execute the after upload function.
          ManualCrop.afterUpload(element.prev().get(0));
        }
        else {
          ManualCrop.afterUpload(this);
        }
      });

      // Init function if a modal (Media module) was opened.
      $('.modal-content', context).ready(function() {
        ManualCrop.init();
      });
    }
  };
});

})(jQuery);
