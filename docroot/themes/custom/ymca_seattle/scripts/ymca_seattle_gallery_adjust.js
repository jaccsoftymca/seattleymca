(function ($) {

  "use strict";

  Drupal.behaviors.ymca_gallery_adjust = {
    attach: function (context, settings) {
      function galleryResponsive() {
        $('.paragraph-gallery').each(function() {
          var $gallery = $(this);
          var $text = $('.cta-group-wrapper .cta-group .text', $gallery);

          if ($(window).width() > 767) {
            var $wrapper = $('.cta-group-wrapper', $gallery);
            var $carousel = $('.carousel', $gallery);
            var zoom = $text.css('zoom');

            var carouselHeight = $carousel.height();
            // Hide/show hack to prevent percentage-to-pixels conversion.
            $wrapper.hide();
            var top = $wrapper.css('top');
            $wrapper.show();
            top = top == '50%' ? 0 : parseInt($wrapper.css('top'));

            var wrapperHeight = $wrapper.outerHeight() + top;
            if (wrapperHeight > carouselHeight || (wrapperHeight < carouselHeight * 0.98 && zoom < 1)) {
              $text.css({zoom: 1});
              zoom = 1;

              for (var i = 1; i < 8; i++) {
                wrapperHeight = $wrapper.outerHeight() + top;
                if (wrapperHeight > carouselHeight) {
                  zoom -= 1 / Math.pow(2, i);
                }
                else if (zoom < 1 && wrapperHeight < carouselHeight * 0.98) {
                  zoom += 1 / Math.pow(2, i);
                }
                else {
                  break;
                }
                $text.css({zoom: zoom});
              }
            }
          }
          else {
            $text.css({zoom: 1});
          }
        });
      }

      $('.paragraph-gallery', context).once().each(function () {
        $(window)
          .on('resize.galleryResponsive', galleryResponsive)
          .trigger('resize.galleryResponsive');

        $(window).on('load', function () {
          $(this).trigger('resize.galleryResponsive');
        });
      });
    }
  };

})(jQuery);
