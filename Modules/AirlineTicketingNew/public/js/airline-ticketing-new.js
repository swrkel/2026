(function ($) {
    'use strict';

    $(function () {
        $('.atn-content select').each(function () {
            var $select = $(this);
            if ($.fn.select2 && !$select.hasClass('select2-hidden-accessible')) {
                $select.select2({ width: '100%' });
            }
        });

        $('.atn-content form').on('submit', function () {
            $(this).find('button[type="submit"]').prop('disabled', true);
        });
    });
})(window.jQuery);
