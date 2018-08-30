(function ($, Drupal, drupalSettings) {

  'use strict';
  var locationSelectsArray = [
    '.sub-category-classes-form .js-form-item-location-s select',
    '.ygs-schedules-search-form #edit-location'
  ];
  var locationSelects = locationSelectsArray.join(', ');

  // Pops the location select box up on page load.
  Drupal.behaviors.ygs_popups_autoload = {
    attach: function (context, settings) {
      // How to set preferred branch:
      // $.cookie('ygs_preferred_branch', 6, { expires: 7, path: '/' });

      var preferred_branch = $.cookie('ygs_preferred_branch');
      if (typeof this.get_query_param().location == 'undefined' && typeof preferred_branch == 'undefined') {
        // Open popup.
        $('a.location-popup-link').once().click();
        $(document).on('click', 'body > .ui-widget-overlay', function() {
          $('.ui-dialog-content').dialog('close');
        });
      }
    },

    // Extracts query params from url.
    get_query_param: function () {
      var query_string = {};
      var query = window.location.search.substring(1);
      var pairs = query.split('&');
      for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');

        // If first entry with this name.
        if (typeof query_string[pair[0]] === 'undefined') {
          query_string[pair[0]] = decodeURIComponent(pair[1]);
        }
        // If second entry with this name.
        else if (typeof query_string[pair[0]] === 'string') {
          query_string[pair[0]] = [
            query_string[pair[0]],
            decodeURIComponent(pair[1])
          ];
        }
        // If third or later entry with this name
        else {
          query_string[pair[0]].push(decodeURIComponent(pair[1]));
        }
      }

      return query_string;
    }
  };

  // Prevent Class page location popup form from being submitted, instead of it
  // fires 'locations-changed' event and closes the dialog.
  Drupal.behaviors.ygs_popup_no_submit = {
    attach: function (context, settings) {
      $('.ygs-popups-branches-form, .ygs-popups-class-branches-form', context).on('submit', function (e) {
        var location = $('[name=branch]:checked', this).val();
        if (document.activeElement.getAttribute('value') == 'Set Location') {
          $('.location-select-set').addClass('hidden');
          $('.location-select-branch').addClass('hidden');
          $('.location-post-select').removeClass('hidden');
          $('.branch-popup-map').addClass('hidden');
          e.preventDefault();
        }
        else {
          if (document.activeElement.getAttribute('value') == 'Yes') {
            Drupal.behaviors.ygs_popup_location_selector.set_cookie();
          }
          $(document).trigger('location-changed', [{location: location}]);
          $(this).parents('.ui-dialog-content').dialog('close');
          e.preventDefault();

          // Trigger form to react to selected location.
          if (typeof location !== 'undefined') {
            $(locationSelects).once('ygs-popups-branches')
              .val(location)
              .trigger('change');
          }
        }
      });
    }
  };

  Drupal.behaviors.ygs_popup_location_selector = {
    nid: 0,
    action: 'flag',

    set_cookie: function() {
      var self = this;
      self.nid = $('input[name="branch"]:checked').val();
      if (self.action == 'flag') {
        $.cookie('ygs_preferred_branch', self.nid, { expires: 365, path: '/' });
      }
      else {
        $.removeCookie('ygs_preferred_branch', { path: '/' });
      }
    }
  };

} (jQuery, Drupal, drupalSettings));
