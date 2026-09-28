{{--
 |------------------------------------------------------------------------------
 | S-627 #2b/#3b: the shared "Are you sure?" confirmation for Finance forms
 |------------------------------------------------------------------------------
 |
 | The brief supplies a screenshot rather than a spec, so the styling below
 | reproduces what is in it: a soft amber warning disc, a heavy near-black
 | title, the entered values as label/value rows separated by hairlines with
 | the amount picked out in amber, then a solid green Yes on the LEFT and a
 | pale red No on the RIGHT, both full width.
 |
 | WHY A PARTIAL
 |   S-627 asks for this dialog on Cheque Deposit (#2) and Card Deposit (#3).
 |   Cash and Card already share deposit.blade.php but Cheque Deposit is its
 |   own view, so writing it twice would guarantee the two drift apart. Both
 |   views include this file instead.
 |
 | WHICH SWEETALERT
 |   public/plugins/sweetalert/sweetalert.min.js is SweetAlert *v2* - the
 |   `swal({title, content, icon, buttons})` API that returns a promise
 |   resolving to the clicked button's `value`. This is NOT sweetalert2
 |   (`Swal.fire`), so no `customClass`/`showCancelButton` options here.
 |
 | ENTER DOES NOT CONFIRM
 |   SweetAlert focuses the last declared button, so No holds focus. Someone
 |   hitting Enter out of habit cancels; they cannot post a transaction by
 |   reflex. That was a deliberate S-611 decision and it is preserved.
 --}}
<style>
    /* Scoped to the className handed to swal(), so ordinary swal() calls
       elsewhere in the module keep their default appearance. */
    .swal-modal.finance-confirm-modal {
        width: 420px;
        max-width: 94vw;
        border-radius: 14px;
        padding: 26px 26px 20px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .28);
    }

    /* The amber disc. SweetAlert's warning icon is a ringed circle with a
       bar-and-dot exclamation inside it; filling the circle and recolouring
       the bar and dot turns it into the badge in the screenshot. */
    .swal-modal.finance-confirm-modal .swal-icon--warning {
        width: 62px;
        height: 62px;
        border: 0;
        background: #FEF3C7;
        margin: 2px auto 16px;
        animation: none;
    }

    .swal-modal.finance-confirm-modal .swal-icon--warning__body {
        width: 5px;
        height: 20px;
        top: 16px;
        background-color: #F59E0B;
        animation: none;
    }

    .swal-modal.finance-confirm-modal .swal-icon--warning__dot {
        width: 6px;
        height: 6px;
        bottom: -10px;
        background-color: #F59E0B;
        animation: none;
    }

    /* deposit.blade.php and transfer.blade.php both carry a legacy
       `.swal-title { color: red }` rule; this outranks it. */
    .swal-modal.finance-confirm-modal .swal-title {
        color: #111827 !important;
        font-size: 25px;
        font-weight: 800;
        margin: 0;
        padding: 0 0 16px;
    }

    .swal-modal.finance-confirm-modal .swal-content {
        margin: 0;
        padding: 0;
    }

    .finance-confirm-rows {
        text-align: left;
    }

    .finance-confirm-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 16px;
        padding: 11px 0;
        border-bottom: 1px solid #E5E7EB;
        font-size: 14px;
    }

    .finance-confirm-row:first-child {
        border-top: 1px solid #E5E7EB;
    }

    .finance-confirm-label {
        color: #4B5563;
        font-weight: 600;
        white-space: nowrap;
    }

    .finance-confirm-value {
        color: #111827;
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    .finance-confirm-value.is-amount {
        color: #F59E0B;
        font-family: Menlo, Consolas, "Courier New", monospace;
        white-space: nowrap;
    }

    .swal-modal.finance-confirm-modal .swal-footer {
        display: flex;
        gap: 12px;
        margin: 0;
        padding: 22px 0 0;
        text-align: center;
    }

    .swal-modal.finance-confirm-modal .swal-button-container {
        flex: 1 1 0;
        margin: 0;
        width: auto;
    }

    .swal-modal.finance-confirm-modal .swal-button {
        width: 100%;
        padding: 12px 10px;
        border-radius: 9px;
        font-size: 15px;
        font-weight: 700;
        box-shadow: none;
    }

    .swal-modal.finance-confirm-modal .swal-button:focus {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .25);
    }

    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-yes,
    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-yes:not([disabled]):hover {
        background-color: #16A34A;
        color: #FFFFFF;
    }

    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-yes:not([disabled]):hover {
        background-color: #15803D;
    }

    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-no,
    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-no:not([disabled]):hover {
        background-color: #FEE2E2;
        color: #DC2626;
    }

    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-no:not([disabled]):hover {
        background-color: #FECACA;
    }

    /* The tick and cross from the screenshot. SweetAlert button labels are
       plain text, so the glyphs come from FontAwesome 4 via ::before. */
    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-yes::before,
    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-no::before {
        font-family: FontAwesome;
        font-weight: normal;
        margin-right: 8px;
    }

    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-yes::before {
        content: "\f00c";
    }

    .swal-modal.finance-confirm-modal .swal-button.finance-confirm-no::before {
        content: "\f00d";
    }

    @media (max-width: 480px) {
        .swal-modal.finance-confirm-modal {
            padding: 20px 18px 16px;
        }

        .swal-modal.finance-confirm-modal .swal-title {
            font-size: 21px;
        }
    }
</style>
<script type="text/javascript">
    (function ($) {
        'use strict';

        /**
         * Format a number the way the rest of the application does, so the
         * confirmation shows the same currency symbol, separators and
         * precision the user sees everywhere else (e.g. "Rs 888.00").
         */
        window.financeConfirmAmount = function (value) {
            var amount = parseFloat(value) || 0;

            if (typeof __currency_trans_from_en === 'function') {
                try {
                    return __currency_trans_from_en(amount, true);
                } catch (ignore) {
                    // Fall through to the plain format below.
                }
            }

            return amount.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        /**
         * Show the confirmation and resolve with true only if Yes was clicked.
         *
         * param: {Object} options
         * param: {Array}  options.rows  [{label, value, highlight}] - rows with an
         *                               empty value are dropped, so a form can
         *                               offer optional fields without producing a
         *                               blank line.
         */
        window.financeConfirmTransaction = function (options) {
            options = options || {};

            var $rows = $('<div class="finance-confirm-rows"/>');

            $.each(options.rows || [], function (index, row) {
                if (!row) {
                    return;
                }

                var value = row.value;
                if (value === null || typeof value === 'undefined' || $.trim(String(value)) === '') {
                    return;
                }

                $('<div class="finance-confirm-row"/>')
                    .append($('<span class="finance-confirm-label"/>').text(row.label))
                    .append(
                        $('<span class="finance-confirm-value"/>')
                            .addClass(row.highlight ? 'is-amount' : '')
                            .text(String(value))
                    )
                    .appendTo($rows);
            });

            var title = options.title
                || ((typeof LANG !== 'undefined' && LANG.sure) ? LANG.sure : 'Are you sure?');

            return swal({
                title: title,
                content: $rows[0],
                icon: 'warning',
                className: 'finance-confirm-modal',
                closeOnClickOutside: false,
                closeOnEsc: true,
                buttons: {
                    yes: {
                        text: options.yesText || '@lang('messages.yes')',
                        value: true,
                        visible: true,
                        closeModal: true,
                        className: 'finance-confirm-yes'
                    },
                    no: {
                        text: options.noText || '@lang('messages.no')',
                        value: false,
                        visible: true,
                        closeModal: true,
                        className: 'finance-confirm-no'
                    }
                }
            });
        };
    })(jQuery);
</script>
