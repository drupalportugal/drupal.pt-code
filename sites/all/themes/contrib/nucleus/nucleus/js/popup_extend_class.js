(function ($) {
Drupal.Nucleus.currentPopupExtendClassName = false;

Drupal.Nucleus.nucleusEventStopPropagation = function(event) {
  if (event.stopPropagation) {
    event.stopPropagation();
  }
  else if (window.event) {
    window.event.cancelBubble = true;
  }
}

Drupal.Nucleus.nucleusShowExtendClassPopup = function(event, name) {
  Drupal.Nucleus.nucleusEventStopPropagation(event);
  if (Drupal.Nucleus.currentPopupExtendClassName) {
    Drupal.Nucleus.nucleusHideExtendClassPopup(Drupal.Nucleus.currentPopupExtendClassName);
  }

  if (Drupal.Nucleus.currentPopupExtendClassName != name) {
    Drupal.Nucleus.currentPopupExtendClassName = name;
    $('#' + name + '-tb-form-popup').show();
    $('#' + name + '-dialog').show();
    $('#' + name + '-edit-btn').addClass('active');
  }
  else {
    Drupal.Nucleus.nucleusHideExtendClassPopup(name);
    Drupal.Nucleus.currentPopupExtendClassName = false;
  }
}

Drupal.Nucleus.nucleusHideExtendClassPopup = function(name) {
  $('#' + name + '-tb-form-popup').hide();
  $('#' + name + '-dialog').hide();
  $('#' + name + '-edit-btn').removeClass('active');
}

Drupal.Nucleus.nucleusCancelExtendClassPopup = function(event, name, key) {
  Drupal.Nucleus.nucleusEventStopPropagation(event);
  Drupal.Nucleus.nucleusHideExtendClassPopup(name);
  Drupal.Nucleus.currentPopupExtendClassName = false;
  Drupal.Nucleus.nucleusResetCurrentExtendClassForm(name, key);
}

Drupal.Nucleus.nucleusResetCurrentExtendClassForm = function(name, key) {
  var selector_name = key + "_style";
  var hidden_name = key + '_extend_class';
  var name = hidden_name.replace(/_/gi, '-');
  var current_extend_class = $('input:hidden[name=' + hidden_name + ']').attr('value');
  var selector = $('select[name=' + selector_name + ']');
  if(selector && current_extend_class && current_extend_class != undefined) {
    var style = selector.attr('value');
    if (Drupal.Nucleus.nucleusExtendClassSupportGroups !== undefined && Drupal.Nucleus.nucleusExtendClassSupportGroups[style]) {
      var group_name_list = Drupal.Nucleus.nucleusExtendClassSupportGroups[style];
      for (var x in group_name_list) {
        var group = group_name_list[x];
        $('#' + name + '-' + group).attr('checked', 'checked');
      }
    }

    var parts = current_extend_class.split(' ');
    for(var i = 0; i < parts.length; i += 2) {
      var group = parts[i];
      var extend_class = parts[i + 1];
      $('#' + name + '-' + group + '-' + extend_class).attr('checked', 'checked');
    }
  }
}

Drupal.Nucleus.nucleusGetApplyingRegionStyle = function(current_page) {
  return $('#edit-' + current_page + "-global-block-style").val();
}

Drupal.Nucleus.nucleusGetApplyingBlockStyle = function(key, current_page) {
  var length = current_page.length + 7;
  var block_key = key.substr(length, key.length - length);
  block_key = block_key.replace(/_/gi, '-');
  var region_key = Drupal.Nucleus.nucleusRegionsBlocksList['blocks'][block_key];
  var region_id = 'edit-' + current_page + "-region-" + region_key + "-style";
  var region_style = $('#' + region_id).val();
  if (region_style == 'default') {
    return Drupal.Nucleus.nucleusGetApplyingRegionStyle(current_page);
  }
  return region_style;
}

Drupal.Nucleus.nucleusHandleShowHideGroupExtendClass = function(name, hidden_name, style) {
  if (Drupal.Nucleus.nucleusExtendClassSupportGroups !== undefined && Drupal.Nucleus.nucleusExtendClassSupportGroups[style]) {
    var values = [];
    var texts = [];
    var group_name_list = Drupal.Nucleus.nucleusExtendClassSupportGroups[style];
    var visited = {};
    for (var x in group_name_list) {
      var group = group_name_list[x];
      visited[group] = true;
      var radio = $('input:radio[name=' + name + '-' + group + ']:checked');
      if (radio) {
        var value = radio.val();
        if (value != undefined && value != '') {
          var text = Drupal.Nucleus.nucleusExtendClassesList[value];
          values.push(group);
          values.push(value);
          texts.push(text);
        }
      }
    }

    if (Drupal.Nucleus.nucleusStyleSupportCounter[style]) {
      for (x in Drupal.Nucleus.nucleusExtendClassGroupsNameList) {
        var group = Drupal.Nucleus.nucleusExtendClassGroupsNameList[x];
        var element = $('#' + name + "-" + group + "-group");
        if (element) {
          if (visited[group]) {
            element.show();
          }
          else {
            element.hide();
          }
        }
      }

      var shower_text = texts.join(', ');
      if (shower_text == "") shower_text = "&nbsp;";
      $('#' + name + '-shower').html(shower_text);
      $('input:hidden[name=' + hidden_name + ']').attr("value", values.join(' '));
      $('#' + name + '-tb-extend-class').show();
    }
    else {
      $('#' + name + '-shower').html('');
      $('input:hidden[name=' + hidden_name + ']').attr("value", '');
      $('#' + name + '-tb-extend-class').hide();
    }
  }
}

Drupal.Nucleus.nucleusOnChangeBlockStyle = function(key, type) {
  if (!Drupal.Nucleus.nucleusExtendClassSupportGroups) {
    window.setTimeout(function() {Drupal.Nucleus.nucleusOnChangeBlockStyle(selector_name, name, hidden_name)}, 50);
    return;
  }

  var selector_name = key + "_style";
  var hidden_name = key + '_extend_class';
  var name = hidden_name.replace(/_/gi, '-');
  var current_page = $('#edit-page-block-style').val();
  var selector = $('select[name=' + selector_name + ']');
  if (selector) {
    var style = selector.attr('value');
    if (style == 'default') {
      if(type == 'block') {
        style = Drupal.Nucleus.nucleusGetApplyingBlockStyle(key, current_page);
      }
      else if(type == 'region') {
        style = Drupal.Nucleus.nucleusGetApplyingRegionStyle(current_page);
      }
    }

    Drupal.Nucleus.nucleusHandleShowHideGroupExtendClass(name, hidden_name, style);

    if (type == 'global') {
      Drupal.Nucleus.nucleusShowHideGlobalExtendClass(Drupal.Nucleus.nucleusRegionsBlocksList['regions'], current_page, style);
    }
    else if (type == 'region') {
      var length = current_page.length + 8;
      var region_key = key.substr(length, key.length - length);
      region_key = region_key.replace(/_/gi, '-');
      var global_style = $('#edit-' + current_page + "-global-block-style").val();
      var apply_style = (style != 'default') ? style : global_style;
      Drupal.Nucleus.nucleusShowHideRegionExtendClass(Drupal.Nucleus.nucleusRegionsBlocksList['regions'][region_key], region_key, current_page, apply_style);
    }
  }
}

Drupal.Nucleus.nucleusShowHideGlobalExtendClass = function(regions_list, page, style) {
  for (var region_key in regions_list) {
    var hidden_name = page + '_region_' + region_key + '_extend_class';
    var name = hidden_name.replace(/_/gi, '-');
    var region_id = 'edit-' + page + "-region-" + region_key + "-style";
    var region_style = $('#' + region_id).val();
    if (region_style == 'default') {
      Drupal.Nucleus.nucleusHandleShowHideGroupExtendClass(name, hidden_name, style);
      Drupal.Nucleus.nucleusShowHideRegionExtendClass(Drupal.Nucleus.nucleusRegionsBlocksList['regions'][region_key], region_key, page, style);
    }
  }
}

Drupal.Nucleus.nucleusShowHideRegionExtendClass = function(blocks_list, region_key, page, style) {
  for (var block_key in blocks_list) {
    var hidden_name = page + '_block_' + block_key + '_extend_class';
    var name = hidden_name.replace(/_/gi, '-');
    var block_id = 'edit-' + page + "-block-" + block_key + "-style";
    var block_style = $('#' + block_id).val();
    if (block_style == 'default') {
      Drupal.Nucleus.nucleusHandleShowHideGroupExtendClass(name, hidden_name, style);
    }
  }
}

Drupal.Nucleus.nucleusSaveExtendClassPopup = function(event, key, type) {
  Drupal.Nucleus.nucleusEventStopPropagation(event);
  if (!Drupal.Nucleus.nucleusExtendClassGroupsNameList || !Drupal.Nucleus.nucleusExtendClassesList) {
    window.setTimeout(function() {Drupal.Nucleus.nucleusSaveExtendClassPopup(event, key)}, 50);
    return;
  }

  selector_name = key + "_style";
  hidden_name = key + '_extend_class';
  name = hidden_name.replace(/_/gi, '-');

  var values = [];
  var texts = [];
  var current_page = $('#edit-page-block-style').val();
  var selector = $('select[name=' + selector_name + ']');
  if (selector) {
    var style = selector.attr('value');
    if (style == 'default') {
      if(type == 'block') {
        style = Drupal.Nucleus.nucleusGetApplyingBlockStyle(key, current_page);
      }
      else if(type == 'region') {
        style = Drupal.Nucleus.nucleusGetApplyingRegionStyle(current_page);
      }
    }

    if (Drupal.Nucleus.nucleusExtendClassSupportGroups !== undefined && Drupal.Nucleus.nucleusExtendClassSupportGroups[style]) {
      var group_name_list = Drupal.Nucleus.nucleusExtendClassSupportGroups[style];
      var support_some_group = false;
      for (var x in group_name_list) {
        var group = group_name_list[x];
        var radio = $('input:radio[name=' + name + '-' + group + ']:checked');
        if (radio) {
          var value = radio.val();
          if (value != undefined && value != '') {
            var text = Drupal.Nucleus.nucleusExtendClassesList[value];
            values.push(group);
            values.push(value);
            texts.push(text);
          }
        }
      }
      var shower_text = texts.join(', ');
      if (shower_text == "") shower_text = "&nbsp;";
      $('#' + name + '-shower').html(shower_text);
      $('input:hidden[name=' + hidden_name + ']').attr("value", values.join(' '));
      $('#' + name + '-tb-extend-class').show();
    }
  }
  Drupal.Nucleus.nucleusHideExtendClassPopup(name);
  Drupal.Nucleus.currentPopupExtendClassName = false;
};

Drupal.Nucleus.nucleusOnClickGroupExtendClass = function(event) {
  Drupal.Nucleus.nucleusEventStopPropagation(event);
}

Drupal.behaviors.nucleusBlockStyleAction = {
  attach: function (context) {
    $(document).bind('click', function() {
      if (Drupal.Nucleus.currentPopupExtendClassName) {
        Drupal.Nucleus.nucleusHideExtendClassPopup(Drupal.Nucleus.currentPopupExtendClassName);
        Drupal.Nucleus.currentPopupExtendClassName = false;
      }
    });
  }
};

})(jQuery);
