(function($) {
  "use strict";

  Drupal.behaviors.ygs_branch_sessions = {
    attach: function(context, settings) {

      function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
          return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
          return uri + separator + key + "=" + value;
        }
      }

      $(document)
        .once()
        .ajaxSuccess(function(e, xhr, settings) {
          if (settings.data.match('form_id=ygs_branch_sessions_form')) {
            var form = $('#branch-sessions-form-wrapper');
            form.find('.js-form-type-select select').removeAttr('readonly');
            form.find('.filters-main-wrapper').addClass('hidden');
            if (form.find('.filter').length !== 0) {
              form.find('.filters-main-wrapper').removeClass('hidden');
            }
            form.find('.add-filters').removeClass('hidden');
            form.find('.close-filters').addClass('hidden');
            form.find('.selects-container').addClass('hidden-xs');
          }
        });

      $('#branch-sessions-form-wrapper').each(function() {
        var view = $(this);

        // Initialize Slick.
        if (!view.find('.branch-sessions-group-slider').hasClass('slick-initialized')) {
          view.find('.branch-sessions-group-slider').slick({
            dots: true,
            infinite: false,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 3,
            arrows: true,
            prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>',
            responsive: [{
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                arrows: true
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

      $('.ygs-branch-sessions-form').each(function() {
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
            form.find('.js-form-type-select select').attr('readonly', true);
            var val = $(this).val(),
              view_full_schedule_link = form.find('.full-schedule'),
              href = view_full_schedule_link.attr('href');
              if ($(this).attr('name') === 'program') {
                href = updateQueryStringParameter(href, 'program', val);
              }
              if ($(this).attr('name') === 'when_day') {
                href = updateQueryStringParameter(href, 'date', val);
              }
              if ($(this).attr('name') === 'when_hours') {
                href = updateQueryStringParameter(href, 'time', val);
              }
              view_full_schedule_link.attr('href', href);
          });

        form.find('.filter .remove')
          .on('click', function(e) {
            e.preventDefault();
            form.parents('.filter').remove();
            form.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            if (form.find('.filter').length === 0) {
              form.find('.filters-main-wrapper').addClass('hidden');
            }
            form.find('.js-form-type-select select').attr('readonly', true);
            form.find('.js-form-submit').trigger('click');
          });

        form.find('.clear')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.filters-main-wrapper').find('a.remove').each(function() {
              form.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            });
            form.find('.js-form-type-select select').attr('readonly', true);
            form.find('.js-form-submit').trigger('click');
          });
      });
    }
  };
})(jQuery);
