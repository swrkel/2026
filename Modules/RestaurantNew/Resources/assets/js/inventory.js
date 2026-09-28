(function ($) {
    'use strict';
    $(document).on('input', '.restnew-recipe-line-qty,.restnew-recipe-line-cost', function () {
        var row = $(this).closest('tr');
        var qty = parseFloat(row.find('.restnew-recipe-line-qty').val() || 0);
        var cost = parseFloat(row.find('.restnew-recipe-line-cost').val() || 0);
        row.find('.restnew-recipe-line-total').text((qty * cost).toFixed(4));
    });
})(jQuery);
