(function ($) {
    'use strict';
    $(document).on('keyup', '.rn-search', function () {
        var value = $(this).val().toLowerCase();
        $('.rn-data-table tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    $(document).on('click', '.rn-toolbar .btn', function () {
        $(this).addClass('active').siblings().removeClass('active');
    });
})(jQuery);

$(document).on('change', '.restaurant-new-menu select[name="menu_category_id"]', function () {
    if ($(this).closest('form').hasClass('rn-filter-row')) {
        $(this).closest('form').submit();
    }
});
