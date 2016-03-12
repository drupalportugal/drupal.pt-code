/**
 * @file
 * JS to automatically open navbar and to lock it vertically.
 */

(function($) {
  Drupal.behaviors.navbar_extras = {
    attach: function(context, settings) {
      if (settings.navbar_extras.navbar_open_menu) {
        var a = "navbar-item--2";
        localStorage.setItem('Drupal.navbar.activeTabID', '"navbar-item--2"');
        localStorage.setItem('Drupal.navbar.activeTab', '"navbar-item--2"');
      }
      if (settings.navbar_extras.navbar_lock_vertically) {
        localStorage.setItem('Drupal.navbar.trayVerticalLocked', "true");
      }
    }
  };
})(jQuery);
