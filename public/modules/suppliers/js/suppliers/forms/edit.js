(function ($) {
    'use strict';

    $(document).ready(function () {
        var $form = $('#supplier-edit-form');

        $form.on('submit', function () {
            $form.find('button[type="submit"]').prop('disabled', true);
        });
    });
})(jQuery);
