/**
 * A second membership JS file because it's unknown what
 * "ymca_seattle_membership.js" does.
 */
(function ($) {
  "use strict";

  Drupal.behaviors.ymca_seattle_membership_pg_autoscroll = {
    attach: function (context, settings) {
      var form = '.calc-block-form';
      this.scrollDownTo(form, '.membership-type input.form-radio');
      this.scrollDownTo(form, '.form-item-location input.form-radio');
      $(document, context).ajaxComplete(function(event, xhr, settings) {
        if (settings.data.indexOf("form_id=calc_block_form") === -1) {
          return;
        }
        $('html, body').stop().animate({
            scrollTop: $(form).offset().top
        }, {
          duration: 900
        });
      });
    },
    scrollDownTo: function(container, element) {
      if (!$(container).length || !$(container + ' ' + element).length) {
        return;
      }
      $(container).once('auto-scroll').each(function() {
        var self = this;
        var radios = $(this).find(element);
        radios.click(function() {
          var submit = $(self).find('input.form-submit').first();
          $('html, body').stop().animate({
              scrollTop: submit.offset().top + submit.outerHeight(true) - $(window).height()
          }, {
            duration: 800
          });
        });
      });
    }
  };
})(jQuery);
