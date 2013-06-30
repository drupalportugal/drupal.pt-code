(function ($) {
Drupal.behaviors.actionTBPurity = {
  attach: function (context) {
    window.setTimeout(function() {
      $('.views-view-grid .grid > .grid-inner').matchHeights();
      $('#sidebar-first-wrapper > .grid-inner, #sidebar-second-wrapper > .grid-inner, #main-content > .grid-inner').matchHeights();
      $('#panel-second-wrapper .panel-column > .grid-inner').matchHeights();
    }, 100);
  }
};
})(jQuery);
