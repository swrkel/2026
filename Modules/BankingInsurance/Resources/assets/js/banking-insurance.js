(function ($) {
    'use strict';
    $(document).on('change', '.banking-insurance-autosubmit', function () {
        $(this).closest('form').submit();
    });
})(jQuery);
