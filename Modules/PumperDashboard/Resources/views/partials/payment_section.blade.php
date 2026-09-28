<style>
    .pumper-payment-screen {
        padding: 12px 8px 24px;
    }

    .pumper-payment-screen .payment-reference-row {
        margin-bottom: 18px;
    }

    .pumper-payment-screen .payment-reference {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin: 0;
        padding: 10px 16px;
        color: #b91c1c;
        font-size: 28px;
        line-height: 1.25;
        font-weight: 800;
        text-align: center;
    }

    .pumper-payment-screen .payment-reference-separator {
        color: #64748b;
    }

    .pumper-payment-screen .payment-workspace {
        display: flex;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .pumper-payment-screen .payment-column-title {
        margin: 0 0 12px;
        font-size: 28px;
        font-weight: 800;
        color: #1f2937;
        text-align: center;
    }

    .pumper-payment-screen .payment-type-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .pumper-payment-screen .payment-type-list > .payment_type_btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 58px;
        margin: 0 !important;
        padding: 14px 20px;
        border: 1px solid transparent !important;
        border-radius: 10px !important;
        box-sizing: border-box;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .13);
        font-size: 22px;
        font-weight: 800;
        line-height: 1.15;
        touch-action: manipulation;
        transform: none !important;
        transition: background-color .12s ease, box-shadow .12s ease, opacity .12s ease;
        user-select: none;
    }

    .pumper-payment-screen .payment-type-list > .payment_type_btn:hover,
    .pumper-payment-screen .payment-type-list > .payment_type_btn:focus,
    .pumper-payment-screen .payment-type-list > .payment_type_btn:active {
        transform: none !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .18);
    }

    .pumper-payment-screen .payment-type-list > .payment_type_btn.active {
        background: #5f6670 !important;
        border-color: transparent !important;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .12) !important;
        opacity: .82;
    }

    .pumper-payment-screen .payment_type_checkbox {
        display: none;
    }

    .pumper-payment-screen .payment-entry-column {
        padding-left: 18px;
        padding-right: 18px;
    }

    .pumper-payment-screen #amount.payment-amount-input {
        width: 100%;
        height: 51px !important;
        min-height: 51px !important;
        margin: 0 0 14px !important;
        padding: 10px 14px;
        background: #fff;
        border: 2px solid #334155 !important;
        border-radius: 9px;
        box-shadow: 0 5px 12px rgba(15, 23, 42, .10);
        font-size: 22px;
        font-weight: 700;
        text-align: right;
    }

    .pumper-payment-screen #key_pad {
        margin: 0;
    }

    .pumper-payment-screen #key_pad .row {
        margin: 0;
        white-space: nowrap;
    }

    .pumper-payment-screen #key_pad button {
        width: 30%;
        height: 88px;
        margin: 3px 1px;
        border: 1px solid transparent !important;
        border-radius: 8px;
        box-sizing: border-box;
        box-shadow: 0 5px 12px rgba(15, 23, 42, .12);
        font-size: 25px;
        font-weight: 800;
        touch-action: manipulation;
        transform: none !important;
        transition: box-shadow .12s ease, filter .12s ease;
    }

    .pumper-payment-screen #key_pad button:hover,
    .pumper-payment-screen #key_pad button:focus,
    .pumper-payment-screen #key_pad button:active {
        transform: none !important;
        box-shadow: 0 7px 15px rgba(15, 23, 42, .18);
        filter: brightness(1.04);
    }

    .pumper-payment-screen .payment-action-panel {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 64px;
        padding-left: 10px;
        padding-right: 10px;
    }

    .pumper-payment-screen .payment-action-panel .pumper-payment-action,
    .pumper-payment-screen .payment-action-panel a,
    .pumper-payment-screen .payment-action-panel a > input {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100% !important;
        min-height: 51px !important;
        margin: 0 !important;
        border-radius: 9px !important;
        box-sizing: border-box;
        font-size: 22px !important;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
        touch-action: manipulation;
        transform: none !important;
    }

    .pumper-payment-screen .payment-action-panel .pumper-payment-action,
    .pumper-payment-screen .payment-action-panel a > input {
        box-shadow: 0 5px 12px rgba(15, 23, 42, .12);
        transition: box-shadow .12s ease, filter .12s ease;
    }

    .pumper-payment-screen .payment-action-panel .pumper-payment-action:hover,
    .pumper-payment-screen .payment-action-panel .pumper-payment-action:focus,
    .pumper-payment-screen .payment-action-panel .pumper-payment-action:active,
    .pumper-payment-screen .payment-action-panel a > input:hover,
    .pumper-payment-screen .payment-action-panel a > input:focus,
    .pumper-payment-screen .payment-action-panel a > input:active {
        transform: none !important;
        box-shadow: 0 7px 16px rgba(15, 23, 42, .18);
        filter: brightness(1.04);
    }

    .pumper-payment-screen .btn-disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    #reloadConfirmationModal .pumper-another-payment-message {
        padding: 24px 20px;
        color: #1f2937;
        font-size: 24px;
        font-weight: 600;
        line-height: 1.4;
    }

    @media (max-width: 991px) {
        .pumper-payment-screen .payment-entry-column {
            padding-left: 15px;
            padding-right: 15px;
        }

        .pumper-payment-screen .payment-action-panel {
            margin-top: 18px;
        }
    }

    @media (max-width: 767px) {
        #reloadConfirmationModal .pumper-another-payment-message {
            padding: 20px 16px;
            font-size: 21px;
        }

        .pumper-payment-screen .payment-reference {
            font-size: 23px;
        }

        .pumper-payment-screen .payment-entry-column {
            margin-top: 18px;
        }

        .pumper-payment-screen .payment-action-panel .pumper-payment-action,
        .pumper-payment-screen .payment-action-panel a,
        .pumper-payment-screen .payment-action-panel a > input {
            font-size: 20px !important;
        }
    }
</style>
<form name="calculator" class="pumper-payment-screen" autocomplete="off">
    <div class="row payment-reference-row">
        <div class="col-md-12">
            @if (session('status'))
                @php
                    $output = session('status');
                    if ($output['success'] && empty($output['meter_sale_saved']) && isset($output['collection_form_no'])) {
                        $collection_form_no = $output['collection_form_no'] ?? $collection_form_no;
                    }
                @endphp
            @endif
            <h2 class="payment-reference">
                <span>Shift NO: {{ $shift_number }}</span>
                <span class="payment-reference-separator" aria-hidden="true">|</span>
                <span>Form No.: {{ $collection_form_no }}</span>
            </h2>
        </div>
    </div>

    <div class="row payment-workspace">
        <div class="@if ($pop_up) col-md-12 @else col-md-8 @endif">
            <div class="row">
                <div class="col-md-5 col-lg-5">
                    <h2 class="payment-column-title">@lang('pumperdashboard::lang.payments')</h2>
                    <div id="pumper-payment-type-list" class="payment-type-list" role="group" aria-label="Payment type">
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-primary" aria-pressed="false">
                            <input
                                {{--
                                    MA-002: cash_denoms_enter is now ALWAYS
                                    applied.

                                    It used to depend on Enter Cash
                                    Denominations being 'yes' - and that class
                                    is what makes po_payment.js open the cash
                                    popup at all:

                                        if ($cashDenomCb.length && $("#cash_payments").length) {
                                            $("#cash_payments").modal(...)
                                        }

                                    So with the setting off, the popup never
                                    opened. You want it to open either way -
                                    only the Enter Bulk Amount box inside it is
                                    governed by the setting, and that is gated
                                    separately in the popup itself.
                                --}}
                                class="payment_type_checkbox cash_denoms_enter"
                                type="checkbox" name="payment_type" value="cash" autocomplete="off" />
                            @lang('pumperdashboard::lang.cash')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat card_payment_btn btn-block btn-info" aria-pressed="false">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="card"
                                autocomplete="off" />
                            @lang('pumperdashboard::lang.card')
                            <input type="hidden" id="sub_card_type">
                            <input type="hidden" id="sub_slip_no">
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-danger add_cheque_payment" aria-pressed="false">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="cheque"
                                autocomplete="off" /> @lang('pumperdashboard::lang.cheque')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-warning" aria-pressed="false">
                            <input class="payment_type_checkbox po_credit_payment" type="checkbox" name="payment_type"
                                value="credit" autocomplete="off" /> @lang('pumperdashboard::lang.credit')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-success" aria-pressed="false">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type"
                                value="multiple_credit" autocomplete="off" /> @lang('pumperdashboard::lang.multiple_credit')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-danger" aria-pressed="false">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="other"
                                autocomplete="off" /> @lang('pumperdashboard::lang.other')
                        </label>
                    </div>
                </div>

                <div class="col-md-6 payment-entry-column">
                    <input name="display" class="form-control input-lg amount input_number payment-amount-input"
                        id="amount" value="" inputmode="decimal" aria-label="Payment amount" />
                    <input type="hidden" name="payment_type" id="payment_type" value="" />

                    <div id="key_pad" class="text-center">
                        <div class="row">
                            <button id="7" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">7</button>
                            <button id="8" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">8</button>
                            <button id="9" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">9</button>
                        </div>
                        <div class="row">
                            <button id="4" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">4</button>
                            <button id="5" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">5</button>
                            <button id="6" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">6</button>
                        </div>
                        <div class="row">
                            <button id="1" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">1</button>
                            <button id="2" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">2</button>
                            <button id="3" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">3</button>
                        </div>
                        <div class="row">
                            <button id="backspace" type="button" class="btn btn-danger" onclick="enterVal(this.id)">⌫</button>
                            <button id="0" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">0</button>
                            <button id="precision" type="button" class="btn btn-success" onclick="enterVal(this.id)">.</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!$pop_up)
            <div class="col-md-3 payment-action-panel">
                <button class="btn btn-flat btn-lg btn-block add_other_sales pumper-payment-action" type="button"
                    style="background: #8F3A84; color: #ffffff;">@lang('pumperdashboard::lang.enter_meters')</button>

                <button id="amount_correct_btn" class="btn btn-success btn-flat btn-lg btn-block amount-correct pumper-payment-action"
                    {{--
                        MA-002: the wording on two lines.

                            Amount Correct?
                            Click Here

                        <br> rather than a fixed height, so the button grows to
                        fit if the text is ever translated into something
                        longer.
                    --}}
                    type="button">@lang('pumperdashboard::lang.amount_correct_line1')<br>@lang('pumperdashboard::lang.amount_correct_line2')</button>

                <a href="{{ action('\\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorController@dashboard') }}">
                    <input value="Dashboard" class="btn btn-flat btn-lg btn-block"
                        style="color: #fff; background-color: #810040;" type="button" />
                </a>

                <button disabled value="save" id="payment_submit" name="submit"
                    class="btn btn-flat btn-lg btn-block pumper-payment-action" style="color: #fff; background-color: #2874a6;"
                    type="button">@lang('lang_v1.save')</button>

                <span onclick="reset()" id="pumper_payment_cancel_wrap">
                    <button type="button" class="btn btn-flat btn-lg btn-block pumper-payment-action"
                        style="color: #fff; background-color: #cc0000;"><i class="fa fa-refresh"
                            aria-hidden="true"></i> @lang('pumperdashboard::lang.cancel')</button>
                </span>

<script>
/*
 |-----------------------------------------------------------------------------
 | One payment method at a time + keep already-used methods inactive.
 |-----------------------------------------------------------------------------
 |
 | IS2301: only one method is selectable while the current payment is being
 | entered. After a successful save the confirmation popup asks whether another
 | payment is required. Choosing "Yes" starts a fresh payment-entry state, so
 | Cash, Card and Credit Sale must become selectable immediately without a page
 | refresh. The intentionally unavailable Cheque / Multiple Credit / Other tabs
 | remain controlled by their separate MA-002 rule below.
 |
 | This is deliberately front-end state only: no settlement/payment posting
 | code is changed.
 */
(function () {
    var LOCK_CLASS = 'pd-method-locked';
    var USED_CLASS = 'pd-method-used';

    function methods() {
        return $('.payment_type_btn');
    }

    function setLocked($button, locked) {
        if (locked) {
            $button.addClass(LOCK_CLASS)
                .css({ opacity: 0.45, 'pointer-events': 'none' })
                .attr('aria-pressed', 'false');
        } else {
            $button.removeClass(LOCK_CLASS)
                .css({ opacity: '', 'pointer-events': '' })
                .attr('aria-pressed', 'false');
        }
    }

    function lockTo($chosen) {
        methods().each(function () {
            var $button = $(this);

            // A method already saved for this Form No. always stays inactive.
            if ($button.hasClass(USED_CLASS)) {
                setLocked($button, true);
                return;
            }

            if ($button.is($chosen)) {
                setLocked($button, false);
                $button.attr('aria-pressed', 'true');
                return;
            }

            setLocked($button, true);
        });
    }

    function unlockUnused() {
        methods().each(function () {
            var $button = $(this);
            setLocked($button, $button.hasClass(USED_CLASS));
        });
    }

    function resetForAnotherPayment() {
        methods().each(function () {
            var $button = $(this);

            $button.removeClass(USED_CLASS + ' ' + LOCK_CLASS + ' active');
            setLocked($button, false);
            $button.find('.payment_type_checkbox').prop('checked', false);
        });
    }

    function markUsed(paymentType) {
        paymentType = String(paymentType || '').toLowerCase().trim();
        if (!paymentType) {
            return;
        }

        methods().each(function () {
            var $button = $(this);
            var value = String($button.find('input[name="payment_type"]').val() || '').toLowerCase();
            if (value === paymentType) {
                $button.addClass(USED_CLASS);
                setLocked($button, true);
            }
        });
    }

    // Save handlers call this only after the backend confirms success.
    window.pdMarkPaymentMethodUsed = markUsed;
    window.pdUnlockUnusedPaymentMethods = unlockUnused;
    window.pdResetPaymentMethodsForAnotherPayment = resetForAnotherPayment;

    $(document).off('click.pdMethodLock').on('click.pdMethodLock', '.payment_type_btn', function () {
        var $button = $(this);

        if ($button.hasClass(LOCK_CLASS) || $button.hasClass(USED_CLASS)) {
            return false;
        }

        lockTo($button);
    });

    // Cancel clears the current unsaved entry but must not re-enable a method
    // already saved against the same collection Form No.
    $(document).off('click.pdMethodUnlock')
        .on('click.pdMethodUnlock', '#pumper_payment_cancel_wrap', function () {
            window.setTimeout(unlockUnused, 0);
        });
})();
</script>

                <a href="{{ action('Auth\\PumpOperatorLoginController@logout') }}"
                    class="btn btn-flat btn-block btn-lg pumper-payment-action"
                    style="background-color: orange; color: #fff;">@lang('pumperdashboard::lang.logout')</a>
            </div>
        @endif
    </div>
</form>
@php
    $collection_form_no = '';
@endphp
@if (session('status'))
    @php
        $output = session('status');
        if ($output['success'] && empty($output['meter_sale_saved']) && isset($output['collection_form_no'])) {
            $collection_form_no = $output['collection_form_no'] ?? '';
        }
    @endphp
@endif
<input type="hidden" class="collection_form_no" id="collection_form_no" value="{{ $collection_form_no }}">
<input type="hidden" id="pump_operator" value="{{ Auth::user()->pump_operator_id ?? '' }}">
<input type="hidden" id="active_shift_id" value="{{ $shift_id ?? '' }}">
<div id="reloadConfirmationModal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="reloadConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reloadConfirmationModalLabel">Confirm Another Payment for Form No.</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pumper-another-payment-message">
                Need to Enter Another Payment?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-right" id="cancelReload">Yes</button>
                {{-- <button type="button" class="btn btn-secondary" id="confirmReload">No</button> --}}
                {{-- <a href="/pumper-dashboard/pump-operators/dashboard" class="btn btn-secondary pull-left" style="background-color: #810040; color: white;">No</a> --}}
                <button type="button" class="btn btn-secondary pull-left go-dashboard"
                    style="background-color: #810040; color: white;">No</button>
            </div>
        </div>
    </div>
</div>
@if (!empty($collection_form_no))
    <script>
        $(document).ready(function() {
            var formNo = "{{ $collection_form_no }}";
            console.log("Form number from session:", formNo);
            if (formNo && formNo !== "" && formNo !== "0") {
                $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + formNo);
                $("#reloadConfirmationModal").modal("show");
            } else {
                console.error("Form number is empty or invalid:", formNo);
            }
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        $("#confirmReload").on("click", function() {
            location.reload();
        });

        /*
         * S692: returning the page to a usable state after a payment is saved.
         *
         * What this does - clearing the compulsory-meters flag, re-enabling the
         * payment buttons and resetting the form - is exactly what the page needs
         * after every save. But it only ran when the user pressed "Yes" on the
         * confirmation dialog, so the Cash / Card / Credit Sale buttons stayed
         * disabled until they did. That is the "have to click Cancel" step in the
         * report.
         *
         * The work is now a named function, so it can be called both from the
         * button and automatically once a payment saves.
         */
        var pdRestoringPaymentButtons = false;
        function pdRestorePaymentButtons() {
            if (pdRestoringPaymentButtons) {
                return;
            }
            pdRestoringPaymentButtons = true;

            $("#meter_sales_compulsory").val("no");
            if ($("#reloadConfirmationModal").hasClass("in")) {
                $("#reloadConfirmationModal").modal("hide");
            }
            $("#reloadConfirmationModal").css("display", "none");

            $(".payment_type_btn").removeClass("active");
            if (typeof reset === 'function') {
                reset();
            }

            // IS2301: "Yes" starts another payment immediately. Clear both the
            // temporary one-at-a-time lock and the just-saved marker so Cash,
            // Card and Credit Sale are active without refreshing the page.
            if (typeof window.pdResetPaymentMethodsForAnotherPayment === 'function') {
                window.pdResetPaymentMethodsForAnotherPayment();
            } else if (typeof window.pdUnlockUnusedPaymentMethods === 'function') {
                window.pdUnlockUnusedPaymentMethods();
            }

            window.setTimeout(function () {
                pdRestoringPaymentButtons = false;
            }, 0);
        }

        // Kept so "Yes" behaves exactly as before.
        $("#cancelReload").off().on("click", function() {
            pdRestorePaymentButtons();

            // Some Bootstrap builds complete modal teardown on the next event
            // turn. Re-apply only the UI reset after teardown so no stale label
            // lock can survive until a manual browser refresh.
            window.setTimeout(function () {
                if (typeof window.pdResetPaymentMethodsForAnotherPayment === 'function') {
                    window.pdResetPaymentMethodsForAnotherPayment();
                }
            }, 50);
        });

        /*
         * Restore automatically when the confirmation dialog is dismissed by ANY
         * means - the Yes button, the close cross, the Escape key or a click
         * outside it. Previously only one of those four left the page usable.
         *
         * "No" is unaffected: it navigates away to the dashboard, and its own
         * handler runs first.
         */
        $(document).off('hidden.bs.modal.pdRestore')
            .on('hidden.bs.modal.pdRestore', '#reloadConfirmationModal', function () {
                pdRestorePaymentButtons();
            });

        /*
         * Expose it so the save handlers in actions/payments.blade.php can put
         * the page back immediately, without waiting for the dialog to close.
         */
        window.pdRestorePaymentButtons = pdRestorePaymentButtons;

        $(document).on('click', '.go-dashboard', function(e) {
            e.preventDefault();
            $("#meter_sales_compulsory").val("yes");
            $("#reloadConfirmationModal").modal("hide");
            $("#reloadConfirmationModal").css("display", "none");
            window.location.href = "{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard') }}";
        });

    });
</script>
<input type="hidden" id="meter_sales_compulsory" value="{{ $meter_sales_compulsory ? 'yes' : 'no' }}">
<script>
    $(document).ready(function() {
        $(".payment_type_btn").each(function(i, ele) {
            var meter_sales_compulsory = $("#meter_sales_compulsory").val();
            if (meter_sales_compulsory == "yes") {
                $(ele).addClass("active");
                $(this).find(".payment_type_checkbox").prop("checked", false);
            }
        });

        $(document).on("click", ".payment_type_btn", function(e) {
            var meter_sales_compulsory = $("#meter_sales_compulsory").val();
            if (meter_sales_compulsory == "yes") {
                e.preventDefault();
                toastr.error("First Enter Meters");
                return false;
            }
        });
    });
</script>

<style>
/*
 * MA-002 (S-609 #6): payment screen sizing and disabled tabs.
 *
 * Appended rather than editing the existing rules above, so the original
 * values stay visible and this block can be removed in one piece if the
 * sizes are not right. Declared later, so it wins on equal specificity.
 *
 *   a. Payment Method font +50%   22px -> 33px
 *   c. Number Pad font     +50%   25px -> 38px  (25 x 1.5 = 37.5)
 *   b. Cheque / Multiple Credit / Other must not open when clicked.
 *      Handled in the script below - pointer-events alone would still
 *      leave them keyboard-reachable, and the label would still toggle
 *      its hidden checkbox.
 */
.pumper-payment-screen .payment-type-list > .payment_type_btn {
    font-size: 33px;
}

.pumper-payment-screen #key_pad button {
    font-size: 38px;
}

/* #6b - visibly inert, so nobody wonders why the tap did nothing. */
.pumper-payment-screen .payment-type-list > .payment_type_btn.ma002-disabled-tab {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<script>
(function () {
    /*
     * MA-002 (S-609 #6b): Cheque, Multiple Credit and Other must not open.
     *
     * Bound in the capture phase on the container so it runs BEFORE the
     * label's own default behaviour and before any handler already attached
     * to these buttons. Returning early here stops both the checkbox toggling
     * and whatever panel the click would have opened.
     *
     * The three values come from the markup above - value="cheque",
     * "multiple_credit" and "other" - so this stays correct if the labels are
     * ever re-worded.
     */
    var BLOCKED = ['cheque', 'multiple_credit', 'other'];

    function markDisabled() {
        document.querySelectorAll('.pumper-payment-screen .payment_type_btn').forEach(function (label) {
            var input = label.querySelector('input[name="payment_type"]');
            if (input && BLOCKED.indexOf(input.value) !== -1) {
                label.classList.add('ma002-disabled-tab');
                input.disabled = true;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', markDisabled);
    markDisabled();

    document.addEventListener('click', function (e) {
        var label = e.target.closest ? e.target.closest('.payment_type_btn') : null;
        if (!label) { return; }
        var input = label.querySelector('input[name="payment_type"]');
        if (input && BLOCKED.indexOf(input.value) !== -1) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
}());
</script>
