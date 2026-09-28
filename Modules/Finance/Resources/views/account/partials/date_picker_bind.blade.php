{{--
 |------------------------------------------------------------------------------
 | IS2045: the deposit date field uses the BROWSER's own date picker.
 |------------------------------------------------------------------------------
 |
 | WHY THE APPROACH CHANGED
 |
 |   This field has been fixed six times - S-611, S-627, S-646, LA-1131,
 |   LA-1176, LA-1189 - and reported broken again each time. Every one of those
 |   attempts kept bootstrap-datetimepicker and worked around one of its
 |   behaviours: the input being blanked on a failed parse, the widget being
 |   positioned outside the modal, the widget being clipped, its buttons being
 |   shrunk by the compact-modal CSS, the plugin not being loaded yet when the
 |   ajax-injected form ran its script, a second orphaned widget swallowing
 |   clicks.
 |
 |   Each fix was reasonable and each was overtaken. The pattern says the
 |   problem is not any single one of those behaviours - it is that a
 |   library-drawn calendar inside an ajax-loaded modal, under a theme that
 |   restyles tables and buttons globally, has too many ways to fail.
 |
 |   So the calendar is no longer drawn by a library. The field becomes
 |   <input type="datetime-local">, which the browser renders and operates
 |   itself. There is nothing to position, nothing to clip, no z-index to lose,
 |   no plugin that might load late, and no second widget to go stale.
 |
 |   This is an established pattern here, not a departure: 34 core views already
 |   use native date inputs, including the Expenses filters and the customer
 |   payment edit form.
 |
 | WHAT IS PRESERVED
 |
 |   window.financeBindDatePicker keeps its name, its arguments and its return
 |   value, so every caller - Cash and Card Deposit, Cheque Deposit, Transfer -
 |   works unchanged. Callers use the returned object's .date() to read the
 |   chosen moment, and that still answers the same way.
 |
 |   The field still SUBMITS in the business date format. The visible native
 |   input is not the field that posts; a hidden input carrying the original
 |   name does, written on every change. The server side is untouched.
 --}}
<style>
    /* The native control supplies its own calendar button, so the addon icon is
       decorative here. Clicking it still opens the picker (see below). */
    .finance-native-date-addon { cursor: pointer; }

    /* Match the height and type styling of the fields around it. */
    input.finance-native-date {
        min-height: 34px;
        line-height: normal;
    }
</style>
<script type="text/javascript">
    (function ($) {
        'use strict';

        var NATIVE_FORMAT = 'YYYY-MM-DDTHH:mm';

        function report(message, config) {
            if (window.console && console.warn) {
                console.warn('[finance date field] ' + message, config || {});
            }
        }

        /**
         * Turn a date field into a native date-time input.
         *
         * param: {Object}  config
         * param: {jQuery}  config.picker  the `.input-group.date` wrapper
         * param: {jQuery}  config.input   the text input inside it
         * param: {String}  config.format  moment format the server expects
         * returns: {Object|null} an object exposing .date(), .hide(), .show()
         */
        window.financeBindDatePicker = function (config) {
            config = config || {};

            var $picker = config.picker;
            var $input = config.input;
            var format = config.format || 'MM/DD/YYYY HH:mm';

            if (!$picker || !$picker.length) {
                report('no date field found to bind to', config);
                return null;
            }

            if (!$input || !$input.length) {
                report('date field has no input inside it', config);
                return null;
            }

            if (typeof moment !== 'function') {
                report('moment.js is not loaded, so no date can be parsed', config);
                return null;
            }

            var input = $input[0];

            // Already converted - the deposit form re-runs its script when the
            // account changes, and converting twice would lose the hidden field.
            if ($input.data('financeNativeDateBound')) {
                return $input.data('financeNativeDateApi');
            }

            /*
             * The hidden field keeps the ORIGINAL name, so the request body is
             * byte-for-byte what it was before and no server code changes.
             */
            var submitName = $input.attr('name');
            var $hidden = $('<input>', { type: 'hidden', name: submitName });

            // Seed both from whatever the server rendered into the text box.
            var initial = $.trim($input.val());
            var seeded = initial ? moment(initial, format, true) : null;

            if (seeded && !seeded.isValid()) {
                // Fall back to a lenient parse; a value the server rendered is
                // worth keeping even if it does not match the format exactly.
                seeded = moment(initial);
            }

            if (!seeded || !seeded.isValid()) {
                seeded = null;
            }

            $hidden.val(seeded ? seeded.format(format) : initial);
            $input.after($hidden);

            /*
             * The visible input becomes native and stops posting. Its name is
             * removed rather than renamed so nothing can bind to it by accident.
             */
            $input
                .removeAttr('name')
                // A number of Finance forms rendered the legacy text field as
                // readonly because bootstrap-datetimepicker owned all editing.
                // Native datetime-local controls must be editable or Chrome/Opera
                // will display the calendar but refuse to change the value.
                .removeAttr('readonly')
                .attr('type', 'datetime-local')
                .attr('step', '60')
                .addClass('finance-native-date')
                .val(seeded ? seeded.format(NATIVE_FORMAT) : '');

            function currentMoment() {
                var raw = $.trim($input.val());

                if (!raw) {
                    return null;
                }

                var parsed = moment(raw, NATIVE_FORMAT, true);

                return parsed.isValid() ? parsed : null;
            }

            function sync() {
                var picked = currentMoment();

                // An empty or half-typed value must not wipe a good stored one
                // until the field genuinely holds something new.
                if (picked) {
                    $hidden.val(picked.format(format));
                }
            }

            $input.on('change.financeNativeDate input.financeNativeDate', sync);

            // The addon icon opens the browser's picker where the browser allows
            // it, and focuses the field where it does not.
            $picker.find('.input-group-addon, .input-group-btn')
                .addClass('finance-native-date-addon')
                .off('click.financeNativeDate')
                .on('click.financeNativeDate', function () {
                    if (typeof input.showPicker === 'function') {
                        try {
                            input.showPicker();
                            return;
                        } catch (e) {
                            // Not permitted in this context; fall through.
                        }
                    }

                    $input.trigger('focus');
                });

            /*
             * The same shape the old function returned, so callers reading
             * .date() keep working. Cash and Cheque Deposit both use it to
             * validate the chosen date before saving.
             */
            var api = {
                date: function (value) {
                    if (typeof value === 'undefined') {
                        return currentMoment();
                    }

                    var next = moment.isMoment(value) ? value : moment(value);

                    if (next && next.isValid()) {
                        $input.val(next.format(NATIVE_FORMAT));
                        $hidden.val(next.format(format));
                    }

                    return api;
                },
                // Retained so existing calls are safe; a native picker is closed
                // by the browser, so there is nothing to do.
                hide: function () { return api; },
                show: function () {
                    if (typeof input.showPicker === 'function') {
                        try { input.showPicker(); } catch (e) {}
                    }
                    return api;
                }
            };

            $input.data('financeNativeDateBound', true);
            $input.data('financeNativeDateApi', api);

            return api;
        };
    })(jQuery);
</script>
