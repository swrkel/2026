(function ($) {
    'use strict';
    $(document).on('click', '.leads-new-delete', function (e) {
        if (!confirm('Are you sure you want to delete this Leads-New record?')) {
            e.preventDefault();
        }
    });
    $(document).on('click', '#leads_new_print', function () { window.print(); });
})(jQuery);
