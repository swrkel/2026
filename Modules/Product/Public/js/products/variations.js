(function ($) {
    'use strict';
    var rowIndex = $('.product-variation-row').length || 0;

    $(document).on('click', '.product-add-variation-row', function (e) {
        e.preventDefault();
        rowIndex++;
        var template = $('#product_variation_row_template').html();
        if (!template) { return; }
        template = template.replace(/__INDEX__/g, rowIndex);
        $('#product_variation_rows').append(template);
    });

    $(document).on('click', '.product-remove-variation-row', function (e) {
        e.preventDefault();
        $(this).closest('.product-variation-row').remove();
    });
})(jQuery);
