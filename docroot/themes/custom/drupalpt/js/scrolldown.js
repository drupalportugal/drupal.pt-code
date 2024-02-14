/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.bootstrap_barrio_subtheme = {
    attach: function(context, settings) {
      $('a[href^="#"]').on('click', function(e) {
        // Make sure this.hash has a value before overriding default behavior
        if (this.hash !== "") {
          // Store hash
          var hash = this.hash;
          // Using jQuery's animate() method to add smooth page scroll
          $('html, body').animate({
            scrollTop: $(hash).offset().top -180}, 300, 'linear');
          return false;
        } // End if
      });
    }
  };
})(jQuery, Drupal);


