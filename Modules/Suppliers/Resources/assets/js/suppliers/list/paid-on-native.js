/*
 * IS2083 - Suppliers > Pay Due / Advance Payment > Paid on.
 *
 * The payment form is rendered by the shared application payment endpoints.
 * Keep the Suppliers fix local to this page, but do not depend on the shared
 * Bootstrap datetimepicker lifecycle: that picker can be destroyed/recreated
 * after the AJAX modal is shown and previously caused the chosen date to
 * disappear.  This implementation owns one visible native datetime-local
 * control and one hidden `paid_on` submit value for the lifetime of the modal.
 *
 * Invariant:
 *   - clicking Paid on (or its calendar icon) always opens a usable calendar;
 *   - after the user chooses a date/time, that exact visible value is retained;
 *   - the hidden `paid_on` value is kept in the business date/time format used
 *     by the shared payment endpoint;
 *   - modal/shared-script reinitialisation may not clear the user's selection.
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    window.__supplierPaidOnIS2083 = '20260918-date-authority-1';

    var MODAL = '.pay_contact_due_modal';
    var STATE_KEY = 'supplier-paid-on-is2083-state';
    var OBSERVER_KEY = 'supplier-paid-on-is2083-observer';
    var REPAIR_KEY = 'supplier-paid-on-is2083-repairing';
    var OPTIONAL_NOTE_KEY = 'supplier-payment-note-optional';
    var PAYMENT_CONTEXT_KEY = 'supplier-payment-context';
    var PAID_ON_ISO_NAME = 'supplier_paid_on_iso';
    var repairTimers = [];

    function formats() {
        return {
            date: String(window.moment_date_format || 'YYYY-MM-DD'),
            time: String(window.moment_time_format || 'HH:mm')
        };
    }

    function parseIso(value) {
        value = $.trim(String(value || ''));
        if (!value) {
            return '';
        }

        var isoMatch = value.match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/);
        if (isoMatch) {
            return isoMatch[1] + 'T' + isoMatch[2];
        }

        var sqlMatch = value.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
        if (sqlMatch) {
            return sqlMatch[1] + 'T' + sqlMatch[2];
        }

        if (typeof window.moment !== 'function') {
            return '';
        }

        var f = formats();
        var parsed = window.moment(value, [
            f.date + ' ' + f.time,
            f.date + ' HH:mm',
            f.date + ' hh:mm A',
            'YYYY-MM-DD HH:mm:ss',
            'YYYY-MM-DD HH:mm',
            'DD/MM/YYYY HH:mm',
            'DD/MM/YYYY hh:mm A',
            'MM/DD/YYYY HH:mm',
            'MM/DD/YYYY hh:mm A'
        ], true);

        if (!parsed.isValid()) {
            parsed = window.moment(value);
        }

        return parsed.isValid() ? parsed.format('YYYY-MM-DDTHH:mm') : '';
    }

    function serverValue(iso) {
        iso = $.trim(String(iso || ''));
        if (!iso) {
            return '';
        }

        if (typeof window.moment !== 'function') {
            return iso.replace('T', ' ') + ':00';
        }

        var parsed = window.moment(iso, ['YYYY-MM-DDTHH:mm', 'YYYY-MM-DDTHH:mm:ss'], true);
        if (!parsed.isValid()) {
            return iso;
        }

        var f = formats();
        return parsed.format(f.date + ' ' + f.time);
    }

    function state($modal) {
        var value = $modal.data(STATE_KEY);
        if (!value || typeof value !== 'object') {
            value = { iso: '', userSelected: false };
            $modal.data(STATE_KEY, value);
        }
        return value;
    }

    function visibleField($modal) {
        var selectors = [
            'input[data-supplier-paid-on-visible="1"]',
            'input#paid_on:not([type="hidden"])',
            'input[name="paid_on"]:not([type="hidden"])',
            'input[data-native-paid-on]:not([type="hidden"])',
            'input[data-supplier-native-paid-on]:not([type="hidden"])',
            'input[type="datetime-local"]'
        ];

        for (var i = 0; i < selectors.length; i += 1) {
            var $candidate = $modal.find(selectors[i]).filter(':visible').first();
            if ($candidate.length) {
                return $candidate;
            }
        }

        // During the Bootstrap show transition jQuery can briefly report the
        // modal controls as not visible. Fall back to the first non-hidden date
        // candidate so the repair can still complete before the user clicks it.
        for (var j = 0; j < selectors.length; j += 1) {
            var $fallback = $modal.find(selectors[j]).first();
            if ($fallback.length) {
                return $fallback;
            }
        }

        return $();
    }

    function hiddenField($modal, $visible) {
        var $hidden = $modal.find('input[type="hidden"][name="paid_on"]').first();

        if (!$hidden.length) {
            $hidden = $('<input>', {
                type: 'hidden',
                name: 'paid_on',
                'data-supplier-paid-on-submit': '1'
            });
            $visible.after($hidden);
        }

        // The shared endpoint must receive one and only one paid_on value.
        $modal.find('input[type="hidden"][name="paid_on"]').not($hidden).remove();
        if ($visible.attr('name') === 'paid_on') {
            $visible.removeAttr('name');
        }

        return $hidden;
    }

    function destroyLegacyPicker($field) {
        try {
            var picker = $field.data('DateTimePicker');
            if (picker && typeof picker.destroy === 'function') {
                picker.destroy();
            }
        } catch (ignore) {
            // A partially initialised global picker must not block this field.
        }

        $field.removeData('DateTimePicker');
        $field.off('.supplierPayDuePicker');
    }

    function openNative(field) {
        if (!field) {
            return;
        }

        field.removeAttribute('readonly');
        field.removeAttribute('disabled');
        field.disabled = false;

        if (typeof field.showPicker === 'function') {
            try {
                field.showPicker();
            } catch (ignore) {
                // A normal browser click still opens the native picker.
            }
        }
    }

    function syncHidden($modal, $field, $hidden, userChange) {
        var currentState = state($modal);
        var currentIso = parseIso($field.val());

        if (userChange) {
            currentState.iso = currentIso;
            currentState.userSelected = currentIso !== '';
        } else if (currentIso !== '') {
            // A valid value rendered by the shared form is safe to adopt until
            // the user has explicitly chosen something. Once chosen, the user's
            // value takes priority over later AJAX/plugin rewrites.
            if (!currentState.userSelected) {
                currentState.iso = currentIso;
            }
        }

        if (currentState.iso !== '' && parseIso($field.val()) !== currentState.iso) {
            $field.val(currentState.iso);
        }

        $hidden.val(serverValue(currentState.iso)).attr('value', serverValue(currentState.iso));

        // S758: also submit one unambiguous ISO value owned by Suppliers.  The
        // server-side SupplierPaymentDateGuard uses this marker to make the root
        // Pay Due / Advance Payment row keep the exact date selected by the user,
        // regardless of the business display date format used by the shared form.
        var $iso = $modal.find('input[type="hidden"][name="' + PAID_ON_ISO_NAME + '"]').first();
        if (!$iso.length) {
            var $form = $field.closest('form');
            if (!$form.length) {
                $form = $modal.find('form').first();
            }
            if ($form.length) {
                $iso = $('<input>', {type: 'hidden', name: PAID_ON_ISO_NAME}).appendTo($form);
            }
        }
        if ($iso.length) {
            $iso.val(currentState.iso).attr('value', currentState.iso);
        }

        $modal.data(STATE_KEY, currentState);
    }


    /*
     * S678 - Supplier > Actions > Pay Due / Advance Payment.
     * The shared ERP payment modal can render Note as a required field. For
     * supplier due/advance payments Note is optional. Keep this adjustment
     * local to the Suppliers list and only to those two actions; do not change
     * validation rules for unrelated payment forms elsewhere in the system.
     */
    function syncPaymentContext($modal) {
        if (!$modal || !$modal.length) {
            return;
        }

        var context = String($modal.data(PAYMENT_CONTEXT_KEY) || '');
        var $forms = $modal.find('form');

        $forms.each(function () {
            var $form = $(this);
            var $field = $form.find('input[name="supplier_payment_context"]');

            if (context !== 'advance_payment' && context !== 'pay_due') {
                $field.remove();
                return;
            }

            if (!$field.length) {
                $field = $('<input>', {
                    type: 'hidden',
                    name: 'supplier_payment_context'
                }).appendTo($form);
            }

            $field.val(context).attr('value', context);
        });
    }

    function makePaymentNoteOptional($modal) {
        if (!$modal || !$modal.length || !$modal.data(OPTIONAL_NOTE_KEY)) {
            return;
        }

        var $notes = $modal.find([
            'textarea[name="note"]',
            'input[name="note"]',
            'textarea#note',
            'input#note',
            'textarea[name$="[note]"]',
            'input[name$="[note]"]'
        ].join(','));

        $notes.each(function () {
            var $note = $(this);

            $note
                .removeAttr('required')
                .removeAttr('aria-required')
                .removeClass('required')
                .prop('required', false);

            // jQuery Validate may have attached a runtime required rule even
            // after the modal HTML was loaded. Remove only that rule while
            // preserving any other validation attached to the Note control.
            if ($.fn.rules) {
                try {
                    $note.rules('remove', 'required');
                } catch (ignore) {
                    // The form may not have initialised jQuery Validate yet.
                }
            }

            var $group = $note.closest('.form-group');
            $group.removeClass('has-error');
            $group.find('label .required, label .text-danger[data-required-marker]').remove();

            // Remove a conventional trailing required asterisk without
            // changing the actual label text.
            $group.find('label').each(function () {
                var $label = $(this);
                $label.contents().filter(function () {
                    return this.nodeType === 3 && /\*\s*$/.test(this.nodeValue || '');
                }).each(function () {
                    this.nodeValue = (this.nodeValue || '').replace(/\s*\*\s*$/, '');
                });
                $label.find('span.text-danger').filter(function () {
                    return $.trim($(this).text()) === '*';
                }).remove();
            });
        });
    }

    function repair($modal) {
        if (!$modal || !$modal.length || $modal.data(REPAIR_KEY)) {
            return false;
        }

        makePaymentNoteOptional($modal);
        syncPaymentContext($modal);

        var $field = visibleField($modal);
        if (!$field.length) {
            return false;
        }

        $modal.data(REPAIR_KEY, true);

        try {
            var currentState = state($modal);
            var $hidden = hiddenField($modal, $field);

            // Capture the form's original value before touching the control.
            var renderedIso = parseIso($field.val()) || parseIso($hidden.val());
            if (!currentState.userSelected && renderedIso) {
                currentState.iso = renderedIso;
            }

            destroyLegacyPicker($field);

            if ($field.attr('type') !== 'datetime-local') {
                $field.attr('type', 'datetime-local');
            }

            $field
                .attr('step', '60')
                .attr('autocomplete', 'off')
                .attr('data-supplier-paid-on-visible', '1')
                .attr('data-supplier-native-paid-on', '1')
                .removeAttr('readonly')
                .removeAttr('disabled')
                .prop('readonly', false)
                .prop('disabled', false);

            if (currentState.iso && parseIso($field.val()) !== currentState.iso) {
                $field.val(currentState.iso);
            }

            syncHidden($modal, $field, $hidden, false);

            $field
                .off('.supplierPaidOn2083')
                .on('pointerdown.supplierPaidOn2083 mousedown.supplierPaidOn2083 focusin.supplierPaidOn2083', function () {
                    this.removeAttribute('readonly');
                    this.removeAttribute('disabled');
                    this.disabled = false;
                })
                .on('click.supplierPaidOn2083', function () {
                    openNative(this);
                })
                .on('input.supplierPaidOn2083 change.supplierPaidOn2083 dp.change.supplierPaidOn2083', function () {
                    syncHidden($modal, $field, $hidden, true);

                    // Some shared handlers run after change and rewrite the field.
                    // Restore the user's value after that event queue completes.
                    window.setTimeout(function () {
                        var selected = state($modal).iso;
                        if (selected && parseIso($field.val()) !== selected) {
                            $field.val(selected);
                        }
                        syncHidden($modal, $field, $hidden, false);
                    }, 0);
                })
                .on('blur.supplierPaidOn2083', function () {
                    syncHidden($modal, $field, $hidden, false);
                });

            var $inputGroup = $field.closest('.input-group');
            $inputGroup
                .find('.input-group-addon, .input-group-text, [data-action="togglePicker"], .fa-calendar, .glyphicon-calendar')
                .off('click.supplierPaidOn2083')
                .on('click.supplierPaidOn2083', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    $field.trigger('focus');
                    openNative($field.get(0));
                });

            $field.closest('form')
                .off('submit.supplierPaidOn2083')
                .on('submit.supplierPaidOn2083', function () {
                    makePaymentNoteOptional($modal);
                    syncPaymentContext($modal);

                    // Read the native control one final time at submit.  This is
                    // intentionally a userChange=true sync so the value visibly
                    // selected by the user wins even if a browser/plugin change
                    // event was delayed or swallowed immediately before Save.
                    syncHidden($modal, $field, $hidden, true);
                });

            return true;
        } finally {
            $modal.data(REPAIR_KEY, false);
        }
    }

    function clearRepairTimers() {
        while (repairTimers.length) {
            window.clearTimeout(repairTimers.pop());
        }
    }

    function scheduleRepairs($modal) {
        clearRepairTimers();
        [0, 30, 100, 250, 600, 1200].forEach(function (delay) {
            repairTimers.push(window.setTimeout(function () {
                repair($modal);
            }, delay));
        });
    }

    function startObserver($modal) {
        var oldObserver = $modal.data(OBSERVER_KEY);
        if (oldObserver && typeof oldObserver.disconnect === 'function') {
            oldObserver.disconnect();
        }

        if (typeof window.MutationObserver !== 'function' || !$modal.length) {
            return;
        }

        var observer = new window.MutationObserver(function () {
            // Guarded repair means observing the attributes is safe and lets us
            // undo a late shared-script readonly/type/name rewrite immediately.
            repair($modal);
        });

        observer.observe($modal.get(0), {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['readonly', 'disabled', 'type', 'name', 'required', 'aria-required', 'class']
        });

        $modal.data(OBSERVER_KEY, observer);
    }

    function initialise($modal) {
        repair($modal);
        scheduleRepairs($modal);
        startObserver($modal);
    }

    $(document)
        .off('.supplierPaidOn2083')
        .on('click.supplierPaidOn2083', '#supplier_records_table a.pay_purchase_due', function () {
            var $modal = $(MODAL);
            var href = String($(this).attr('href') || '');
            var isAdvancePayment = href.indexOf('/payments/advance-payment/') !== -1;
            var isPayDue = href.indexOf('/payments/pay-contact-due/') !== -1;
            var noteOptional = isPayDue || isAdvancePayment;

            $modal.data(STATE_KEY, { iso: '', userSelected: false });
            $modal.data(OPTIONAL_NOTE_KEY, noteOptional);
            $modal.data(PAYMENT_CONTEXT_KEY, isAdvancePayment ? 'advance_payment' : (isPayDue ? 'pay_due' : ''));
            $('.supplier-action-menu-detached').hide();
        })
        .on('shown.bs.modal.supplierPaidOn2083', MODAL, function () {
            initialise($(this));
        })
        .on('focusin.supplierPaidOn2083 pointerdown.supplierPaidOn2083', MODAL + ' input#paid_on, ' + MODAL + ' input[data-native-paid-on], ' + MODAL + ' input[data-supplier-paid-on-visible]', function () {
            // Delegated safety net for a field replaced after the modal opened.
            repair($(this).closest(MODAL));
        })
        .on('hidden.bs.modal.supplierPaidOn2083', MODAL, function () {
            clearRepairTimers();
            var $modal = $(this);
            var observer = $modal.data(OBSERVER_KEY);
            if (observer && typeof observer.disconnect === 'function') {
                observer.disconnect();
            }
            $modal.removeData(OBSERVER_KEY).removeData(REPAIR_KEY).removeData(STATE_KEY).removeData(OPTIONAL_NOTE_KEY).removeData(PAYMENT_CONTEXT_KEY);
        });
})(window.jQuery);
