(function ($) {
    'use strict';

    var DATE_CONTROL_SELECTOR = [
        'input[type="date"]',
        'input[type="datetime-local"]',
        'input.datepicker',
        'input.date-picker',
        'input.datetimepicker',
        'input[data-datepicker]',
        'input[data-supplier-date]'
    ].join(',');

    function makeSupplierDateControlsInteractive($form) {
        var $dateControls = $form.find(DATE_CONTROL_SELECTOR);

        if (!$dateControls.length) {
            return;
        }

        $dateControls.each(function () {
            var input = this;
            var $input = $(input);

            if ($input.prop('disabled')) {
                return;
            }

            /*
             * Add Supplier date fields must remain user-selectable. Some host
             * layouts mark picker-backed fields readonly to prevent free text;
             * when the picker is blocked/overlaid that makes the field
             * impossible to use. On the create form we deliberately allow the
             * control to receive input while keeping any server validation
             * unchanged.
             */
            $input.prop('readonly', false).removeAttr('readonly');

            /* Native date controls are the safest fallback and work even when
             * a third-party picker from the host layout is unavailable. */
            if (input.type === 'date' || input.type === 'datetime-local') {
                $input
                    .off('.supplierCreateDate')
                    .on('click.supplierCreateDate focus.supplierCreateDate', function () {
                        if (typeof input.showPicker === 'function') {
                            try {
                                input.showPicker();
                            } catch (ignore) {
                                // Browser may reject showPicker on a synthetic focus;
                                // the native control remains usable normally.
                            }
                        }
                    });
                return;
            }

            /* If the application already attached Bootstrap datetimepicker,
             * ask that existing instance to open. Do not create a second picker. */
            $input
                .off('click.supplierCreateDate focus.supplierCreateDate')
                .on('click.supplierCreateDate focus.supplierCreateDate', function () {
                    var picker = $input.data('DateTimePicker');
                    if (picker && typeof picker.show === 'function') {
                        picker.show();
                    }
                });
        });
    }

    $(document).ready(function () {
        var $form = $('#supplier-create-form');

        if (!$form.length) {
            return;
        }

        makeSupplierDateControlsInteractive($form);

        $form
            .off('click.supplierCreateDateTrigger', '.supplier-date-trigger')
            .on('click.supplierCreateDateTrigger', '.supplier-date-trigger', function () {
                var target = $(this).data('target');
                var input = target ? $form.find(target).get(0) : null;

                if (!input || input.disabled) {
                    return;
                }

                input.focus();

                if (typeof input.showPicker === 'function') {
                    try {
                        input.showPicker();
                        return;
                    } catch (ignore) {
                        // The focused native date input remains selectable.
                    }
                }

                $(input).trigger('click');
            });

        $form.on('submit', function () {
            $form.find('button[type="submit"]').prop('disabled', true);
        });

        $form.find('input[name="name"]').trigger('focus');
    });
})(jQuery);
