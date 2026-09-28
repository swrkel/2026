{{--
 |------------------------------------------------------------------------------
 | S-666: the journal Date field uses the BROWSER's own date picker.
 |------------------------------------------------------------------------------
 |
 | WHY THIS APPROACH
 |
 |   The Add and Edit Journal forms are loaded into a modal by ajax and their
 |   inline scripts run the moment the HTML lands. The Date field was driven by
 |   bootstrap-datepicker, which has to be present at that instant, has to be
 |   positioned inside the modal, and has to survive the theme's global table and
 |   button styling. IS1992 already fixed one failure in that chain - Add called
 |   .datepicker('setDate') on a field the picker had never been initialised on,
 |   so it either did nothing or attached one with a DEFAULT format, writing a
 |   string the server's uf_date() could not parse against the business format.
 |
 |   The deposit forms in this module had the same class of problem and were
 |   fixed six times before the calendar was handed to the browser instead
 |   (IS2045). This does the same thing for the journal, for the same reason:
 |   there is nothing to load in time, nothing to position, nothing to restyle
 |   and nothing to leave orphaned.
 |
 |   Native date inputs are already used in 34 core views of this application, so
 |   this is the established pattern rather than a new one.
 |
 | WHAT IS PRESERVED
 |
 |   IS2078: the field SUBMITS the browser's unambiguous ISO value (Y-m-d).
 |   The visible native input does not post; a hidden input carrying the original
 |   name does. This prevents day/month reversal regardless of tenant format.
 |
 |   Called by both journal/create.blade.php and journal/edit.blade.php, so the
 |   two cannot drift apart the way they did under IS1992.
 --}}
<script type="text/javascript">
    (function ($) {
        'use strict';

        /*
         * The business date format is expressed for bootstrap-datepicker
         * ("mm/dd/yyyy"); moment needs it in its own notation ("MM/DD/YYYY").
         * Only the tokens the setting can contain are translated.
         */
        function momentFormatFromPickerFormat(pickerFormat) {
            var format = String(pickerFormat || 'mm/dd/yyyy');

            return format
                .replace(/dd/g, 'DD')
                .replace(/mm/g, 'MM')
                .replace(/yyyy/g, 'YYYY')
                .replace(/yy/g, 'YY');
        }

        /**
         * Convert every .journal_date field inside a container into a native
         * date input backed by a hidden field in the business format.
         *
         * param: {jQuery} $context  the modal (or document) to search
         * param: {String} [initial] date to preselect, in the business format;
         *                          today is used when omitted
         */
        window.financeBindJournalDate = function ($context, initial) {
            var $scope = $context && $context.length ? $context : $(document);

            /*
             * IS2046: use the moment format the application already publishes.
             *
             * layouts/partials/javascripts.blade.php derives BOTH globals from
             * session('business.date_format') - the same setting uf_date() parses
             * with on the server:
             *
             *     $datepicker_date_format  ->  window.datepicker_date_format
             *     $moment_date_format      ->  window.moment_date_format
             *
             * Taking moment_date_format directly removes the translation step
             * below, and with it any chance of this file and the server reading
             * the same date differently.
             *
             * That difference is what produced the reported swap: a date entered
             * as 1 August was submitted as 01/08/2026, and where the business
             * format is m/d/Y uf_date() read it as 8 January - successfully, so
             * nothing raised an error and the wrong date was stored. Writing the
             * hidden field in the business format is what stops it.
             */
            var format = (typeof moment_date_format !== 'undefined' && moment_date_format)
                ? moment_date_format
                : momentFormatFromPickerFormat(
                    (typeof datepicker_date_format !== 'undefined' && datepicker_date_format)
                        ? datepicker_date_format
                        : 'mm/dd/yyyy'
                );

            $scope.find('.journal_date').each(function () {
                var $input = $(this);
                var input = this;

                // The Add form re-runs its script when the account changes;
                // converting twice would discard the hidden field.
                if ($input.data('financeJournalDateBound')) {
                    return;
                }

                if (typeof moment !== 'function') {
                    if (window.console && console.warn) {
                        console.warn('[finance journal date] moment.js is not loaded; the date field is left as plain text.');
                    }
                    return;
                }

                var submitName = $input.attr('name');
                var $hidden = $('<input>', { type: 'hidden', name: submitName });

                /*
                 * Which date to show: the one passed in (Edit supplies the saved
                 * date), otherwise whatever the server rendered, otherwise today.
                 */
                var seedText = $.trim(String(initial || '')) || $.trim(String($input.val() || ''));
                var seeded = seedText ? moment(seedText, format, true) : null;

                if (seeded && !seeded.isValid()) {
                    seeded = moment(seedText);              // lenient second try
                }

                if (!seeded || !seeded.isValid()) {
                    seeded = moment();                      // today
                }

                $hidden.val(seeded.format('YYYY-MM-DD'));
                $input.after($hidden);

                $input
                    .removeAttr('name')                     // so it cannot post
                    .attr('type', 'date')
                    .val(seeded.format('YYYY-MM-DD'));      // native inputs are ISO

                $input.on('change.financeJournalDate input.financeJournalDate', function () {
                    var raw = $.trim(String($input.val() || ''));

                    if (!raw) {
                        return;                             // do not wipe a good value
                    }

                    var picked = moment(raw, 'YYYY-MM-DD', true);

                    if (picked.isValid()) {
                        $hidden.val(picked.format('YYYY-MM-DD'));
                    }
                });

                // Where the browser allows it, clicking the field opens the picker
                // rather than only placing a cursor.
                $input.on('focus.financeJournalDate', function () {
                    if (typeof input.showPicker === 'function') {
                        try { input.showPicker(); } catch (e) {}
                    }
                });

                $input.data('financeJournalDateBound', true);
            });
        };
    })(jQuery);
</script>
