/*
 * LA-1167 / IS1987 - Supplier list > Actions > Pay due amount
 *
 * Reported:
 *   1. After picking a date the field does not show the selected date.
 *   2. The month arrow buttons do nothing.
 *
 * WHY IT HAPPENS
 *
 * app.js (the IS1967 block) binds this picker with
 *     widgetParent: $payDueModal.find('.modal-content')
 * The calendar is therefore appended inside .modal-content and positioned
 * ABSOLUTELY. That only lands under the field if .modal-content is itself a
 * positioned element - and it is not by default. It only becomes
 * position:relative when erp-global-modal-system.js has added the
 * .erp-modal-system class, which happens independently of this binding.
 *
 * When .modal-content is still static, the browser positions the calendar
 * against the nearest positioned ancestor instead, which is why it appears
 * floating near the top-left of the dialog rather than beneath "Paid on" - and
 * the calendar the user is clicking is then sitting over unrelated markup, so
 * day clicks and the prev/next arrows land on whatever is underneath.
 *
 * THE FIX
 *
 * Anchor the calendar to the field's OWN .form-group and force that element to
 * position:relative, so it is guaranteed to be the offset parent. The calendar
 * then opens flush under its own field, exactly like the select2 dropdowns on
 * the tank and pump forms.
 *
 * This runs on shown.bs.modal, after app.js has finished, so it is the binding
 * that survives. app.js's dp.change handler stays attached to the input and
 * keeps writing the chosen value back, and a matching handler is bound here so
 * the value is still written if that block ever changes.
 *
 * Scope: loaded only from Modules/Suppliers/Resources/views/suppliers/index.blade.php,
 * and every lookup starts from the modal element the Suppliers list declares.
 */
(function ($) {
    'use strict';

    var MODAL_SELECTOR = '.pay_contact_due_modal, .linked_account_modal';
    var FIELD_SELECTOR = 'input#paid_on, input[name="paid_on"], .paid_on input';
    var WIDGET_SELECTOR = '.bootstrap-datetimepicker-widget';

    function dateTimeFormat() {
        var dateFormat = window.moment_date_format || 'MM/DD/YYYY';
        var timeFormat = window.moment_time_format || 'HH:mm';

        return {
            date: String(dateFormat),
            full: String(dateFormat) + ' ' + String(timeFormat)
        };
    }

    function destroyPicker($input) {
        var instance = $input.data('DateTimePicker');

        if (instance && typeof instance.destroy === 'function') {
            try {
                instance.destroy();
            } catch (error) {
                // An already-detached instance can throw here; the orphan sweep
                // below still clears whatever it left in the DOM.
            }
        }

        $input.removeData('DateTimePicker');
    }

    function removeOrphanWidgets($scope) {
        // Eonasdan appends the widget beside the field, or to <body> when the
        // field sits in an input group. Clear both so none is left behind.
        if ($scope && $scope.length) {
            $scope.find(WIDGET_SELECTOR).remove();
        }

        $('body').children(WIDGET_SELECTOR).remove();
    }

    /*
     * The element the calendar is attached to must be positioned, otherwise the
     * absolute placement is measured against some ancestor further up the page.
     */
    function anchorFor($input) {
        var $anchor = $input.closest('.form-group');

        if (!$anchor.length) {
            $anchor = $input.closest('.input-group').parent();
        }

        if (!$anchor.length) {
            $anchor = $input.parent();
        }

        if ($anchor.length && $anchor.css('position') === 'static') {
            $anchor.css('position', 'relative');
        }

        return $anchor;
    }

    function seedValue($input, formats) {
        var raw = $.trim(String($input.val() || ''));
        var instance = $input.data('DateTimePicker');

        if (!raw || !instance || typeof window.moment !== 'function') {
            return;
        }

        // Parse with the business format first. Falling back to a bare date keeps
        // a value rendered without a time component usable.
        var parsed = window.moment(raw, formats.full, true);

        if (!parsed.isValid()) {
            parsed = window.moment(raw, formats.date, true);
        }

        if (parsed.isValid()) {
            instance.date(parsed);
        }
    }

    /**
     * IS2056: remove the duplicate "Paid on" inputs.
     *
     * The modal was showing THREE date controls stacked: the real paid_on field
     * (empty, showing its mm/dd/yyyy placeholder) and two extra date inputs
     * below it carrying the value. Saving then failed with "This field is
     * required" and "Invalid inputs, Check & try again!!", because validation
     * reads the named field - which was the empty one.
     *
     * So the two symptoms in the ticket are one fault: the value was being typed
     * into a control that is not the field the form submits.
     *
     * The modal body is rendered by the main application, so this cannot be
     * fixed at source from inside the module. Instead the extras are removed
     * here, on every open, and any value they hold is moved onto the real field
     * first so nothing the user chose is lost.
     *
     * Kept deliberately narrow: only date-like inputs inside the SAME form group
     * as paid_on are considered, and the first named paid_on input is always the
     * one kept.
     */
    function removeDuplicateDateInputs($modal) {
        var $named = $modal.find('input[name="paid_on"]');

        if ($named.length === 0) {
            return;
        }

        // The field the form actually submits.
        var $keep = $named.first();
        var $group = $keep.closest('.form-group, .input-group, .col-md-4, .col-sm-4');

        if ($group.length === 0) {
            $group = $keep.parent();
        }

        var $candidates = $group.find('input[type="date"], input[type="datetime-local"], input[name="paid_on"]');

        $candidates.each(function () {
            var $input = $(this);

            if ($input.get(0) === $keep.get(0)) {
                return;
            }

            // Carry a value across before discarding the duplicate, so a date the
            // user already picked is not thrown away.
            var value = $.trim(String($input.val() || ''));

            if (value !== '' && $.trim(String($keep.val() || '')) === '') {
                $keep.val(value);
            }

            $input.remove();
        });
    }

    /**
     * IS2056: guarantee the field is usable even with no picker plugin.
     *
     * paid_on is rendered READONLY, on the assumption that a JavaScript picker
     * will supply the value. When that plugin is not loaded, the field cannot be
     * typed into and no picker opens - so it stays empty, and Save fails on
     * "This field is required".
     *
     * Making it a native datetime-local input removes the dependency entirely:
     * every current browser then provides its own calendar and clock. Applied
     * ONLY when no picker plugin is present, so where the plugin does load the
     * existing behaviour and formatting are untouched.
     */
    function ensureNativeFallback($modal) {
        var $input = $modal.find('input[name="paid_on"]').first();

        if ($input.length === 0 || $input.attr('type') === 'datetime-local') {
            return;
        }

        $input.removeAttr('readonly').prop('readonly', false);

        var raw = $.trim(String($input.val() || ''));
        var iso = '';

        if (raw !== '' && typeof window.moment === 'function') {
            var formats = dateTimeFormat();
            var parsed = window.moment(raw, [formats.full, formats.date, 'YYYY-MM-DD HH:mm'], false);

            if (parsed.isValid()) {
                iso = parsed.format('YYYY-MM-DDTHH:mm');
            }
        }

        $input.attr('type', 'datetime-local');

        // Never leave it blank - an empty required field is the reported failure.
        $input.val(iso !== ''
            ? iso
            : (typeof window.moment === 'function'
                ? window.moment().format('YYYY-MM-DDTHH:mm')
                : ''));
    }

    /**
     * IS2064: has the CORE modal already converted this field itself?
     *
     * pay_supplier_due_modal.blade.php now does the whole job:
     *
     *   - the visible input becomes a native datetime-local control
     *   - its `name` is REMOVED so it cannot post
     *   - a HIDDEN input carrying `paid_on` is inserted, written on every
     *     change in the business date format
     *   - the field is marked data('nativePaidOnBound') so it runs once
     *
     * That is a complete, deliberate solution - the comment above it records
     * that the field had been fixed five times with the picker library before
     * core handed the calendar to the browser instead.
     *
     * This module script predates that and does its own version of the same
     * thing. Running both is what produced the reported fault: after core
     * finishes, `input[name="paid_on"]` matches core's HIDDEN field, so this
     * script converted the hidden input into a second visible date control and
     * its duplicate-removal could strip the very field the form submits. Hence
     * three date boxes on screen and "This field is required" on save - the
     * value was in a control the form no longer posts.
     *
     * So when core has handled it, this script does nothing at all. The check
     * looks for either marker, so it holds whether or not the flag is set.
     */
    function coreHasHandledField($modal) {
        var $marked = $modal.find('input').filter(function () {
            return !!$(this).data('nativePaidOnBound');
        });

        if ($marked.length) {
            return true;
        }

        // Core's hidden partner field is the other reliable sign.
        return $modal.find('input[type="hidden"][name="paid_on"]').length > 0;
    }

    function rebindPicker($modal) {
        // IS2064: stand down entirely if the core modal already converted the
        // field. Two implementations of the same fix fight each other.
        if (coreHasHandledField($modal)) {
            return;
        }

        // IS2056: clear the duplicates before touching the picker, so the picker
        // is attached to the field that will actually be submitted.
        removeDuplicateDateInputs($modal);

        if (!$.fn.datetimepicker) {
            // No plugin on this page - fall back to the browser's own control
            // rather than leaving a readonly field nobody can fill in.
            ensureNativeFallback($modal);

            return;
        }

        var formats = dateTimeFormat();

        $modal.find(FIELD_SELECTOR).each(function () {
            var $input = $(this);

            destroyPicker($input);
            removeOrphanWidgets($input.closest('.form-group, .input-group, .modal-content'));

            var $anchor = anchorFor($input);

            $input.datetimepicker({
                format: formats.full,
                useCurrent: false,
                keepOpen: false,
                ignoreReadonly: true,
                widgetParent: $anchor,
                widgetPositioning: {
                    horizontal: 'auto',
                    vertical: 'bottom'
                }
            });

            // The field is rendered readonly, so the value can only arrive from
            // the picker. Writing it here means the selection always shows.
            $input.off('dp.change.supplierPayDuePicker')
                  .on('dp.change.supplierPayDuePicker', function (e) {
                      if (e.date && e.date.isValid && e.date.isValid()) {
                          $(this).val(e.date.format(formats.full));
                      }
                  });

            seedValue($input, formats);
        });
    }

    function teardown($modal) {
        // IS2064: nothing to tear down when core owns the field.
        if (coreHasHandledField($modal)) {
            return;
        }

        $modal.find(FIELD_SELECTOR).each(function () {
            destroyPicker($(this));
        });

        removeOrphanWidgets($modal);
    }

    $(document)
        .off('.supplierPayDuePicker')
        /*
         * The modal body is fetched over AJAX and app.js binds its own picker in
         * that success callback. shown.bs.modal fires afterwards, so rebinding
         * here leaves exactly one instance, anchored correctly.
         */
        .on('shown.bs.modal.supplierPayDuePicker', MODAL_SELECTOR, function () {
            rebindPicker($(this));
        })
        .on('hidden.bs.modal.supplierPayDuePicker', MODAL_SELECTOR, function () {
            teardown($(this));
        });
})(jQuery);
