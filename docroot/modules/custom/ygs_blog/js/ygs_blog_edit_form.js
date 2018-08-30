(function ($) {
  "use strict";

  Drupal.behaviors.ygs_blog_edit_form = {
    attach: function (context, settings) {
      $('#select-all', context).once().click(function (e) {
        $(this).parents('.form-item').find('option').prop('selected', true);
        e.preventDefault();
      });
    }
  };

})(jQuery);
