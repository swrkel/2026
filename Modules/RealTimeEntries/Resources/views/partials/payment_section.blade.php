<style>
    .btn-large {
        padding: 18px 28px;
        font-size: 22px; //change this to your desired size
        line-height: normal;
    }

    /* when a payment button is selected we no longer override its
       background colour.  the original bootstrap button colour should
       remain so that the tab appears the same colour as the rest of the
       interface.  previously we forced a gray background which caused the
       “multiple colours for Open” issue described in the ticket. */
    .rtp_payment_type_btn.active {
        /* keep the original colour, but add a subtle outline so the active
           button is still visibly different when all others are locked */
        outline: 2px solid #333;
    }

    #key_pad input {
        border: none;
    }

    #key_pad button {
        height: 80px;
        width: 30%;
        font-size: 25px;
        margin: 2px 1px;
        border: none !important;
    }

    .payment_type_checkbox {
        display: none;
    }

    .rtp_payment_type_btn.disabled {
        background-color: #808080 !important;
        /* Gray */
        border-color: #808080 !important;
        color: #fff !important;
        opacity: 1 !important;
    }
    .rtp_payment_type_btn.locked {
        background-color: #808080 !important;
        border-color: #808080 !important;
        color: #fff !important;
        opacity: 1 !important;
        pointer-events: none !important;
    }
</style>
<form name="calculator">
    <div class="clearfix"></div>
    <br />
    <div class="">
        <div class="col-md-8 col-lg-8">
            <div class="row">
                <h2 style="color: red; text-align: center;">
                    @if (session('status') && is_array(session('status')))
                        @php
                            $output = session('status');
                            if (! empty($output['success']) && isset($output['collection_form_no'])) {
                                $collection_form_no = $output['collection_form_no'];
                            }
                        @endphp
                    @endif
                </h2>
            </div>
            <div class="row">
                <div class="col-md-5">
                    <h2>@lang('petro::lang.payments')</h2>
                </div>
                <div class="col-md-6">
                    <input name="display" class="form-control input-lg amount input_number"
                        style="margin-top: 10px; background: #fff; border: 2px solid #333;" id="amount"
                        value="" disabled />
                    <input type="hidden" name="payment_type" id="payment_type" value="" />
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="@if ($pop_up) col-md-12 @else col-md-8 @endif">
            <div class="col-md-5 col-lg-5">
                <div class="row">
                    <div class="btn-group-vertical">
                        <label class="rtp_payment_type_btn btn btn-large btn-flat btn-block btn-primary cash_payment_btn">
                            <input
                                class="payment_type_checkbox @if (!empty($enter_cash_denoms) && $enter_cash_denoms == 'yes') cash_denoms_enter @endif"
                                type="checkbox" name="payment_type" value="cash" autocomplete="off" />
                            @lang('petro::lang.cash')
                        </label>
                        <label class="rtp_payment_type_btn btn btn-large btn-flat card_payment_btn btn-block btn-info">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="card"
                                autocomplete="off" />
                            @lang('petro::lang.card')
                            <input type="hidden" id="sub_card_type">
                            <input type="hidden" id="sub_slip_no">
                        </label>
                        <label class="rtp_payment_type_btn btn btn-large btn-flat btn-block btn-danger add_cheque_payment">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="cheque"
                                autocomplete="off" /> @lang('petro::lang.cheque')
                        </label>
                        <label class="rtp_payment_type_btn btn btn-large btn-flat btn-block btn-warning po_credit_payment">
                            <input
                                class="payment_type_checkbox @if (!empty($direct_cr) && $direct_cr == 'yes') po_credit_payment @endif"
                                type="checkbox" name="payment_type" value="credit" autocomplete="off" />
                            @lang('petro::lang.credit')
                        </label>




                    </div>
                </div>
            </div>
            <div id="key_pad" class="row col-md-6 text-center" style="margin-left: 7px;">
                <div class="row">
                    <button id="7" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">7</button>
                    <button id="8" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">8</button>
                    <button id="9" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">9</button>
                </div>
                <div class="row">
                    <button id="4" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">4</button>
                    <button id="5" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">5</button>
                    <button id="6" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">6</button>
                </div>
                <div class="row">
                    <button id="1" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">1</button>
                    <button id="2" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">2</button>
                    <button id="3" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">3</button>
                </div>
                <div class="row">
                    <button id="backspace" type="button" class="btn btn-danger" disabled
                        onclick="enterVal(this.id)">⌫</button>
                    <button id="0" type="button" class="btn btn-primary btn-sm" disabled
                        onclick="enterVal(this.id)">0</button>
                    <button id="precision" type="button" class="btn btn-success" disabled
                        onclick="enterVal(this.id)">.</button>
                </div>
            </div>
        </div>
        @if (!$pop_up)
            <div class="col-md-3">
                <div class="row">

                    <button class="btn btn-flat btn-lg btn-block add_other_sales" type="button" disabled
                        style="background: #8F3A84; color: #ffffff;">@lang('petro::lang.enter_meters')</button>
                    <br />

                    <button class="btn btn-success btn-flat btn-lg btn-block real-time-amount-correct" disabled
                        type="button">@lang('petro::lang.amount_correct_click_here')</button>
                    <br />

                    <button disabled value="save" id="real_time_payment_submit" name="submit"
                        class="btn btn-flat btn-lg btn-block" style="color: #fff; background-color: #2874a6;"
                        type="button">@lang('lang_v1.save')</button>
                    <br />
                    <span onclick="reset()">
                        <button type="button" disabled
                            class="btn btn-flat btn-lg btn-block rtp-payment-cancel-btn"
                            style="color: #fff; background-color: #cc0000;" type="button"><i class="fa fa-refresh"
                                aria-hidden="true"></i> @lang('petro::lang.cancel')</button>
                    </span>
                </div>
            </div>
        @endif
    </div>
    <input type="hidden" name="pump_operator_id" id="pump_operator_id" value="">
</form>
@php
    $collection_form_no = '';
    $show_payment_followup_modal = false;
@endphp
@if (session('status') && is_array(session('status')))
    @php
        $output = session('status');
        if (! empty($output['success']) && isset($output['collection_form_no'])) {
            $collection_form_no = $output['collection_form_no'] ?? '';
        }
        if (! empty($output['success']) && ! empty($collection_form_no) && empty($output['meter_sale_saved'])) {
            $show_payment_followup_modal = true;
        }
    @endphp
@endif
<input type="hidden" class="collection_form_no" id="collection_form_no" value="{{ $collection_form_no }}">
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
            <div class="modal-body">
                Need to Enter Another Payment?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-right" id="confirmReload">Yes</button>
                <button type="button" class="btn btn-secondary" id="cancelReload" data-dismiss="modal">No</button>
                {{-- <a href="/petro/pump-operators/dashboard" class="btn btn-secondary pull-left"
                    style="background-color: #810040; color: white;">No</a> --}}
            </div>
        </div>
    </div>
</div>
@if ($show_payment_followup_modal)
    <script>
        $(document).ready(function() {
            $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " +
                {{ $collection_form_no }});
            $("#reloadConfirmationModal").modal("show");
        });
    </script>
@endif
<script>
    $(document).ready(function() {
        $("#confirmReload").on("click", function() {
            var pendingAction = $("#reloadConfirmationModal").data('pending-action');

            // Duplicate-save flows are handled by their own namespaced handlers
            // Credit flows use stopImmediatePropagation so this handler won't run for credit
            if (pendingAction) {
                return;
            }

            // Clean up stale namespaced handlers to prevent double-firing
            $("#confirmReload").off('click.duplicate click.duplicateCard click.creditPayment');

            // Regular cash / card / cheque: reset form and close modal
            reset();
            $("#reloadConfirmationModal").removeData('payment-type');
            $("#reloadConfirmationModal").modal('hide');
        });

        $("#cancelReload").off().on("click", function() {
            // Clean up stale namespaced handlers on Yes button
            $("#confirmReload").off('click.duplicate click.duplicateCard click.creditPayment');

            // Hide the modal and clean stale data
            $("#reloadConfirmationModal").modal("hide");
            $("#reloadConfirmationModal").removeData('pending-action').removeData('payment-type');

            // Reset meter_sales_compulsory so payment buttons are not permanently blocked
            $("#meter_sales_compulsory").val("no");

            // Unlock all payment type buttons
            if (typeof unlockRealTimePayments === 'function') {
                unlockRealTimePayments();
            } else {
                $(".rtp_payment_type_btn").removeClass("active locked disabled")
                    .css("pointer-events", "auto");
            }

            // Restore/keep select2 values to prevent them from resetting to empty
            var currentOperator = $("#realtime_entries_pump_operator").val();
            var currentShift = $("#shift_number").val();
            if (currentOperator) {
                window._restoringOperator = true;
                $('#realtime_entries_pump_operator').val(currentOperator).trigger('change');
                window._restoringOperator = false;
            }
            if (currentShift) {
                // Wait slightly for operator AJAX to finish if it re-populated
                setTimeout(function() {
                    if ($('#shift_number option[value="' + currentShift + '"]').length) {
                         window._restoringOperator = true;
                         $('#shift_number').val(currentShift).trigger('change');
                         window._restoringOperator = false;
                    }
                }, 500);
            }
        });
    });
</script>
<input type="hidden" id="meter_sales_compulsory" value="{{ ($meter_sales_compulsory ?? false) ? 'yes' : 'no' }}">
<script>
    function enterVal(val) {
        const amountInput = document.getElementById('amount');
        if (!amountInput || amountInput.disabled) {
            return;
        }
        let currentValue = amountInput.value || '';

        if (val === 'backspace') {
            amountInput.value = currentValue.slice(0, -1);
        } else if (val === 'precision') {
            if (!currentValue.includes('.')) {
                amountInput.value += '.';
            }
        } else {
            amountInput.value += val;
        }
    }
</script>

<script>
    $(document).on('click', '.real-time-amount-correct', function() {
        console.log("Amount Correct Clicked");

        let rawAmount = $("#amount").val();

        // Remove commas before parsing
        rawAmount = rawAmount.replace(/,/g, '');

        const amount = parseFloat(rawAmount);
        const paymentSubmit = $("#real_time_payment_submit");

        if (!$("#realtime_entries_pump_operator").val() || !$("#shift_number").val()) {
            paymentSubmit.prop('disabled', true);
            return;
        }
        if (!isNaN(amount) && amount > 0) {
            paymentSubmit.prop('disabled', false);
        } else {
            toastr.error("Enter Valid Amount");
            paymentSubmit.prop('disabled', true);
        }
    });
</script>
