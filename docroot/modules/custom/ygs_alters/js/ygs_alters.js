(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.push_history_listener = {
    attach: function (context, settings) {
      $(document).once('push-history').on('url_changed', function (e, params) {
        // Use params.new_url and params.old_url to react on url changes made
        // with PushHistoryCommand.
      });
    }
  };

  Drupal.AjaxCommands.prototype.pushHistory = function (ajax, response, status) {
    var new_url = window.location.origin + window.location.pathname + '?' + response.url;
    var old_url = window.location.href;
    window.history.replaceState({}, document.title, new_url);
    $(document).trigger('url_changed', {
      old_url: window.location.href,
      new_url: new_url
    });
  };

  // Subcategory page preferred location.
  Drupal.behaviors.ygs_subcategory_form = {
    attach: function(context, settings) {
      $('.sub-category-classes-form')
        .once()
        .each(function() {
          var $self = $(this);
          var selected_location = $self.find('.js-form-item-location-s select').val();
          $self.find('.required-filter .remove')
            .mousedown(function(e) {
              var mobile_breakpoint = 768;
              if($(window).width() < mobile_breakpoint) {
                e.preventDefault();
                $('.sub-category-classes-view .add-filters').click();
              }
            });

          if (selected_location) {
            $('.activity-group-slider a').each(function() {
              var href = $(this).attr('href');
              if (href.match('/?location=/')) {
                href.replace('/?location=/', '?location=' + selected_location);
              }
              else {
                href = href + '?location=' + selected_location;
              }
              $(this).attr('href', href);
            });
          }
          setTimeout(function() {
            var preferred_branch = $.cookie('ygs_preferred_branch');
            var location = Drupal.behaviors.ygs_popups_autoload.get_query_param().location;
            if (typeof location == 'undefined' && typeof preferred_branch !== 'undefined') {
              $self.find('.js-form-item-location-s select')
                .val(preferred_branch)
                .trigger('change');
            }
          }, 0);
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
