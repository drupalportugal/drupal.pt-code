(function ($) {
Drupal.Nucleus = {};
Drupal.behaviors.nucleusGridAction = {
  attach: function (context) {
    Drupal.Nucleus.nucleusChangeGridType($("#edit-grid"));
    $("#edit-grid").change(function () {
      Drupal.Nucleus.nucleusChangeGridType(this);
    });

    var value = $('#edit-grid').attr('value');
    var grid_int = parseInt(value.substr(value.length - 2, 2), 10);
    if (grid_int != current_grid_int) {
      Drupal.Nucleus.nucleusChangePageWidth(grid_int);
    }
    $('#edit-grid').change(function() {
      var value = this.value;
      var grid_int = parseInt(value.substr(value.length - 2, 2), 10);
      Drupal.Nucleus.nucleusChangePageWidth(grid_int);
    });
  }
};

Drupal.Nucleus.nucleusUpdateGridOptions = function(select_id, grid_int) {
  if (grid_int < current_grid_int) {
    for (var i = grid_int + 1; i <= current_grid_int; i++) {
      $('#' + select_id + ' option[value="' + i + '"]').remove();
    }
  }
  else if (grid_int > current_grid_int) {
    var select = $('#' + select_id);
    for (var i = current_grid_int + 1; i <= grid_int; i++) {
      select.append($('<option value="' + i + '">' + grid_24_options[i] + '</option>'));
    }
  }
};

Drupal.Nucleus.nucleusChangePageWidth = function(grid_int) {
  for (var x in nucleus_width_selects) {
    Drupal.Nucleus.nucleusUpdateGridOptions(nucleus_width_selects[x], grid_int);
  }
  current_grid_int = grid_int;
}

Drupal.Nucleus.nucleusChangeGridType = function(element) {
  if ($(element).attr('value').substr(0, 5) == 'fluid') {
    $(".form-item-layout-width").css({display: ""});
  }
  else {
    $(".form-item-layout-width").css({display: "none"});
  }
}

Drupal.Nucleus.nucleusOnClickResetDefaultSettings = function() {
  var answer = confirm(Drupal.t('Are you sure you want to reset your theme settings to default theme settings?'))
  if (answer){
    $("input:hidden[name = nucleus_use_default_settings]").attr("value", 1);
    return true;
  }
  return false;
}

})(jQuery);
