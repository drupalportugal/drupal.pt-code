/**
 * @file
 * Provide a component that switches the sitewide language.
 */

(function ($, Backbone, Drupal, undefined) {

Drupal.behaviors.languageSwitch = {
  attach: function (context) {
    var $body = $(window.parent.document.body).once('language-switch');
    if ($body.length) {
      var tabModel = Drupal.languageSwitch.models.tabModel = new Drupal.languageSwitch.TabStateModel();
      var $tab = $('#language-switch-navbar-tab').once('language-switch');
      if ($tab.length > 0) {
        Drupal.languageSwitch.views.tabView = new Drupal.languageSwitch.TabView({
          el: $tab.get(),
          tabModel: tabModel,
        });
      }
    }
  }
};

Drupal.languageSwitch = Drupal.languageSwitch || {

  // Storage for view and model instances.
  views: {},
  models: {},

  // Backbone Model for the navbar tab state.
  TabStateModel: Backbone.Model.extend({
    defaults: {
      isLanguageSwitchOpen: false
    }
  }),

  // Handles the navbar tab interactions.
  TabView: Backbone.View.extend({
    events: {
      'click .language-switch-trigger': 'toggleLanguageSwitch',
      'mouseleave': 'toggleLanguageSwitch'
    },

    initialize: function(options) {
      this.tabModel = options.tabModel;
      this.tabModel.on('change:isLanguageSwitchOpen', this.render, this);
    },

    render: function() {
      var isLanguageSwitchOpen = this.tabModel.get('isLanguageSwitchOpen');
      this.$el.toggleClass('open', isLanguageSwitchOpen);
      return this;
    },

    toggleLanguageSwitch: function(event){
      if (event.type === 'mouseleave') {
        this.tabModel.set('isLanguageSwitchOpen', false);
      }
      else {
        this.tabModel.set('isLanguageSwitchOpen', !this.tabModel.get('isLanguageSwitchOpen'));
      }
      event.stopPropagation();
      event.preventDefault();
      },
    }),
  };

}(jQuery, Backbone, Drupal));
