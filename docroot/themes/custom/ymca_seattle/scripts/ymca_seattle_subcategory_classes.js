(function($) {
  "use strict";

  Drupal.behaviors.ymca_seattle_subcategory_classes_theme = {
    attach: function(context, settings) {

      $(document)
        .once()
        .ajaxSuccess(function(e, xhr, settings) {
          if (settings.data.match('view_name=sub_category_classes&view_display_id=search_form')) {
            var view = $('.sub-category-classes-view');
            view.find('.js-form-type-select select').attr('disabled', false);
            view.find('.filters-container').addClass('hidden');
            if (view.find('.filter').length !== 0) {
              view.find('.filters-container').removeClass('hidden');
            }
          }
        });

      $('.sub-category-classes-view').once().each(function() {
        var view = $(this);

        // Initialize Slick.
        view.find('.activity-group-slider').slick({
          dots: true,
          infinite: false,
          speed: 300,
          slidesToShow: 3,
          slidesToScroll: 3,
          prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
          nextArrow: '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>',
          responsive: [
            {
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                dots: true,
                arrows: true
              }
            },
            {
              breakpoint: 768,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                dots: true,
                arrows: true
              }
            }
          ]
        });

        // Filters actions.
        view.find('.add-filters')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.selects-container').removeClass('hidden-xs');
            view.find('.close-filters').removeClass('hidden');
            view.find('.filters-container').addClass('hidden');
            $(this).addClass('hidden');
        });
        view.find('.close-filters')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.selects-container').addClass('hidden-xs');
            view.find('.add-filters').removeClass('hidden');
            view.find('.filters-container').removeClass('hidden');
            $(this).addClass('hidden');
          });

        view.find('.js-form-type-select select')
          .change(function() {
            // Trigger the click before "disabled". Otherwise inputs become NULL.
            view.find('form .form-actions input:eq(0)').trigger('click');
            view.find('.js-form-type-select select').attr('disabled', true);
          });

        view.find('.filter .remove')
          .on('click', function(e) {
            e.preventDefault();
            view.parents('.filter').remove();
            view.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            if (view.find('.filter').length === 0) {
              view.find('.filters-container').addClass('hidden');
            }
            // Trigger the click before "disabled". Otherwise inputs become NULL.
            view.find('.actions-wrapper').find('input:eq(0)').trigger('click');
            view.find('.js-form-type-select select').attr('disabled', true);
          });

        view.find('.clear')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.filters-container').find('a.remove').each(function() {
              view.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            });
            // Trigger the click before "disabled". Otherwise inputs become NULL.
            view.find('.actions-wrapper').find('input:eq(0)').trigger('click');
            view.find('.js-form-type-select select').attr('disabled', true);
          });
      });
    }
  };
})(jQuery);
