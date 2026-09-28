(function ($) {
    'use strict';
    $(document).on('click', '.product-generate-barcode', function (e) {
        e.preventDefault();
        var sku = $('#sku').val() || ('PRD-' + Date.now());
        $('#barcode').val(sku);
    });
})(jQuery);
