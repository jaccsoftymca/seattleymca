(function ($) {
  "use strict";
  Drupal.ymca_seattle =  Drupal.ymca_seattle || {};

  Drupal.behaviors.ymca_seattle_theme = {
    attach: function (context, settings) {
      $('.ui-tabs').tabs({
        active: false,
        collapsible: true
      });

      $(window).load(function () {

      });
      $(window).resize(function () {
        Drupal.ymca_seattle.branch__updates_queue_mobile();
        Drupal.ymca_seattle.location__timeschedule_mobile();
      });

      // Drupal.ymca_seattle.branch__updates_queue();
      Drupal.ymca_seattle.branch__updates_queue_mobile();
      Drupal.ymca_seattle.location__timeschedule_mobile();
    }
  };

  Drupal.ymca_seattle.branch__updates_queue_mobile = function () {
    $('.branch__updates_queue').each(function () {
      var view = $(this);

      // Initialize Slick.
      if (!view.find('.row').hasClass('slick-initialized')) {
        if ($(window).width() < 768) {
          view.find('.row').slick({
            responsive: [
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1,
                  infinite: true,
                  speed: 300,
                  arrows: true,
                  dots: true,
                  prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
                  nextArrow: '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>'
                }
              }]
          });
        }
      }
      // Slick is initialised but we check if it fits screen size.
      else if ($(window).width() >= 768) {
        view.find('.row').slick('unslick');
      }
    });
  };

  Drupal.ymca_seattle.location__timeschedule_mobile = function () {
    $('.timeshift').each(function () {
      var view = $(this);

      // Initialize Slick.
      if (!view.find('.row').hasClass('slick-initialized')) {
        if ($(window).width() < 1024) {
          view.find('.row').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: false,
            speed: 300,
            arrows: true,
            dots: false,

            variableWidth: true,
            prevArrow: '<i class="slick-prev fa fa-chevron-left"></i>',
            nextArrow: '<i class="slick-next fa fa-chevron-right"></i>',
            responsive: [
              {
                breakpoint: 480,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1,
                  infinite: false,
                  speed: 300,
                  arrows: true,
                  dots: false,

                  variableWidth: false,
                  prevArrow: '<i class="slick-prev fa fa-chevron-left"></i>',
                  nextArrow: '<i class="slick-next fa fa-chevron-right"></i>'
                }
              }]
          });
        }
      }
      // Slick is initialised but we check if it fits screen size.
      else if ($(window).width() >= 960) {
        view.find('.row').slick('unslick');
      }
    });
  };

  // Sidebar collapsible.
  Drupal.behaviors.sidebar = {
    attach: function (context, settings) {
      var current_scroll = 0;
      $('.sidebar')
        .once()
        .on('show.bs.collapse',
          // Add custom class for expand specific styling. in = open.
          function (e) {
            $(this)
              .next('.viewport')
              .addBack()
              .removeClass('out')
              .addClass('collapsing-in');

            current_scroll = $(window).scrollTop();
            $('.nav-global').css({
              top: current_scroll
            });
          }
        )
        .on('shown.bs.collapse',
          // Allow css to control open rest state.
          function () {
            $(this)
              .next('.viewport')
              .addBack()
              .removeClass('collapsing-in')
              .addClass('in');

            var body =  $('body');

            body.addClass('sidebar-in');

            $('html').addClass('sidebar-in');
          }
        )
        .on('hide.bs.collapse',
          // Add custom class for collapse specific styling. out = closed.
          function (e) {
            var sidebar = $(this);
            sidebar
              .next('.viewport')
              .addBack()
              .removeClass('in')
              .addClass('collapsing-out');

            $(window).scrollTop(current_scroll);

            $('#page-head').css({
              marginTop: ''
            });

          }
        )
        .on('hidden.bs.collapse',
          // Allow css to control closed rest state.
          function () {
            $(this)
              .next('.viewport')
              .addBack()
              .addClass('out')
              .removeClass('collapsing-out');

            $('body').removeClass('sidebar-in');
            $('html').removeClass('sidebar-in');

            $('.nav-global').css({
              top: 0
            });
          }
        )
        .find('li')
        .on('hide.bs.dropdown',
          // For nested dropdowns, prevent collapse of other dropdowns.
          function (e) {
            e.preventDefault();
          }
        );
    }
  };

  // Sidebar collapsible menu items.
  Drupal.behaviors.sidebarMenuCollapsible = {
    attach: function (context, settings) {
      $('.sidebar .dropdown-toggle').on('click', function () {
        var expanded = $(this).attr('aria-expanded');
        if (expanded == 'true') {
          $(this).removeAttr('aria-expanded');
          $(this).parent().removeClass('open');
          return false;
        }
      });
    }
  };

  /**
   * Fixes issues with IE11 on the locations schedule page.
   */
  Drupal.behaviors.locationsFilterFix = {
    attach: function (context, settings) {
      if (Object.hasOwnProperty.call(window, "ActiveXObject") && !window.ActiveXObject) {
        $('html').addClass('ie11');
      }
    }
  };

})(jQuery);
