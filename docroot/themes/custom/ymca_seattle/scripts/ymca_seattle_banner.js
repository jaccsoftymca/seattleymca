(function ($) {

  "use strict";

  Drupal.behaviors.ymca_seattle_banner = {
    timer: null,
    prev_state: 'small',
    attach: function (context, settings) {

      // Prevent resize callback to be called to often.
      function bannerResponsive() {
        if (Drupal.behaviors.ymca_seattle_banner.timer) {
          clearTimeout(Drupal.behaviors.ymca_seattle_banner.timer);
        }
        Drupal.behaviors.ymca_seattle_banner.timer = setTimeout(bannerResponsiveCallback, 100);
      }

      function bannerResponsiveCallback() {
        $('.paragraph--type--banner').each(function() {
          var $banner = $(this);
          var $cta = $('.banner-cta-section', $banner);
          var ww = $(window).width();
          var state = ww > 991 ? 'large' : 'small';

          if (state != 'small') {
            var $banner_image_element = $('img', $banner);
            $cta.css({
              fontSize: '1em',
              height: 'auto'
            });
            var banner_height = $banner.height();
            var height = $banner_image_element.height();
            if (banner_height >= height) {
              $cta.css({
                fontSize: (height / banner_height) + 'em',
                height: height
              });
            }
            else {
              var width = $banner_image_element.width();
              var k = width - ww / 2;
              var ratio = height / width;
              height = ratio * ww /2 + k * ratio;
              $cta.css({height: height});
            }

          }
          else if (Drupal.behaviors.ymca_seattle_banner.prev_state != 'small') {
            $cta.css({
              fontSize: '1em',
              height: 'auto'
            });
          }
          Drupal.behaviors.ymca_seattle_banner.prev_state = state;
        });
      }

      $(window)
        .on('resize.bannerResponsive', bannerResponsive)
        .trigger('resize.bannerResponsive')
        .on('load', function () {
          $(this).trigger('resize.bannerResponsive');
        });
    }
  };

})(jQuery);
