(function ($) {
    'use strict';
    $(document).on('change', '#product_image', function () {
        var file = this.files && this.files[0] ? this.files[0].name : '';
        $('.product-image-file-name').text(file);
    });
})(jQuery);
