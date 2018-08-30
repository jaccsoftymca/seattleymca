(function($) {
  "use strict";

  /**
   * Triggered by AJAX action for updating browser URL query options and browser's history.
   *
   * @param parameters
   */
  $.fn.schedulesAjaxAction = function(parameters) {
    var params = [];
    for (var key in parameters) {
      // Skip some form keys.
      if ((key === 'location' ||
        key === 'room' ||
        key === 'program' ||
        key === 'category' ||
        key === 'class' ||
        key === 'age' ||
        key === 'date' ||
        key === 'time') &&
        parameters[key] !== 'all') {
        params.push(key + '=' + parameters[key]);
      }
      // Handle the display.
      if (key === 'display' && parameters[key] !== 0 && parameters[key] !== null) {
        params.push(key + '=' + parameters[key]);
      }
    }
    history.replaceState({}, '', window.location.pathname + '?' + params.join('&'));
  };

  Drupal.behaviors.ygs_schedules = {
    attach: function(context, settings) {
      $('.ygs-schedules-search-form .js-form-item-date input').datepicker({
        onSelect: function(dateText, ins) {
          $(this)
            .parents('.ygs-schedules-search-form')
            .each(function() {
              var form = $(this);
              // Trigger the click before "disabled". Otherwise inputs become NULL.
              form.find('.js-form-submit').trigger('click');
              form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', true);
            });
        }
      });

      $(document)
        .once()
        .ajaxSuccess(function(e, xhr, settings) {
          if (settings.data.match('form_id=ygs_schedules_search_form')) {
            var form = $('.ygs-schedules-search-form');
            form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', false);
            // If weekly view, ensure time field is disabled.
            if (form.find('.js-form-item-display input').prop('checked')
              && !form.find('.js-form-item-time select').prop('disabled')) {
              form.find('.js-form-item-time select').prop('disabled', true);
            }
            form.find('.filters-container').addClass('hidden');
            if (form.find('.filter').length !== 0) {
              form.find('.filters-container').removeClass('hidden');
            }
            form.find('.add-filters').removeClass('hidden');
            form.find('.close-filters').addClass('hidden');
            form.find('.selects-container').addClass('hidden-xs');
          }
        });

      $('.schedule-sessions-group-slider').each(function() {
        var view = $(this);

        // Initialize Slick.
        if (!view.hasClass('slick-initialized')) {
          view.slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 3,
            prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>',
            responsive: [{
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                arrows: true,
                dots: true
              }
            }, {
              breakpoint: 480,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                arrows: true,
                dots: true
              }
            }, {
              breakpoint: 768,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                arrows: true,
                dots: true
              }
            }]
          });
        }
      });

      $('.ygs-schedules-search-form').each(function() {
        var form = $(this);
        // Filters actions.
        form.find('.add-filters')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.selects-container').removeClass('hidden-xs');
            form.find('.close-filters').removeClass('hidden');
            form.find('.filters-container').addClass('hidden');
            $(this).addClass('hidden');
          });
        form.find('.close-filters')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.selects-container').addClass('hidden-xs');
            form.find('.add-filters').removeClass('hidden');
            form.find('.filters-container').removeClass('hidden');
            $(this).addClass('hidden');
          });

        form.find('.js-form-type-select select')
          .on('change', function() {
            form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', true);
          });

        form.find('.js-form-type-checkbox input')
          .on('change', function() {
            form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', true);
          });

        form.find('.filter .remove')
          .on('click', function(e) {
            e.preventDefault();
            form.parents('.filter').remove();
            form.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            if (form.find('.filter').length === 0) {
              form.find('.filters-main-wrapper').addClass('hidden');
            }
            // Trigger the click before "disabled". Otherwise inputs become NULL.
            form.find('.js-form-submit').trigger('click');
            form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', true);
          });

        form.find('.clear')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.filters-main-wrapper').find('a.remove').each(function() {
              form.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            });
            // Trigger the click before "disabled". Otherwise inputs become NULL.
            form.find('.js-form-submit').trigger('click');
            form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', true);
          });

        // Handle preferred location.
        setTimeout(function() {
          var preferred_branch = $.cookie('ygs_preferred_branch');
          var location = Drupal.behaviors.ygs_popups_autoload.get_query_param().location;
          if (typeof location == 'undefined' && typeof preferred_branch !== 'undefined') {
            form.find('.js-form-item-location select')
              .val(preferred_branch)
              .trigger('change');
          }
        }, 0);
      });

      $('.schedule-sessions-group').once().each(function() {
        var group = $(this);

        var form = $('.ygs-schedules-search-form'),
          input = form.find('input[name="date"]');

        var filter_date_string = input.val().split('/'),
          filter_date = new Date(filter_date_string[2], filter_date_string[0] - 1, filter_date_string[1]),
          today = new Date();
          today.setHours(0,0,0,0);
        if (today >= filter_date) {
          group.find('.prev-week').addClass('hidden');
        }
        else {
          group.find('.prev-week').removeClass('hidden');
        }
        group.find('.week-control').on('click', function(e) {
          e.preventDefault();
          if (!$(this).hasClass('week-control-processing')) {
            var current_date = input.val().split('/'),
              date = new Date(current_date[2], current_date[0] - 1, current_date[1]);

            if ($(this).hasClass('prev-week')) {
              date.setDate(date.getDate() - 7);
            }
            if ($(this).hasClass('next-week')) {
              date.setDate(date.getDate() + 7);
            }

            var new_date = date.format('mm/dd/yyyy');
            input.val(new_date);
            // Trigger the click before "disabled". Otherwise inputs become NULL.
            form.find('.js-form-submit').trigger('click');
            form.find('.js-form-type-select select, input.form-checkbox, input.hasDatepicker').attr('disabled', true);
            $(this).addClass('week-control-processing');

            var filter_date_string = input.val().split('/'),
              filter_date = new Date(filter_date_string[2], filter_date_string[0] - 1, filter_date_string[1]),
              today = new Date();
              today.setHours(0,0,0,0);
            if (today >= filter_date) {
              group.find('.prev-week').addClass('hidden');
            }
            else {
              group.find('.prev-week').removeClass('hidden');
            }
          }
        });
      });
    }
  };
})(jQuery);
