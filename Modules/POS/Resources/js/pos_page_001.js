(function ($) {
    'use strict';

    $(document).on('keyup change', '.pos-instant-filter', function () {
        var value = String($(this).val() || '').toLowerCase();
        $(this).closest('.box').find('tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) !== -1);
        });
    });

    $(document).ready(function () {
        if ($.fn.select2) {
            $('.pos-typeahead').select2({ width: '100%', allowClear: true });
        }
    });
})(jQuery);
