@php
$business_id = session()->get('user.business_id');
$business_details = App\Business::find($business_id);
$currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
$quantity_precision = !empty($business_details->quantity_precision) ? $business_details->quantity_precision : 2;
@endphp

{{-- Fix swal z-index so it appears above Bootstrap modals --}}
<style>
    .swal-overlay { z-index: 99999 !important; }
    .swal-modal   { z-index: 100000 !important; }

    /* Keep Select2 dropdowns correctly layered and positioned inside Add Payment modal. */
    .add_payment .select2-container { width: 100% !important; }
    .add_payment .select2-dropdown { z-index: 100000 !important; }
    .add_payment .select2-results__options { max-height: 230px !important; overflow-y: auto !important; }
</style>

<div class="modal-dialog" role="document" style="width: 85%;">

    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\SettlementController@store'), 'method' =>
        'post', 'id' =>'settlement_form' ]) !!}



        <div class="modal-header">

            {{--

            @ModifiedBy Afes oktavianus

            @DateBy 31-05-2021

            @Task 3350

            --}}



            <h4 class="modal-title pull-left" style="padding-right: 25px">@lang( 'petrogeneral::lang.add_payment' )</h4>

            @if (!isset($provider) && $provider != 'SET_SW')

            <h4 class="modal-title pull-left" style="padding-right: 25px">@lang( 'petrogeneral::lang.settlement_no' ):
                {{$settlement->settlement_no}}</h4>

            <h4 class="modal-title pull-left" style="padding-right: 25px">Shift No : {{$show_shift_no}}</h4>



            <h4 class="modal-title">@lang( 'petrogeneral::lang.date' ): {{$settlement->transaction_date}}</h4>

            @endif

            <div class="pull-right">
                <button type="button" class="btn btn-danger" data-dismiss="modal">@lang('petrogeneral::lang.back')</button>
                <button
                    data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\AddPaymentController@preview', [$settlement->id]) }}"
                    class="btn-modal btn btn-success" id="payment_review_btn" data-container=".preview_settlement"
                    style="margin-left: 5px;">
                    @lang("petrogeneral::lang.preview")
                </button>
                <button type="button" id="settlement_save_btn"
                    class="btn btn-primary @if($total_balance != 0) hide @endif" style="margin-left: 5px;">
                    Finalize Settlement
                </button>
            </div>

        </div>



        <div class="modal-body">

            <div class="col-md-12">

                <div class="row">

                    @if (!isset($provider) && $provider != 'SET_SW')

                    <div class="col-md-2 text-center">

                        <b>@lang('petrogeneral::lang.pump_operator')</b> <br>

                        {{isset($pump_operator->name) ? $pump_operator->name : ''}}

                    </div>

                    @endif

                    <div class="col-md-2 text-center">

                        <b>@lang('petrogeneral::lang.current_short')</b> <br>



                        {{@num_format($operator_bal > 0 ? abs($operator_bal) : 0) }}

                    </div>

                    <div class="col-md-2 text-center">

                        <b>@lang('petrogeneral::lang.current_excess')</b> <br>



                        {{@num_format($operator_bal < 0 ? abs($operator_bal) : 0) }} </div>

                            <div class="col-md-2 text-center">

                                <b>@lang('petrogeneral::lang.daily_collections')</b> <br>

                                {{@num_format($total_daily_collection)}}

                            </div>

                            <div class="col-md-2 text-center">

                                <b>@lang('petrogeneral::lang.daily_vouchers')</b> <br>

                                {{@num_format(0)}}

                            </div>

                            <div class="col-md-2 text-center">

                                <b>@lang('petrogeneral::lang.commision_ammount')</b> <br>

                                {{isset($pump_operator->total_commision) ? @num_format($pump_operator->total_commision)
                                : 0 }}

                            </div>

                    </div>

                    @php


                    $total_paid = !empty($total_paid) ? $total_paid : 0;
                    $total_balance = $total_amount - $total_paid;

                    @endphp

                    <br><br>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <!-- Show Balance to Operator button when there is a non-zero balance -->
                            <button type="button" id="balance_to_operator_btn"
                                class="btn @if($total_balance == 0) hide @endif"
                                style="background-color: purple; color: white;">
                                Balance to Operator
                            </button>
                        </div>
                    </div>
                    <br><br>

                    <div class="row">
                        <div class="col-md-3 text-center text-red">
                            <b>@lang('petrogeneral::lang.total_amount'): </b>
                            <span class="total_amount">{{@num_format($total_amount)}}</span>
                        </div>
                        <div class="col-md-3 text-center text-red">
                            <b>@lang('petrogeneral::lang.total_paid'): </b>
                            <span class="total_paid">{{@num_format($total_paid)}}</span>
                        </div>
                        <div class="col-md-3 text-center text-red">
                            <b>@lang('petrogeneral::lang.balance'): </b>
                            <span class="total_balance">{{@num_format($total_balance)}}</span>
                        </div>
                        <div class="col-md-3 text-center text-red">
                        </div>
                        <!-- <div class="col-md-3 text-center text-red"> -->

                        <!--<button type="button" id="settlement_save_btn" style="margin-left: 45px;"-->

                        <!--   class="btn btn-primary pull-left @if(!empty($total_balance) && $total_balance == 0) hide @endif">@lang('messages.save')</button>-->

                        <!-- <button type="button" id="settlement_save_btn" style="margin-left: 45px;"

                     class="btn btn-primary @if( $total_balance != 0 ) {{ 'hide' }} @endif pull-left">@lang('messages.save')</button>

                     <button data-href="{{action('\Modules\PetroGeneral\Http\Controllers\AddPaymentController@preview', [$settlement->id])}}" class="btn-modal btn btn-success pull-right" id="payment_review_btn" data-container=".preview_settlement"> @lang("petrogeneral::lang.preview")</button>

               </div> -->

                        <div class="col-md-3 text-center text-red">
                        </div>


                    </div>

                </div>

                <input type="hidden" name="settlement_id" value="{{$settlement->settlementt_no}}">

                <input type="hidden" name="settlement_no" id="settlement_no" value="{{$settlement->settlement_no}}">

                <input type="hidden" name="total_balance" id="total_balance"
                    value="{{!empty($total_balance)? $total_balance : 0 }}">

                <input type="hidden" name="total_amount" id="total_amount"
                    value="{{!empty($total_amount)? $total_amount : 0 }}">

                <input type="hidden" name="total_paid" id="total_paid"
                    value="{{!empty($total_paid)? $total_paid : 0 }}">

                {{-- Direct Settlement flag (used by shared payment tab JS). --}}
                <input type="hidden" id="is_direct_settlement" value="1">

                <br><br>

                <div class="clearfix"></div>

                <div style="margin-top: 20px;">

                    @include('petrogeneral::settlement.partials.payment_tabs')

                </div>



                <div class="clearfix"></div>

                {!! Form::close() !!}

                <div class="modal fade contact_modal" tabindex="-1" role="dialog"
                    aria-labelledby="gridSystemModalLabel">

                </div>





            </div><!-- /.modal-content -->

        </div><!-- /.modal-dialog -->

        <script>

            // Session storage functions for data persistence
            function saveFormDataToSession() {
                var settlement_no = $("#settlement_no").val();
                if (!settlement_no) return;

                // NOTE: We don't save table data (excess, shortage, cash, credit sales, etc.) to session
                // because these are persisted in the database and reloaded from backend on page refresh.
                // Saving them would cause duplicates when restored.
                // We only save:
                // 1. Total values (for display consistency)
                // 2. Form field values (for user convenience)
                // 3. Auto-balance state (to prevent duplicate auto-balance entries)

                var formData = {
                    settlement_no: settlement_no,
                    total_amount: $("#total_amount").val(),
                    total_paid: $("#total_paid").val(),
                    total_balance: $("#total_balance").val(),
                    last_credit_customer: $("#credit_sale_customer_id").val(),
                    last_credit_customer_name: $("#credit_sale_customer_id :selected").text(),
                    last_order_number: $("#order_number").val(),
                    last_order_date: $("#order_date").val(),
                    auto_balance_state: window.__petro_auto_balance_state || null,
                    balance_locked: window.__petro_balance_locked || false,
                    locked_total_paid: window.__petro_locked_total_paid || null,
                    locked_total_balance: window.__petro_locked_total_balance || null,
                    timestamp: new Date().getTime()
                };

                sessionStorage.setItem('settlement_form_data_' + settlement_no, JSON.stringify(formData));
                console.log('Form data saved to session:', formData);
            }

            function restoreFormDataFromSession() {
                var settlement_no = $("#settlement_no").val();
                if (!settlement_no) return;

                var savedData = sessionStorage.getItem('settlement_form_data_' + settlement_no);
                if (!savedData) return;

                try {
                    var formData = JSON.parse(savedData);

                    // Check if data is not too old (within 24 hours)
                    var now = new Date().getTime();
                    if (formData.timestamp && (now - formData.timestamp) > 24 * 60 * 60 * 1000) {
                        sessionStorage.removeItem('settlement_form_data_' + settlement_no);
                        return;
                    }

                    console.log('Restoring form data from session:', formData);

                    // Restore locked balance state if exists (from Balance to Operator)
                    if (formData.balance_locked && formData.locked_total_paid !== null && formData.locked_total_balance !== null) {
                        window.__petro_balance_locked = formData.balance_locked;
                        window.__petro_locked_total_paid = parseFloat(formData.locked_total_paid);
                        window.__petro_locked_total_balance = parseFloat(formData.locked_total_balance);

                        // Restore locked values
                        $("#total_paid").val(window.__petro_locked_total_paid);
                        $("#total_balance").val(window.__petro_locked_total_balance);
                        $(".total_paid").text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                        $(".total_balance").text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));

                        // Restore button states for locked balance
                        if (window.__petro_locked_total_balance === 0) {
                            $('#balance_to_operator_btn').addClass('hide').hide();
                            $("#settlement_save_btn").removeClass("hide").show();
                        } else {
                            $('#balance_to_operator_btn').removeClass('hide').show();
                            $("#settlement_save_btn").addClass("hide").hide();
                        }

                        // Start continuous enforcement of locked values if balance is locked
                        if (window.__petro_balance_locked && window.__petro_locked_total_balance === 0) {
                            if (window.__petro_balance_lock_interval) {
                                clearInterval(window.__petro_balance_lock_interval);
                            }
                            window.__petro_balance_lock_interval = setInterval(function () {
                                if (window.__petro_balance_locked) {
                                    window.__petro_balance_locked_updating = true;
                                    $('#total_paid').val(window.__petro_locked_total_paid);
                                    $('#total_balance').val(window.__petro_locked_total_balance);
                                    $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                                    $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));

                                    // Ensure buttons stay correct
                                    if (window.__petro_locked_total_balance === 0) {
                                        $('#balance_to_operator_btn').addClass('hide').hide();
                                        $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                                    }
                                    setTimeout(() => { window.__petro_balance_locked_updating = false; }, 50);
                                }
                            }, 100);
                        }
                    } else {
                        // Restore normal totals if no locked state
                        // totals from session are often stale; use backend values instead
                        // if (formData.total_amount) {
                        //     $("#total_amount").val(formData.total_amount);
                        //     $(".total_amount").text(__number_f(parseFloat(formData.total_amount), false, false, __currency_precision));
                        // }
                        // if (formData.total_paid) {
                        //     $("#total_paid").val(formData.total_paid);
                        //     $(".total_paid").text(__number_f(parseFloat(formData.total_paid), false, false, __currency_precision));
                        // }
                        if (formData.total_balance !== undefined && formData.total_balance !== null) {
                            $("#total_balance").val(formData.total_balance);
                            var balance = parseFloat(formData.total_balance);
                            $(".total_balance").text(__number_f(balance, false, false, __currency_precision));

                            // Restore Balance to Operator button and save button based on balance
                            if (balance === 0) {
                                $('#balance_to_operator_btn').addClass('hide');
                                $("#settlement_save_btn").removeClass("hide");
                            } else {
                                $('#balance_to_operator_btn').removeClass('hide');
                                $("#settlement_save_btn").addClass("hide");
                            }
                        }
                    }

                    // Restore auto balance state
                    if (formData.auto_balance_state) {
                        window.__petro_auto_balance_state = formData.auto_balance_state;
                    }

                    // Restore last credit customer and order number for convenience
                    if (formData.last_credit_customer) {
                        $("#credit_sale_customer_id").val(formData.last_credit_customer).trigger('change');
                    }
                    if (formData.last_order_number) {
                        $("#order_number").val(formData.last_order_number);
                    }
                    if (formData.last_order_date) {
                        $("#order_date").val(formData.last_order_date);
                    }

                    // Update tab visibility
                    setTimeout(function () {
                        show_hide_excess_shortage_tab();
                    }, 100);

                } catch (e) {
                    console.error('Error restoring form data:', e);
                }
            }

            function clearFormDataFromSession() {
                var settlement_no = $("#settlement_no").val();
                if (settlement_no) {
                    sessionStorage.removeItem('settlement_form_data_' + settlement_no);
                }
            }

            // Balance to Operator handler — registered at script level (NOT inside document.ready)
            // This partial is loaded via AJAX (.load()), so document.ready won't re-fire.
            // Placing it here ensures the handler is registered every time the modal loads.
            var __currency_precision_bto = {{ $currency_precision ?? 2 }};
            $(document).off('click', '#balance_to_operator_btn').on('click', '#balance_to_operator_btn', function () {
                var __currency_precision = __currency_precision_bto;
                var raw_balance = ($('#total_balance').val() || '0').toString().replace(/,/g, '');
                var balance = parseFloat(raw_balance);

                if (isNaN(balance)) { toastr.error('Balance value is not valid.'); return; }
                if (balance === 0) { toastr.info('No balance to add'); return; }

                var type = balance > 0 ? 'shortage' : 'excess';
                window.__petro_balance_to_operator_type = type;

                var has_auto_row = $('#shortage_table tbody tr[data-auto-balance="1"]').length > 0
                    || $('#excess_table tbody tr[data-auto-balance="1"]').length > 0;
                if (has_auto_row && window.__petro_auto_balance_state
                    && window.__petro_auto_balance_state.type === type
                    && window.__petro_auto_balance_state.balance === balance) {
                    toastr.info('Balance already added'); return;
                }

                window.__petro_balance_to_operator_active = true;
                window.__petro_balance_locked = true;
                window.__petro_auto_balance_pending = { type: type, balance: balance };
                if (typeof window.show_hide_excess_shortage_tab === 'function') window.show_hide_excess_shortage_tab();

                var is_edit = $("#is_edit").val() || 0;

                var remove_existing_auto = function () {
                    var $row = $('#shortage_table tbody tr[data-auto-balance="1"], #excess_table tbody tr[data-auto-balance="1"]').first();
                    if ($row.length === 0) return $.Deferred().resolve(true).promise();
                    var $btn = $row.find('button.delete_shortage_payment, button.delete_excess_payment').first();
                    var url = $btn.data('href');
                    if (!url) { $row.remove(); return $.Deferred().resolve(true).promise(); }
                    return $.ajax({ method: "delete", url: url, data: { is_edit: is_edit } }).then(function (result) {
                        if (!(result && result.success)) {
                            toastr.error((result && result.msg) ? result.msg : 'Unable to remove existing balance row');
                            return $.Deferred().reject().promise();
                        }
                        $row.remove();
                        if (typeof delete_payment === 'function' && result.amount !== undefined) delete_payment(result.amount);
                        if ($btn.hasClass('delete_shortage_payment')) {
                            if (typeof window.calculateTotal === 'function') window.calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");
                        } else {
                            typeof rebuildExcessTable === 'function' ? rebuildExcessTable() : (typeof window.calculateTotal === 'function' && window.calculateTotal("#excess_table", ".excess_amount", ".excess_total"));
                        }
                        return true;
                    }, function () { toastr.error('Unable to remove existing balance row'); return $.Deferred().reject().promise(); });
                };

                remove_existing_auto().done(function () {
                    $('#shortage_amount').val('');
                    $('#excess_amount').val('');

                    var amount = Math.abs(balance);
                    var field = type === 'shortage' ? '#shortage_amount' : '#excess_amount';
                    var form_amount = type === 'shortage' ? amount : -amount;
                    var balance_adjustment = balance;
                    $(field).val(form_amount.toFixed(2));

                    window.__petro_skip_add_payment = true;
                    var current_total_paid = parseFloat(($('#total_paid').val() || '0').toString().replace(/,/g, '')) || 0;
                    var new_total_paid = current_total_paid + balance_adjustment;
                    var current_total_balance = parseFloat(($('#total_balance').val() || '0').toString().replace(/,/g, ''));
                    var new_balance = current_total_balance - balance_adjustment;

                    window.__petro_locked_total_paid = new_total_paid;
                    window.__petro_locked_total_balance = new_balance;
                    window.__petro_balance_locked = true;

                    if (window.__petro_balance_lock_interval) clearInterval(window.__petro_balance_lock_interval);
                    window.__petro_balance_lock_interval = setInterval(function () {
                        if (window.__petro_balance_locked && !window.__petro_balance_locked_updating) {
                            $('#total_paid').val(window.__petro_locked_total_paid);
                            $('#total_balance').val(window.__petro_locked_total_balance);
                            $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                            $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));
                            if (window.__petro_locked_total_balance === 0) {
                                $('#balance_to_operator_btn').addClass('hide').hide();
                                $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                            }
                        }
                    }, 100);

                    $('#total_paid').val(new_total_paid);
                    $('#total_balance').val(new_balance);
                    $('.total_paid').text(__number_f(new_total_paid, false, false, __currency_precision));
                    $('.total_balance').text(__number_f(new_balance, false, false, __currency_precision));
                    $('#balance_to_operator_btn').addClass('hide').hide();
                    $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');

                    var tab_selector = type === 'shortage' ? '.shortage_tab' : '.excess_tab';
                    var other_tab = type === 'shortage' ? '.excess_tab' : '.shortage_tab';
                    var note_field = type === 'shortage' ? '#shortage_note' : '#excess_note';
                    var tab_pane = type === 'shortage' ? '#shortage_tab' : '#excess_tab';
                    var target_active_tab = type === 'shortage' ? '#shortage_tab' : '#excess_tab';
                    var btn_class = type === 'shortage' ? '.shortage_add' : '.excess_add_btn';

                    $('#settlement_form ' + other_tab).parents('li:first').addClass('disabled');
                    $('#settlement_form ' + tab_selector).parents('li:first').removeClass('disabled');
                    $(note_field).val('Balance to Operator');
                    $('#settlement_form ' + tab_selector).tab('show');

                    setTimeout(function () {
                        if (typeof ensureSettlementTabExclusive === 'function') ensureSettlementTabExclusive(tab_pane);
                        $(field).val(form_amount.toFixed(2));
                        window.__petro_balance_to_operator_active = true;
                        $(btn_class).trigger('click');

                        setTimeout(function () {
                            if (window.__petro_balance_locked) {
                                $('#total_paid').val(window.__petro_locked_total_paid);
                                $('#total_balance').val(window.__petro_locked_total_balance);
                                $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                                $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));
                            }
                            window.__petro_skip_add_payment = false;
                            var final_balance = parseFloat(($('#total_balance').val() || '0').toString().replace(/,/g, ''));
                            if (!isNaN(final_balance) && final_balance === 0) {
                                $('#balance_to_operator_btn').addClass('hide').hide();
                                $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                            }
                            if (typeof window.show_hide_excess_shortage_tab === 'function') window.show_hide_excess_shortage_tab();
                            window.__petro_balance_to_operator_active = false;
                            if (typeof window.clearBalanceLock === 'function') {
                                window.clearBalanceLock();
                            }
                            if (typeof saveFormDataToSession === 'function') saveFormDataToSession();
                        }, 1500);
                    }, 200);
                });
            });

            (function () {
                // Initialize Balance to Operator flag
                window.__petro_skip_add_payment = false;

                // Restore form data from session on page load
                restoreFormDataFromSession();
                // Save form data periodically (every 5 seconds)
                setInterval(saveFormDataToSession, 5000);

                // Save form data when leaving the page
                $(window).on('beforeunload', function () {
                    saveFormDataToSession();
                });

                // Save form data when modal is being closed (Back button)
                $('.add_payment').on('hide.bs.modal', function () {
                    saveFormDataToSession();
                });

                // Save form data on any input change
                $('#settlement_form').on('change keyup', 'input, select, textarea', function () {
                    saveFormDataToSession();
                });

                // Call show_hide_excess_shortage_tab on modal open
                $(document).on('shown.bs.modal', '.add_payment', function () {
                    // Restore data when modal opens
                    restoreFormDataFromSession();
                    // Immediately call to set correct tab visibility
                    show_hide_excess_shortage_tab();
                    // Also call after a small delay to ensure DOM is fully ready
                    setTimeout(function () {
                        show_hide_excess_shortage_tab();
                    }, 100);
                    // Sync Total Amount and Balance from main page (so modal matches Meter Sales Total after any sale update)
                    var $mainMeter = $('#meter_sale_total');
                    if ($mainMeter.length && $mainMeter.closest('.add_payment').length === 0) {
                        var meter_sale_totals = parseFloat($mainMeter.val()) || 0;
                        var shift_operator_other_sale_total = parseFloat($('#shift_operator_other_sale_total').val()) || 0;
                        var other_sale_totals = (parseFloat($('#other_sale_total').val()) || 0) + shift_operator_other_sale_total;
                        var other_income_totals = parseFloat($('#other_income_total').val()) || 0;
                        var customer_payment_totals = parseFloat($('#customer_payment_total').val()) || 0;
                        var total_amount = meter_sale_totals + other_sale_totals + other_income_totals + customer_payment_totals;
                        var total_paid = parseFloat($('.add_payment #total_paid').val()) || 0;
                        var total_balance = total_amount - total_paid;
                        var prec = typeof __currency_precision !== 'undefined' ? __currency_precision : 2;
                        $('.add_payment #total_amount').val(total_amount);
                        $('.add_payment .total_amount').text(typeof __number_f !== 'undefined' ? __number_f(total_amount, false, false, prec) : total_amount);
                        $('.add_payment #total_balance').val(total_balance);
                        $('.add_payment .total_balance').text(typeof __number_f !== 'undefined' ? __number_f(total_balance, false, false, prec) : total_balance);
                    }
                });

                // Also call on modal shown to handle page refresh scenarios
                $('.add_payment').on('shown.bs.modal', function () {
                    restoreFormDataFromSession();
                    show_hide_excess_shortage_tab();
                    // Sync Total Amount/Balance from main page when modal is shown
                    var $mainMeter = $('#meter_sale_total');
                    if ($mainMeter.length && $mainMeter.closest('.add_payment').length === 0) {
                        var meter_sale_totals = parseFloat($mainMeter.val()) || 0;
                        var shift_operator_other_sale_total = parseFloat($('#shift_operator_other_sale_total').val()) || 0;
                        var other_sale_totals = (parseFloat($('#other_sale_total').val()) || 0) + shift_operator_other_sale_total;
                        var other_income_totals = parseFloat($('#other_income_total').val()) || 0;
                        var customer_payment_totals = parseFloat($('#customer_payment_total').val()) || 0;
                        var total_amount = meter_sale_totals + other_sale_totals + other_income_totals + customer_payment_totals;
                        var total_paid = parseFloat($('.add_payment #total_paid').val()) || 0;
                        var total_balance = total_amount - total_paid;
                        var prec = typeof __currency_precision !== 'undefined' ? __currency_precision : 2;
                        $('.add_payment #total_amount').val(total_amount);
                        $('.add_payment .total_amount').text(typeof __number_f !== 'undefined' ? __number_f(total_amount, false, false, prec) : total_amount);
                        $('.add_payment #total_balance').val(total_balance);
                        $('.add_payment .total_balance').text(typeof __number_f !== 'undefined' ? __number_f(total_balance, false, false, prec) : total_balance);
                    }
                });

                // Save form data periodically (every 5 seconds) to handle connection loss
                setInterval(function () {
                    if ($('.add_payment').is(':visible')) {
                        saveFormDataToSession();
                    }
                }, 5000);

                // Save form data before page unload (back button, refresh, etc.)
                $(window).on('beforeunload', function () {
                    saveFormDataToSession();
                });

                function openSettlementFinalizePrintWindow() {
                    var printWindow = null;

                    try {
                        printWindow = window.open('', '_blank');
                    } catch (e) {
                        printWindow = null;
                    }

                    if (printWindow) {
                        printWindow.document.open();
                        printWindow.document.write(
                            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Settlement Print</title></head>' +
                            '<body style="font-family: Arial, sans-serif; padding: 24px;">Preparing print preview...</body></html>'
                        );
                        printWindow.document.close();
                        printWindow.focus();
                    }

                    return printWindow;
                }

                function writeSettlementFinalizePrint(printWindow, printContent, fallbackUrl) {
                    var hasContent = printContent && typeof printContent === 'string' && printContent.trim().length > 0;

                    if (!printWindow || printWindow.closed) {
                        if (fallbackUrl) {
                            window.open(fallbackUrl, '_blank');
                        } else {
                            toastr.warning('Please allow popups to print the settlement');
                        }
                        return;
                    }

                    if (!hasContent) {
                        if (fallbackUrl) {
                            printWindow.onload = function () {
                                setTimeout(function () {
                                    printWindow.focus();
                                    printWindow.print();
                                }, 700);
                            };
                            printWindow.location.href = fallbackUrl;
                        } else {
                            printWindow.close();
                            toastr.warning('Print content is missing. Please try reprint.');
                        }
                        return;
                    }

                    var printableHtml = /<html[\s>]/i.test(printContent)
                        ? printContent
                        : '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Settlement Print</title>' +
                            '<style>@media print { body { margin: 0; } }</style></head><body>' +
                            printContent +
                            '</body></html>';

                    printWindow.document.open();
                    printWindow.document.write(printableHtml);
                    printWindow.document.close();
                    printWindow.focus();

                    var runPrint = function () {
                        if (printWindow && !printWindow.closed) {
                            printWindow.focus();
                            printWindow.print();
                        }
                    };

                    if (printWindow.document.readyState === 'complete') {
                        setTimeout(runPrint, 700);
                    } else {
                        printWindow.onload = function () {
                            setTimeout(runPrint, 700);
                        };
                    }
                }

                //save settlement add payment js

                $(document).off("click", "#settlement_save_btn").on("click", "#settlement_save_btn", function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    console.log("save settlement");

                    var $btn = $(this);

                    // Double-submission prevention
                    if ($btn.data('submitting')) {
                        console.log("Settlement save already in progress, ignoring click");
                        return false;
                    }

                    // Clear the balance lock interval when finalizing
                    if (window.__petro_balance_lock_interval) {
                        clearInterval(window.__petro_balance_lock_interval);
                        window.__petro_balance_lock_interval = null;
                    }
                    window.__petro_balance_locked = false;

                    // Mark as submitting
                    $btn.data('submitting', true);
                    $btn.attr("disabled", "disabled");

                    var url = $("#settlement_form").attr("action");

                    if (!url) {
                        toastr.error("Form action URL not found!");
                        $btn.removeAttr("disabled");
                        $btn.data('submitting', false);
                        return false;
                    }

                    var settlement_no = $("#settlement_no").val();
                    var printWindow = openSettlementFinalizePrintWindow();

                    var no_change = $("#no_change").val();



                    var qtyArray = [];

                    $('.denom_qty').each(function () {

                        var qtyValue = $(this).val();

                        qtyArray.push(qtyValue); // Add the value to the array

                    });



                    var denomArray = [];

                    $('.denom_value').each(function () {

                        var denomValue = $(this).val();

                        denomArray.push(denomValue); // Add the value to the array

                    });



                    var denoEnabled = 0;



                    if ($('#enable_cash_denoms').is(':checked')) {

                        denomEnabled = 1;

                    } else {

                        denomEnabled = 0;

                    }

                    var shift_ids = $('#shift_number').val();







                    $.ajax({

                        method: "post",

                        url: url,

                        dataType: "json",

                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        data: { settlement_no: settlement_no, denom_qty: qtyArray, denom_value: denomArray, denom_enabled: denomEnabled, no_change: no_change, shift_ids: shift_ids },

                        success: function (result) {
                            console.log("Save response type:", typeof result);
                            console.log("Save response:", result);

                            // Check if result is JSON (object with success property)
                            if (result && typeof result === 'object' && result.hasOwnProperty('success')) {
                                // Result is JSON response
                                if (result.success === 0) {
                                    // Error response
                                    toastr.error(result.msg || "Something went wrong while saving.");
                                    if (printWindow && !printWindow.closed) {
                                        printWindow.close();
                                    }
                                    $btn.removeAttr("disabled"); // Re-enable button on error
                                    $btn.data('submitting', false); // Reset submitting flag
                                    return;
                                } else {
                                    // Success response (JSON)
                                    console.log("Save successful (JSON response)");
                                    toastr.success(result.msg || "Saved Successfully");
                                    localStorage.removeItem('lastUpdateData');
                                    clearFormDataFromSession(); // Clear session data after successful save

                                    if (result.html) {
                                        $("#settlement_print").html(result.html);
                                    }
                                    writeSettlementFinalizePrint(
                                        printWindow,
                                        result.html,
                                        result.settlement_id ? "{{ url('/petro-general/settlement/print') }}/" + result.settlement_id : null
                                    );

                                    // Close modal and return to a blank Direct Settlement create page.
                                    // The next operator selection should generate the next ST/DST pair fresh.
                                    setTimeout(function () {
                                        $('.add_payment').modal('hide');
                                        window.location.href = "{{ url('/petro-general/settlement/create') }}";
                                    }, 3000);
                                    return;
                                }
                            }

                            // Result is HTML view (string) - success case (fallback for non-AJAX requests)
                            console.log("Save successful, showing print view (HTML response)");
                            toastr.success("Saved Successfully");
                            localStorage.removeItem('lastUpdateData');
                            clearFormDataFromSession(); // Clear session data after successful save
                            $("#settlement_print").html(result);
                            writeSettlementFinalizePrint(printWindow, result, null);

                            // Close modal and reload page after a slightly longer delay
                            // to ensure print window preparation isn't interrupted
                            setTimeout(function () {
                                $('.add_payment').modal('hide');
                                location.reload();
                            }, 3000);

                        },

                        error: function (xhr, status, error) {
                            console.error("Save error:", error, xhr.responseText);
                            var errorMessage = "Something went wrong while saving.";
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.msg) {
                                    errorMessage = response.msg;
                                }
                            } catch (e) {
                                // Use default error message
                            }
                            toastr.error(errorMessage);
                            if (printWindow && !printWindow.closed) {
                                printWindow.close();
                            }
                            $btn.removeAttr("disabled"); // Re-enable button on error
                            $btn.data('submitting', false); // Reset submitting flag on error
                        }

                    });

                });

                // Prevent form from submitting normally (only allow AJAX submission via button)
                $(document).off("submit", "#settlement_form").on("submit", "#settlement_form", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Form submit prevented - use Finalize Settlement button instead");
                    return false;
                });



                $(document).off("click", ".cash_add").on("click", ".cash_add", function () {
                    if ($("#cash_amount").val() == "") {
                        toastr.error("Please enter amount");
                        return false;
                    }

                    var cash_customer_id = $("#cash_customer_id").val();
                    var cash_amount = $("#cash_amount").val();
                    var settlement_no = $("#settlement_no").val();
                    var customer_name = $("#cash_customer_id :selected").text();
                    var cash_note = $("#cash_note").val();
                    var is_edit = $("#is_edit").val() ?? 0;

                    $.ajax({
                        method: "post",
                        url: "/petro-general/settlement/payment/save-cash-payment",
                        data: {
                            customer_id: cash_customer_id,
                            amount: cash_amount,
                            settlement_no: settlement_no,
                            note: cash_note,
                            is_edit: is_edit
                        },
                        success: function (result) {
                            if (!result.success) {
                                toastr.error(result.msg);
                            } else {
                                if ($('#calculate_cash').is(':checked')) {
                                    $(".denoms_totals").hide();
                                    $(".cash_to_disable").hide();
                                    $("#cash_amount").prop('readonly', true);
                                } else {
                                    $(".denoms_totals").show();
                                    $(".cash_to_disable").show();
                                    $("#cash_amount").prop('readonly', false);
                                }

                                settlement_cash_payment_id = result.settlement_cash_payment_id;
                                add_payment(cash_amount);

                                $("#cash_table tbody").prepend(`
                                    <tr> 
                                        <td>${customer_name}</td>
                                        <td class="cash_amount">${__number_f(cash_amount, false, false, __currency_precision)}</td>
                                        <td>${cash_note}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="/petro-general/settlement/payment/delete-cash-payment/${settlement_cash_payment_id}">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `);

                                $(".cash_fields").val("");
                                calculateTotal("#cash_table", ".cash_amount", ".cash_total");
                                
                                handlePaymentSuccessConfirmation();
                            }
                        },
                        error: function (xhr) {
                            var msg = "Please enter a positive amount for Shortage";
                            if (xhr.responseJSON && xhr.responseJSON.msg) {
                                msg = xhr.responseJSON.msg;
                            }
                            toastr.error(msg);
                        }
                    });
                });

                $(document).on("click", ".delete_cash_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#cash_table", ".cash_amount", ".cash_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                // $(document).on("click", ".customer_loans_add", function () {
                $(document).off("click", ".customer_loans_add").on("click", ".customer_loans_add", function () {

                    if ($("#customer_loans_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var cash_customer_id = $("#customer_loans_customer_id").val();

                    var cash_amount = $("#customer_loans_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var customer_name = $("#customer_loans_customer_id :selected").text();

                    var is_edit = $("#is_edit").val() ?? 0;



                    var note = $("#customer_loans_note").val();





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-customer-loans",

                        data: {

                            customer_id: cash_customer_id,

                            amount: cash_amount,

                            settlement_no: settlement_no,

                            is_edit: is_edit,

                            note: note

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {



                                settlement_customer_loan_id = result.settlement_customer_loan_id;

                                add_payment(cash_amount);

                                $("#customer_loans_table tbody").prepend(

                                    `

                    <tr> 

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td class="customer_loan_amount">` +

                                    __number_f(cash_amount, false, false, __currency_precision) +

                                    `</td>

                        

                        <td>` +

                                    note +

                                    `</td>

                        

                        <td><button type="button" class="btn btn-xs btn-danger delete_customer_loans_payment" data-href="/petro-general/settlement/payment/delete-customer-loans/` +

                                    settlement_customer_loan_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".customer_loans_field").val("");

                                calculateTotal("#customer_loans_table", ".customer_loan_amount", ".customer_loans_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_customer_loans_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");



                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#customer_loans_table", ".customer_loan_amount", ".customer_loans_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                // $(document).on("click", ".loan_payments_add", function () {
                $(document).off("click", ".loan_payments_add").on("click", ".loan_payments_add", function () {

                    if ($("#loan_payments_amount").val() === "") {

                        toastr.error("Please enter amount");

                        return false;

                    }



                    if ($("#loan_payments_bank").val() === "") {

                        toastr.error("Please choose loan account");

                        return false;

                    }





                    var loan_payments_amount = $("#loan_payments_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var loan_payments_bank = $("#loan_payments_bank").val();



                    var bank_name = $("#loan_payments_bank :selected").text();

                    var loan_payments_note = $("#loan_payments_note").val();

                    var is_edit = $("#is_edit").val() ?? 0;





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-loan-payment",

                        data: {

                            loan_account: loan_payments_bank,

                            amount: loan_payments_amount,

                            settlement_no: settlement_no,

                            note: loan_payments_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {



                                settlement_loan_payment_id = result.settlement_loan_payment_id;

                                add_payment(loan_payments_amount);

                                $("#loan_payments_table tbody").prepend(

                                    `

                    <tr> 

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td class="loan_payments_amount">` +

                                    __number_f(loan_payments_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    loan_payments_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_loan_payment" data-href="/petro-general/settlement/payment/delete-loan-payment/` +

                                    settlement_loan_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".loan_payments_fields").val("");

                                calculateTotal("#loan_payments_table", ".loan_payments_amount", ".loan_payments_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_loan_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#loan_payments_table", ".loan_payments_amount", ".loan_payments_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });



                // $(document).on("click", ".drawing_payments_add", function () {
                $(document).off("click", ".drawing_payments_add").on("click", ".drawing_payments_add", function () {

                    if ($("#drawing_payments_amount").val() === "") {

                        toastr.error("Please enter amount");

                        return false;

                    }



                    if ($("#drawing_payments_bank").val() === "") {

                        toastr.error("Please choose account");

                        return false;

                    }





                    var loan_payments_amount = $("#drawing_payments_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var loan_payments_bank = $("#drawing_payments_bank").val();



                    var bank_name = $("#drawing_payments_bank :selected").text();

                    var loan_payments_note = $("#drawing_payments_note").val();

                    var is_edit = $("#is_edit").val() ?? 0;





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-drawing-payment",

                        data: {

                            loan_account: loan_payments_bank,

                            amount: loan_payments_amount,

                            settlement_no: settlement_no,

                            note: loan_payments_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {



                                settlement_loan_payment_id = result.settlement_loan_payment_id;

                                add_payment(loan_payments_amount);

                                $("#drawing_payments_table tbody").prepend(

                                    `

                    <tr> 

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td class="loan_payments_amount">` +

                                    __number_f(loan_payments_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    loan_payments_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_drawing_payment" data-href="/petro-general/settlement/payment/delete-drawing-payment/` +

                                    settlement_loan_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".loan_payments_fields").val("");

                                calculateTotal("#drawing_payments_table", ".drawing_payments_amount", ".drawing_payments_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_drawing_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#drawing_payments_table", ".drawing_payments_amount", ".drawing_payments_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                // $(document).on("click", ".cash_deposit_add", function () {
                $(document).off("click", ".cash_deposit_add").on("click", ".cash_deposit_add", function () {

                    if ($("#cash_deposit_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var bank_id = $("#cash_deposit_bank").val();

                    var bank_name = $("#cash_deposit_bank :selected").text();

                    var cash_deposit_amount = $("#cash_deposit_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var account = $("#cash_deposit_account").val();

                    var time = $("#cash_deposit_time").val();

                    // var image = $("#image").val();

                    var is_edit = $("#is_edit").val() ?? 0;

                    swal({
                        title: "Add Cash Deposit?",
                        text: "Are you sure you want to add this cash deposit?",
                        icon: "warning",
                        buttons: true,
                        dangerMode: false,
                    }).then(function(confirmed) {
                        if (!confirmed) return;

                        $.ajax({

                            method: "post",

                            url: "/petro-general/settlement/payment/save-cash-deposit",

                            data: {

                                bank_id, cash_deposit_amount, settlement_no, account, time, is_edit
                            },

                            success: function (result) {

                                if (!result.success) {

                                    toastr.error(result.msg);

                                }

                                else {

                                    settlement_cash_payment_id = result.settlement_cash_payment_id;

                                    add_payment(cash_deposit_amount);

                                    $("#cash_deposit_table tbody").prepend(
                                        `<tr>
                                            <td>` + bank_name + `</td>
                                            <td>` + account + `</td>
                                            <td class="cash_deposit_amount">` + __number_f(cash_deposit_amount, false, false, __currency_precision) + `</td>
                                            <td>` + time + `</td>
                                            <td><button type="button" class="btn btn-xs btn-danger delete_cash_deposit" data-href="/petro-general/settlement/payment/delete-cash-deposit/` + settlement_cash_payment_id + `"><i class="fa fa-times"></i></button></td>
                                        </tr>`
                                    );

                                    calculateTotal("#cash_deposit_table", ".cash_deposit_amount", ".cash_deposit_total");
                                    handlePaymentSuccessConfirmation();

                                }

                            },

                        });

                    });

                });

                $(document).on("click", ".delete_cash_deposit", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#cash_deposit_table", ".cash_deposit_amount", ".cash_deposit_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                //card payments

                // $(document).on("click", ".card_add", function () {
                $(document).off("click", ".card_add").on("click", ".card_add", function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if ($("#card_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }





                    var card_customer_id = $("#card_customer_id").val();

                    var customer_name = $("#card_customer_id :selected").text();

                    var card_amount = $("#card_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var card_type = $("#card_type :selected").text();

                    var card_type_id = $("#card_type").val();

                    var card_number = $("#card_number").val();

                    var card_note = $("#card_note").val();

                    var slip_no = $("#slip_no").val();

                    var is_edit = $("#is_edit").val() ?? 0;



                    if (card_type_id == null || card_type_id == "" || card_type_id == "undefined") {

                        toastr.error("Please select card type");

                        return false;

                    }

                    swal({
                        title: "Add Card Payment?",
                        text: "Are you sure you want to add this card payment?",
                        icon: "warning",
                        buttons: true,
                        dangerMode: false,
                    }).then(function(confirmed) {
                        if (!confirmed) return;

                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-card-payment",

                        data: {

                            customer_id: card_customer_id,

                            amount: card_amount,

                            card_type: card_type_id,

                            card_number: card_number,

                            settlement_no: settlement_no,

                            note: card_note,

                            slip_no: slip_no,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_card_payment_id = result.settlement_card_payment_id;

                                add_payment(card_amount);

                                $("#card_table tbody").prepend(

                                    `

                    <tr> 

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td>` +

                                    card_type +

                                    `</td>

                        <td>` +

                                    card_number +

                                    `</td>

                        <td class="card_amount">` +

                                    __number_f(card_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    slip_no +

                                    `</td>

                        <td>` +

                                    card_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_card_payment" data-href="/petro-general/settlement/payment/delete-card-payment/` +

                                    settlement_card_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".card_fields").val("").trigger('change');

                                $(".cash_fields").val("").trigger('change');

                                calculateTotal("#card_table", ".card_amount", ".card_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },
                        error: function (xhr, status, error) {
                            console.error('Card payment save error:', error);
                            toastr.error('Failed to save card payment. Please check console for details.');
                        }

                    });

                    });

                });

                $(document).on("click", ".delete_card_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#card_table", ".card_amount", ".card_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });

                //cheque payments

                // $(document).on("click", ".cheque_add", function () {
                $(document).off("click", ".cheque_add").on("click", ".cheque_add", function () {
                    if ($("#cheque_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var cheque_customer_id = $("#cheque_customer_id").val();

                    var customer_name = $("#cheque_customer_id :selected").text();

                    var cheque_amount = $("#cheque_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var cheque_date = $("#cheque_date").val();

                    var bank_name = $("#bank_name").val();

                    var cheque_number = $("#cheque_number").val();

                    var cheque_post_dated_cheque = $("#cheque_post_dated_cheque").val();

                    var cheque_note = $("#cheque_note").val();

                    var is_edit = $("#is_edit").val() ?? 0;

                    swal({
                        title: "Add Cheque Payment?",
                        text: "Are you sure you want to add this cheque payment?",
                        icon: "warning",
                        buttons: true,
                        dangerMode: false,
                    }).then(function(confirmed) {
                        if (!confirmed) return;

                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-cheque-payment",

                        data: {

                            customer_id: cheque_customer_id,

                            amount: cheque_amount,

                            bank_name: bank_name,

                            cheque_date: cheque_date,

                            cheque_number: cheque_number,

                            settlement_no: settlement_no,

                            note: cheque_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_cheque_payment_id = result.settlement_cheque_payment_id;

                                add_payment(cheque_amount);

                                $("#cheque_table tbody").prepend(

                                    `

                    <tr> 

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td>` +

                                    cheque_number +

                                    `</td>

                        <td>` +

                                    cheque_date +

                                    `</td>

                        <td class="cheque_amount">` +

                                    __number_f(cheque_amount, false, false, __currency_precision) +

                                    `</td>

                         <td>` +

                                    cheque_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_cheque_payment" data-href="/petro-general/settlement/payment/delete-cheque-payment/` +

                                    settlement_cheque_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".cheque_fields").val("");

                                $(".cash_fields").val("");

                                calculateTotal("#cheque_table", ".cheque_amount", ".cheque_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },

                    });

                    });

                });

                $(document).on("click", ".delete_cheque_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#cheque_table", ".cheque_amount", ".cheque_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });

                //credit_sale payments

                // $(document).on("click", ".credit_sale_add", function () {

                //      console.log('789');

                //     if ($("#credit_sale_amount").val() == "") {

                //         toastr.error("Please enter amount");

                //         return false;

                //     }

                //     var credit_sale_customer_id = $("#credit_sale_customer_id").val();

                //     var customer_name = $("#credit_sale_customer_id :selected").text();

                //     var credit_sale_product_id = $("#credit_sale_product_id").val();

                //     var credit_sale_product_name = $("#credit_sale_product_id :selected").text();

                //     if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !== null && $("#customer_reference_one_time").val() !== undefined) {

                //         var customer_reference = $("#customer_reference_one_time").val();

                //     } else {

                //         var customer_reference = $("#customer_reference").val();

                //     }

                //     var settlement_no = $("#settlement_no").val();

                //     var order_date = $("#order_date").val();

                //     var order_number = $("#order_number").val();



                //     var credit_sale_price = __read_number($("#unit_price"));

                //     var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;

                //     var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;

                //     var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;

                //     var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                //     var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;



                //     var outstanding = $(".current_outstanding").text();

                //     var credit_limit = $(".credit_limit").text();

                //     var credit_note = $("#credit_note").val();

                //     var is_edit = $("#is_edit").val() ?? 0;



                //     $.ajax({

                //         method: "post",

                //         url: "/petro-general/settlement/payment/save-credit-sale-payment",

                //         data: {

                //             settlement_no: settlement_no,

                //             customer_id: credit_sale_customer_id,

                //             product_id: credit_sale_product_id,

                //             order_number: order_number,

                //             order_date: order_date,



                //             price: credit_sale_price,

                //             unit_discount: credit_unit_discount,

                //             qty: credit_sale_qty,

                //             amount: credit_total_amount,

                //             sub_total: credit_sub_total,

                //             total_discount: credit_total_discount,

                //             outstanding: outstanding,

                //             credit_limit: credit_limit,

                //             customer_reference: customer_reference,

                //             note: credit_note,

                //             is_edit: is_edit

                //         },

                //         success: function (result) {

                //             if (!result.success) {

                //                 toastr.error(result.msg);

                //             } else {

                //                 settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;

                //                 add_payment(credit_total_amount-credit_total_discount);

                //                 $("#credit_sale_table tbody").prepend(

                //                     `

                //                     <tr> 

                //                         <td>` +

                //                         customer_name +

                //                         `</td>

                //                         <td>` +

                //                         outstanding +

                //                         `</td>

                //                         <td>` +

                //                         credit_limit +

                //                         `</td>

                //                         <td>` +

                //                         order_number +

                //                         `</td>

                //                         <td>` +

                //                         order_date +

                //                         `</td>

                //                         <td>` +

                //                         customer_reference +

                //                         `</td>

                //                         <td>` +

                //                         credit_sale_product_name +

                //                         `</td>

                //                         <td>` +

                //                         __number_f(credit_sale_price, false, false, __currency_precision) +

                //                         `</td>

                //                         <td>` +

                //                         __number_f(credit_sale_qty, false, false, __currency_precision) +

                //                         `</td>

                //                         <td class="credit_sale_amount">` +

                //                         __number_f(credit_total_amount, false, false, __currency_precision) +

                //                         `</td>



                //                         <td class="credit_tbl_discount_amount">` +

                //                         __number_f(credit_total_discount, false, false, __currency_precision) +

                //                         `</td>

                //                         <td class="credit_tbl_total_amount">` +

                //                         __number_f(credit_sub_total, false, false, __currency_precision) +

                //                         `</td>





                //                         <td>` +

                //                       credit_note +

                //                         `</td>

                //                         <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petro-general/settlement/payment/delete-credit-sale-payment/` +

                //                         settlement_credit_sale_payment_id +

                //                         `"><i class="fa fa-times"></i></button>

                //                         </td>

                //                     </tr>

                //                 `

                //                 );

                //                 $("#customer_reference_one_time").val("").trigger("change");

                //                 $(".credit_sale_fields").val("");

                //                 $(".cash_fields").val("");

                //                 $("#credit_sale_product_id").trigger('change');

                //                 $("#order_number").val(order_number);

                //                 calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");

                //                 calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");

                //                 calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");



                //             }

                //         },

                //     });

                // });



                $(document).on('input', '#credit_total_amount', function () {

                    $("#credit_sale_qty").prop('disabled', true);



                    let price = __read_number($("#unit_price")) ?? 0;

                    let total_amount = __read_number($("#credit_total_amount")) ?? 0;

                    let qty = total_amount / price;



                    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                    let unit_discount = total_discount / qty;

                    let amount = total_amount - total_discount



                    __write_number($("#credit_sale_amount"), amount);

                    __write_number($("#unit_discount"), unit_discount);

                    __write_number_without_decimal_format($("#credit_sale_qty"), qty);





                });



                $(document).on('input', '#credit_sale_qty', function () {

                    if ($('#total_amount_enable').val() != '1') {

                        $("#credit_total_amount").prop('disabled', true);

                    }



                    let price = __read_number($("#unit_price")) ?? 0;

                    let qty = __read_number($("#credit_sale_qty")) ?? 0;

                    let total_amount = price * qty;



                    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                    let unit_discount = total_discount / qty;

                    let amount = total_amount - total_discount



                    __write_number($("#credit_sale_amount"), amount);

                    __write_number($("#unit_discount"), unit_discount);

                    __write_number($("#credit_total_amount"), total_amount);



                });



                $(document).on("change", "#credit_discount_amount, #unit_price", function () {

                    let price = __read_number($("#unit_price")) ?? 0;

                    let qty_check = __read_number($("#credit_sale_qty")) ?? 0;



                    var qty = 0;

                    var total_amount = 0;



                    if (qty_check > 0) {

                        qty = __read_number($("#credit_sale_qty")) ?? 0;

                        total_amount = price * qty;

                        __write_number($("#credit_total_amount"), total_amount);

                    } else {

                        total_amount = __read_number($("#credit_total_amount")) ?? 0;

                        qty = total_amount / price;

                        __write_number_without_decimal_format($("#credit_sale_qty"), qty);

                    }







                    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                    let unit_discount = total_discount / qty;

                    let amount = total_amount - total_discount



                    __write_number($("#unit_discount"), unit_discount);

                    __write_number($("#credit_sale_amount"), amount);



                });



                $(document).on("change", "#credit_sale_product_id", function () {

                    if ($(this).val()) {

                        $.ajax({

                            method: "get",

                            url: "/petro-general/settlement/payment/get-product-price",

                            data: { product_id: $(this).val() },

                            success: function (result) {

                                $("#unit_price").val(result.price);

                                $("#unit_price").trigger('change');



                                $("#credit_total_amount").prop("disabled", false);

                                $("#credit_sale_qty").prop("disabled", false);

                                if ($("#manual_discount").val() == 1) {

                                    $("#credit_discount_amount").prop("disabled", false);

                                }



                            },

                        });

                    } else {

                        $("#credit_total_amount").prop("disabled", true);

                        $("#credit_sale_qty").prop("disabled", true);

                        $("#credit_discount_amount").prop("disabled", true);

                    }

                });

                $(document).on("change", "#credit_sale_customer_id", function () {

                    $.ajax({

                        method: "get",

                        url: "/petro-general/settlement/payment/get-customer-details/" + $(this).val(),

                        data: {},

                        success: function (result) {

                            $(".current_outstanding").text(result.total_outstanding);

                            $(".credit_limit").text(result.credit_limit);

                            if (result.manual_bill_settlement == 1) {

                                $('.unit_discount').prop('disabled', false);

                                $('.credit_total_amount').prop('disabled', false);

                                $('.credit_discount_amount').prop('disabled', false);

                                // Set hidden input to 1

                                $('#total_amount_enable').val(1);

                            }


                            // Clear existing options
                            $("#customer_reference").empty();

                            if (result.customer_references && result.customer_references.length > 0) {
                                // Add "Please Select"
                                $("#customer_reference").append(`<option value="">Please Select</option>`);


                                // Append other vehicle options
                                result.customer_references.forEach(function (ref) {
                                    $("#customer_reference").append(`<option value="` + ref.reference + `">` + ref.reference + `</option>`);
                                });
                            } else {
                                // If no vehicles, just show "No Vehicle No" selected
                                $("#customer_reference").append(`<option selected="selected" value="no_vehicle">No Vehicle No</option>`);
                            }

                            // Optionally, trigger change if needed
                            // $("#customer_reference").val("no_vehicle").trigger("change");



                        },

                    });

                });

                // Unbind first so only this handler runs (avoids double-run with petro_payment.js)
                $(document).off("click", ".delete_credit_sale_payment").on("click", ".delete_credit_sale_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                // Use net_amount (after discount) for balance calculation.
                                // Always call delete_payment so the removed amount is added back to the balance section.
                                let this_amount = result.net_amount || result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");

                                calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");

                                calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });

                //expense payments

                // $(document).on("click", ".expense_add", function () {
                $(document).off("click", ".expense_add").on("click", ".expense_add", function () {

                    if ($("#expense_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var settlement_no = $("#settlement_no").val();

                    var expense_number = $("#expense_number").val();

                    var reference_no = $("#reference_no").val();

                    var expense_account = $("#expense_account").val();

                    var expense_account_name = $("#expense_account :selected").text();

                    var expense_category = $("#expense_category").val();

                    var expense_category_name = $("#expense_category :selected").text();

                    var expense_reason = $("#expense_reason").val();

                    var expense_amount = $("#expense_amount").val();

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-expense-payment",

                        data: {

                            settlement_no: settlement_no,

                            expense_number: expense_number,

                            category_id: expense_category,

                            reference_no: reference_no,

                            account_id: expense_account,

                            reason: expense_reason,

                            amount: expense_amount,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_expense_payment_id = result.settlement_expense_payment_id;

                                add_payment(expense_amount);

                                $("#expense_table tbody").prepend(

                                    `

                    <tr> 

                        <td>` +

                                    expense_number +

                                    `</td>

                        <td>` +

                                    expense_category_name +

                                    `</td>

                        <td>` +

                                    reference_no +

                                    `</td>

                        <td>` +

                                    expense_account_name +

                                    `</td>

                        <td>` +

                                    expense_reason +

                                    `</td>

                        <td class="expense_amount">` +

                                    __number_f(expense_amount, false, false, __currency_precision) +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_expense_payment" data-href="/petro-general/settlement/payment/delete-expense-payment/` +

                                    settlement_expense_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".expense_fields").val("").trigger('change');

                                $("#expense_number").val(result.expense_number);

                                calculateTotal("#expense_table", ".expense_amount", ".expense_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_expense_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");



                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#expense_table", ".expense_amount", ".expense_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });

                //shortage payments

                // $(document).on("click", ".shortage_add", function () {
                $(document).off("click", ".shortage_add").on("click", ".shortage_add", function () {
                    if ($("#shortage_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));

                    if (!isNaN(current_balance) && current_balance < 0) {
                        toastr.error("Balance is negative. Please use Excess");
                        return false;
                    }

                    var settlement_no = $("#settlement_no").val();

                    var shortage_amount = __read_number($("#shortage_amount")) ?? 0;

                    if (window.__petro_balance_to_operator_active && shortage_amount < 0) {
                        shortage_amount = Math.abs(shortage_amount);
                    }

                    if (isNaN(shortage_amount) || shortage_amount <= 0) {
                        toastr.error("Please enter a positive amount for Shortage");
                        return false;
                    }

                    var shortage_note = $("#shortage_note").val();



                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-shortage-payment",

                        data: {

                            settlement_no: settlement_no,

                            amount: shortage_amount,

                            note: shortage_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_shortage_payment_id = result.settlement_shortage_payment_id;

                                // Only call add_payment if NOT triggered by Balance to Operator
                                // (Balance to Operator already updated the balance manually)
                                if (!window.__petro_skip_add_payment) {
                                    if (typeof window.add_payment === 'function') {
                                        window.add_payment(shortage_amount);
                                    } else if (typeof window.petroSettlementAddPaymentCore === 'function') {
                                        window.petroSettlementAddPaymentCore(shortage_amount);
                                    }
                                }

                                var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'shortage')
                                    ? ' data-auto-balance="1"' : '';
                                $("#shortage_table tbody").prepend(

                                    `

                    <tr` + auto_balance_attr + `> 

                        <td></td>

                        <td class="shortage_amount">` +

                                    __number_f(shortage_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    shortage_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_shortage_payment" data-href="/petro-general/settlement/payment/delete-shortage-payment/` +

                                    settlement_shortage_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );
                                if (auto_balance_attr) {
                                    window.__petro_auto_balance_state = window.__petro_auto_balance_pending;
                                    window.__petro_auto_balance_pending = null;
                                }

                                if (!window.__petro_balance_to_operator_active) {
                                    $(".shortage_fields").val("");
                                }

                                $(".cash_fields").val("");

                                $("#shortage_number").val(result.shortage_number);

                                calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");

                                handlePaymentSuccessConfirmation();

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_shortage_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");



                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        dataType: "json",

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount != null ? parseFloat(result.amount) : 0;

                                if (!isNaN(this_amount)) {

                                    delete_payment(this_amount);

                                }

                                calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });

                //excess payments

                $(document).on("click", ".excess_add", function () {

                    console.log('function called')

                    var excess_amount_input = $("#excess_amount").val();

                    var excess_note = $("#excess_note").val();

                    if (excess_amount_input == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));

                    // Skip balance validation if triggered by Balance to Operator (balance was already updated to 0)
                    if (!window.__petro_balance_to_operator_active && !isNaN(current_balance) && current_balance > 0) {
                        toastr.error("Balance is positive. Please use Shortage");
                        return false;
                    }

                    var excess_amount = __read_number($("#excess_amount")) ?? 0;

                    if (isNaN(excess_amount) || excess_amount >= 0) {

                        toastr.error("Please enter a negative amount for Excess");

                        return false;

                    }

                    var settlement_no = $("#settlement_no").val();

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-excess-payment",

                        data: {

                            settlement_no: settlement_no,

                            amount: excess_amount,

                            note: excess_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_excess_payment_id = result.settlement_excess_payment_id;

                                // Only call add_payment if NOT triggered by Balance to Operator
                                // (Balance to Operator already updated the balance manually)
                                if (!window.__petro_skip_add_payment) {
                                    if (typeof window.add_payment === 'function') {
                                        window.add_payment(excess_amount);
                                    } else if (typeof window.petroSettlementAddPaymentCore === 'function') {
                                        window.petroSettlementAddPaymentCore(excess_amount);
                                    }
                                }

                                var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'excess')
                                    ? ' data-auto-balance="1"' : '';
                                $("#excess_table tbody").prepend(

                                    `

                    <tr` + auto_balance_attr + `> 

                        <td></td>

                        <td class="excess_amount">` +

                                    __number_f(excess_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    excess_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petro-general/settlement/payment/delete-excess-payment/` +

                                    settlement_excess_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );
                                if (auto_balance_attr) {
                                    window.__petro_auto_balance_state = window.__petro_auto_balance_pending;
                                    window.__petro_auto_balance_pending = null;
                                }

                                $(".excess_fields").val("");

                                $(".cash_fields").val("");

                                $("#excess_number").val(result.excess_number);

                                if (typeof rebuildExcessTable === 'function') {
                                    rebuildExcessTable();
                                } else {
                                    calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                                }

                                handlePaymentSuccessConfirmation();

                            }

                        },
                        error: function (xhr) {
                            var msg = "Please enter a negative amount for Excess";
                            if (xhr.responseJSON && xhr.responseJSON.msg) {
                                msg = xhr.responseJSON.msg;
                            }
                            toastr.error(msg);
                        }

                    });

                });

                $(document).on("click", ".delete_excess_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        dataType: "json",

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount != null ? parseFloat(result.amount) : 0;

                                if (!isNaN(this_amount)) {

                                    delete_payment(this_amount);

                                }

                                if (typeof rebuildExcessTable === 'function') {
                                    rebuildExcessTable();
                                } else {
                                    calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                                }

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });



                function calculateDenoms(amount = 0) {

                    var grand_total = 0;

                    $('.denom_amt').each(function () {

                        var amt = $(this).val();

                        // Check if amt exists and is not null/undefined before calling replace
                        if (amt && typeof amt === 'string') {
                            amt = parseFloat(amt.replace(/,/g, ""));
                        } else if (amt) {
                            amt = parseFloat(amt);
                        } else {
                            amt = 0;
                        }





                        if (!isNaN(amt)) {

                            grand_total += amt;

                        }

                    });





                    if ($('#calculate_cash').is(':checked')) {

                        $("#cash_amount").val(grand_total);

                        $("#cash_amount").prop('readonly', true);

                    } else {

                        $("#cash_amount").prop('readonly', false);

                    }



                    $('.denom_total').val(grand_total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));



                    var cashtotal = $(".cash_total").text() || "0";

                    var bal = parseFloat(cashtotal.replace(/,/g, "")) - grand_total + amount;

                    var total_balance_val = $("#total_balance").val() || "0";
                    total_balance = parseFloat(total_balance_val.replace(/,/g, ""));





                    if ($('#enable_cash_denoms').is(':checked')) {

                        if (bal == 0 && total_balance == 0) {

                            $("#settlement_save_btn").removeClass("hide");

                        } else {

                            $("#settlement_save_btn").addClass("hide");

                        }

                    } else {

                        if (total_balance == 0) {

                            $("#settlement_save_btn").removeClass("hide");

                        } else {

                            $("#settlement_save_btn").addClass("hide");

                        }

                    }





                    $(".denom_bal").val(bal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    if (typeof update_finalize_button_from_current_state === 'function') {
                        update_finalize_button_from_current_state();
                    }

                }



                window.clearBalanceLock = function() {
                    console.log('Clearing balance lock. Current interval:', window.__petro_balance_lock_interval, 'Locked:', window.__petro_balance_locked);
                    if (window.__petro_balance_lock_interval) {
                        clearInterval(window.__petro_balance_lock_interval);
                        window.__petro_balance_lock_interval = null;
                        console.log('Interval cleared physically');
                    }
                    window.__petro_balance_locked = false;
                    window.__petro_locked_total_paid = null;
                    window.__petro_locked_total_balance = null;

                    // Sync change to session storage immediately
                    if (typeof saveFormDataToSession === 'function') {
                        saveFormDataToSession();
                    }
                };

                // Alias for internal calls
                var clearBalanceLock = window.clearBalanceLock;

                window.add_payment = function(add_amount) {
                    try {
                        if (typeof window.petroSettlementAddPaymentCore === 'function') {
                            window.petroSettlementAddPaymentCore(add_amount);
                        } else {
                            add_amount = parseFloat(String(add_amount).replace(/,/g, ""));
                            if (isNaN(add_amount)) {
                                return;
                            }

                            var $ctx = $('.add_payment').length ? $('.add_payment') : $(document);

                            let total_balance = parseFloat(($ctx.find("#total_balance").val() || "0").toString().replace(/,/g, "")) || 0;
                            let total_paid = parseFloat(($ctx.find("#total_paid").val() || "0").toString().replace(/,/g, "")) || 0;

                            total_balance = total_balance - add_amount;
                            total_paid = total_paid + add_amount;

                            if (!window.__petro_skip_add_payment) {
                                window.clearBalanceLock();
                            }

                            $ctx.find("#total_balance").val(total_balance);
                            $ctx.find("#total_paid").val(total_paid);

                            $ctx.find(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
                            $ctx.find(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));

                            window.show_hide_excess_shortage_tab();

                            if (typeof calculateDenoms === 'function') {
                                calculateDenoms(add_amount);
                            }

                            if (total_balance === 0) {
                                $ctx.find('#balance_to_operator_btn').addClass('hide').hide();
                            } else {
                                $ctx.find('#balance_to_operator_btn').removeClass('hide').show();
                            }

                            if (typeof update_finalize_button_from_current_state === 'function') {
                                update_finalize_button_from_current_state();
                            }
                        }

                        if (typeof saveFormDataToSession === 'function') {
                            saveFormDataToSession();
                        }
                    } catch (e) {
                        console.error('Error in add_payment:', e);
                    }
                };

                // Alias for internal calls
                var add_payment = window.add_payment;

                // Direct Settlement: Credit Sales are SALES (affect Total Amount) AND also count as "paid" (accounts receivable)
                // So they affect both Total Amount and Total Paid
                function add_sale_amount(add_amount) {
                    add_amount = parseFloat(add_amount);
                    if (isNaN(add_amount)) return;

                    var $ctx = $('.add_payment').length ? $('.add_payment') : $(document);

                    let total_amount = parseFloat(($ctx.find("#total_amount").val() || "0").toString().replace(/,/g, "")) || 0;
                    let total_paid = parseFloat(($ctx.find("#total_paid").val() || "0").toString().replace(/,/g, "")) || 0;
                    let total_balance = parseFloat(($ctx.find("#total_balance").val() || "0").toString().replace(/,/g, "")) || 0;

                    // Manual sale addition should always clear the balance lock
                    clearBalanceLock();

                    // Credit sales increase both Total Amount (as sales) and Total Paid (as accounts receivable)
                    total_amount = total_amount + add_amount;
                    total_paid = total_paid + add_amount;
                    total_balance = total_amount - total_paid;

                    $ctx.find("#total_amount").val(total_amount);
                    $ctx.find("#total_paid").val(total_paid);
                    $ctx.find("#total_balance").val(total_balance);

                    $ctx.find(".total_amount").text(__number_f(total_amount, false, false, __currency_precision));
                    $ctx.find(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));
                    $ctx.find(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));

                    // Update tab visibility based on new balance
                    show_hide_excess_shortage_tab();

                    // Update Balance to Operator button based on balance: show whenever balance is non-zero
                    if (total_balance === 0) {
                        $ctx.find('#balance_to_operator_btn').addClass('hide').hide();
                    } else {
                        $ctx.find('#balance_to_operator_btn').removeClass('hide').show();
                    }

                    if (typeof update_finalize_button_from_current_state === 'function') {
                        update_finalize_button_from_current_state();
                    }

                    // Save to session storage
                    if (typeof saveFormDataToSession === 'function') {
                        saveFormDataToSession();
                    }
                }

                function show_hide_excess_shortage_tab() {
                    window.show_hide_excess_shortage_tab = show_hide_excess_shortage_tab;
                    var $ctx = $('.add_payment').length ? $('.add_payment') : $(document);
                    // Get balance value - try multiple sources
                    // CRITICAL: Check input value first (most accurate), then text display
                    var balanceText = $ctx.find("#total_balance").val() || $ctx.find(".total_balance").eq(0).text() || "0";

                    // Remove all formatting (commas, spaces, currency symbols) and parse
                    // Handle negative numbers that might be displayed as "(123.45)" or "-123.45"
                    let cleanBalance = balanceText.toString().replace(/,/g, "").replace(/\(/g, "-").replace(/\)/g, "").trim();
                    let total_balance = parseFloat(cleanBalance);

                    // Handle NaN
                    if (isNaN(total_balance)) {
                        total_balance = 0;
                    }

                    console.log('show_hide_excess_shortage_tab - Balance:', total_balance, 'Raw:', balanceText);

                    $('#settlement_form .shortage_tab, #settlement_form .excess_tab').parent('li').find('a').off('click.tab_lock');
                    $(document).find('li.disabled a').off('click.tab_lock');
                    $('#excess_amount').prop('disabled', false);

                    // Check if Balance to Operator is active - if so, show the appropriate tab temporarily
                    var isBalanceToOperatorActive = window.__petro_balance_to_operator_active || window.__petro_balance_locked;

                    // Show/hide tabs based on balance state (inappropriate tabs are hidden, not just disabled)
                    // Exception: When Balance to Operator is active, show the appropriate tab
                    if (total_balance > 0) {
                        // Balance is positive (shortage) - hide Excess tab, show Shortage tab
                        if (isBalanceToOperatorActive) {
                            // Show both tabs when Balance to Operator is active
                            $('#settlement_form .shortage_tab').parents('li:first').show().removeClass('disabled');
                            $('#settlement_form .excess_tab').parents('li:first').show().addClass('disabled');
                        } else {
                            $('#settlement_form .excess_tab').parents('li:first').hide().removeClass('disabled');
                            $('#settlement_form .shortage_tab').parents('li:first').show().removeClass('disabled');
                        }

                        $('.shortage_add').prop('disabled', false);
                        $('.excess_add_btn').prop('disabled', true);
                        $('#shortage_amount').prop('disabled', false);
                        $('#excess_amount').prop('disabled', true);

                        // Only auto-switch away from the opposite (Excess) tab. Leave other
                        // payment tabs (Credit Sales, Loan Payments, etc.) alone so their
                        // content doesn't disappear when the balance becomes non-zero.
                        var active_tab = $('#settlement_form .settlement_tabs li.active a').attr('href');
                        if (active_tab === '#excess_tab') {
                            $('.shortage_tab').tab('show');
                        }

                    } else if (total_balance < 0) {
                        // Balance is negative (excess) - hide Shortage tab, show Excess tab
                        if (isBalanceToOperatorActive) {
                            // Show both tabs when Balance to Operator is active
                            $('#settlement_form .excess_tab').parents('li:first').show().removeClass('disabled');
                            $('#settlement_form .shortage_tab').parents('li:first').show().addClass('disabled');
                        } else {
                            $('#settlement_form .shortage_tab').parents('li:first').hide().removeClass('disabled');
                            $('#settlement_form .excess_tab').parents('li:first').show().removeClass('disabled');
                        }

                        $('.shortage_add').prop('disabled', true);
                        $('.excess_add_btn').prop('disabled', false);
                        $('#shortage_amount').prop('disabled', true);
                        $('#excess_amount').prop('disabled', false);

                        // Only auto-switch away from the opposite (Shortage) tab. Leave other
                        // payment tabs (Credit Sales, Loan Payments, etc.) alone so their
                        // content doesn't disappear when the balance becomes non-zero.
                        var active_tab = $('#settlement_form .settlement_tabs li.active a').attr('href');
                        if (active_tab === '#shortage_tab') {
                            $('.excess_tab').tab('show');
                        }

                    } else {
                        // Balance is zero - if Balance to Operator was just used, keep that tab open and the other locked
                        var lastBalanceType = window.__petro_balance_to_operator_type;
                        // Fallback: infer from which table has the auto-balance row (fixes Shortage tab appearing locked after Balance to Operator)
                        var hasShortageAuto = $('#shortage_table tbody tr[data-auto-balance="1"]').length > 0;
                        var hasExcessAuto = $('#excess_table tbody tr[data-auto-balance="1"]').length > 0;
                        if (!lastBalanceType && (hasShortageAuto || hasExcessAuto)) {
                            lastBalanceType = hasShortageAuto ? 'shortage' : 'excess';
                        }
                        // CRITICAL: Use locked state if ANY of these conditions are true:
                        // - Balance to Operator is currently active
                        // - Balance is locked (balance_locked flag)
                        // - There's an auto-balance row in either table
                        // - lastBalanceType was set (even if active flag was already cleared)
                        var useLockedState = lastBalanceType && (
                            isBalanceToOperatorActive || 
                            window.__petro_balance_locked || 
                            hasShortageAuto || 
                            hasExcessAuto
                        );
                        if (useLockedState && lastBalanceType === 'shortage') {
                            // Shortage was added - keep Shortage tab active, lock Excess tab
                            $('#settlement_form .shortage_tab').parents('li:first').show().removeClass('disabled');
                            $('#settlement_form .excess_tab').parents('li:first').show().addClass('disabled');
                            $('.shortage_add').prop('disabled', false);
                            $('.excess_add_btn').prop('disabled', true);
                            $('#shortage_amount').prop('disabled', false);
                            $('#excess_amount').prop('disabled', true);
                        } else if (useLockedState && lastBalanceType === 'excess') {
                            // Excess was added - keep Excess tab active, lock Shortage tab
                            $('#settlement_form .excess_tab').parents('li:first').show().removeClass('disabled');
                            $('#settlement_form .shortage_tab').parents('li:first').show().addClass('disabled');
                            $('.excess_add_btn').prop('disabled', false);
                            $('.shortage_add').prop('disabled', true);
                            $('#excess_amount').prop('disabled', false);
                            $('#shortage_amount').prop('disabled', true);
                        } else {
                            // Normal zero balance - both tabs available
                            $('#settlement_form .excess_tab').parents('li:first').show().removeClass('disabled');
                            $('#settlement_form .shortage_tab').parents('li:first').show().removeClass('disabled');
                            $('.excess_add_btn').prop('disabled', false);
                            $('.shortage_add').prop('disabled', false);
                            $('#shortage_amount').prop('disabled', false);
                            $('#excess_amount').prop('disabled', false);
                        }
                    }

                    // Show/hide Finalize Settlement button and Balance to Operator button based on balance
                    if (total_balance === 0) {
                        // Balance is zero - show Finalize Settlement, hide Balance to Operator
                        $('#settlement_save_btn').removeClass('hide');
                        $('#balance_to_operator_btn').addClass('hide');
                    } else {
                        // Balance is not zero - hide Finalize Settlement, show Balance to Operator
                        $('#settlement_save_btn').addClass('hide');
                        $('#balance_to_operator_btn').removeClass('hide');
                    }

                    $(document).find('li.disabled a').off('click.tab_lock').on('click.tab_lock', function (e) {
                        e.preventDefault();
                        if ($(this).hasClass('excess_tab')) {
                            toastr.warning('Balance is positive. Please use Shortage tab.');
                        } else if ($(this).hasClass('shortage_tab')) {
                            toastr.warning('Balance is negative. Please use Excess tab.');
                        }
                        return false;
                    });

                    // NOTE: Do NOT unbind .excess_tab and .shortage_tab click handlers because:
                    // 1. It breaks Bootstrap's data-toggle="tab" functionality
                    // 2. Bootstrap uses jQuery's .on() internally for the tab toggle
                    // 3. The .off('click') removes ALL click handlers, including Bootstrap's

                    // Ensure custom tab click handler is attached (without removing Bootstrap's handler)
                    // Use document delegation with a custom namespace to avoid duplicate handlers
                    $(document).off('click.petro-settlement-tabs', '#settlement_form .settlement_tabs li a').on('click.petro-settlement-tabs', '#settlement_form .settlement_tabs li a', function (e) {
                        // Only handle if the tab is not disabled
                        if (!$(this).parents('li:first').hasClass('disabled')) {
                            setTimeout(() => {
                                $('#excess_amount').prop('disabled', false);
                            }, 500);
                        }
                    });

                }



                window.delete_payment = function(delete_amount) {
                    try {
                        console.log('delete_payment called with:', delete_amount);
                        delete_amount = parseFloat(delete_amount);

                        if (isNaN(delete_amount)) {
                            console.warn('delete_payment: delete_amount is NaN');
                            return;
                        }

                        var $ctx = $('.add_payment').length ? $('.add_payment') : $(document);
                        console.log('delete_payment context:', $ctx.attr('class') || 'document');

                        let total_balance = parseFloat(($ctx.find("#total_balance").val() || "0").toString().replace(/,/g, "")) || 0;
                        let total_paid = parseFloat($ctx.find("#total_paid").val()) || 0;
                        console.log('delete_payment current totals:', { total_balance, total_paid });

                        total_balance = total_balance + delete_amount;
                        total_paid = total_paid - delete_amount;
                        console.log('delete_payment new totals:', { total_balance, total_paid });

                        // Deleting a payment should always clear the balance lock
                        window.clearBalanceLock();

                        // Store raw numeric value in hidden input (preserves negative sign) and update balance section
                        $ctx.find("#total_balance").val(total_balance);
                        $ctx.find("#total_paid").val(total_paid);

                        $ctx.find(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
                        $ctx.find(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));

                        if (total_balance == 0) {
                            $ctx.find("#settlement_save_btn").removeClass("hide").show();
                        } else {
                            $ctx.find("#settlement_save_btn").addClass("hide").hide();
                        }

                        // Update Balance to Operator button based on balance: show whenever balance is non-zero
                        if (total_balance === 0) {
                            $ctx.find('#balance_to_operator_btn').addClass('hide').hide();
                        } else {
                            $ctx.find('#balance_to_operator_btn').removeClass('hide').show();
                        }

                        window.show_hide_excess_shortage_tab();

                        if (typeof calculateDenoms === 'function') {
                            calculateDenoms((0 - delete_amount));
                        }

                        if (typeof update_finalize_button_from_current_state === 'function') {
                            update_finalize_button_from_current_state();
                        }

                        if (typeof saveFormDataToSession === 'function') {
                            saveFormDataToSession();
                        }
                    } catch (e) {
                        console.error('Error in delete_payment:', e);
                    }
                };

                // Alias for internal calls
                var delete_payment = window.delete_payment;

                // Direct Settlement: removing a Credit Sale reduces both Total Amount (sales side) and Total Paid (accounts receivable)
                function delete_sale_amount(delete_amount) {
                    delete_amount = parseFloat(delete_amount);
                    if (isNaN(delete_amount)) return;

                    var $ctx = $('.add_payment').length ? $('.add_payment') : $(document);

                    let total_amount = parseFloat(($ctx.find("#total_amount").val() || "0").toString().replace(/,/g, "")) || 0;
                    let total_paid = parseFloat(($ctx.find("#total_paid").val() || "0").toString().replace(/,/g, "")) || 0;
                    let total_balance = parseFloat(($ctx.find("#total_balance").val() || "0").toString().replace(/,/g, "")) || 0;

                    // Manual sale deletion should also clear the balance lock
                    clearBalanceLock();

                    // Credit sales decrease both Total Amount and Total Paid
                    total_amount = total_amount - delete_amount;
                    total_paid = total_paid - delete_amount;
                    total_balance = total_amount - total_paid;

                    $ctx.find("#total_amount").val(total_amount);
                    $ctx.find("#total_paid").val(total_paid);
                    $ctx.find("#total_balance").val(total_balance);

                    $ctx.find(".total_amount").text(__number_f(total_amount, false, false, __currency_precision));
                    $ctx.find(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));
                    $ctx.find(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));

                    // Update tab visibility based on new balance
                    show_hide_excess_shortage_tab();

                    // Update Balance to Operator button based on balance: show whenever balance is non-zero
                    if (total_balance === 0) {
                        $ctx.find('#balance_to_operator_btn').addClass('hide').hide();
                    } else {
                        $ctx.find('#balance_to_operator_btn').removeClass('hide').show();
                    }

                    if (typeof update_finalize_button_from_current_state === 'function') {
                        update_finalize_button_from_current_state();
                    }

                    // Save to session storage
                    if (typeof saveFormDataToSession === 'function') {
                        saveFormDataToSession();
                    }
                }

                function update_finalize_button_from_current_state() {
                    const isDirectSettlement = ($('#is_direct_settlement').val() === '1');

                    function set_settlement_finalize_visible(shouldShow) {
                        if (shouldShow) {
                            $("#settlement_save_btn").removeClass("hide").show().css("display", "inline-block");
                        } else {
                            $("#settlement_save_btn").addClass("hide").hide();
                        }
                    }

                    // Direct Settlement: allow finalize regardless of sales-vs-paid balance (credit sales create A/R).
                    if (isDirectSettlement) {
                        set_settlement_finalize_visible(true);
                        return;
                    }

                    let total_balance = parseFloat((($("#total_balance").val() || "0") + "").replace(/,/g, ""));
                    if (isNaN(total_balance)) total_balance = 0;

                    let finalize_balance = total_balance;

                    // For Direct Settlement, finalize should be based on non-credit (cash) reconciliation:
                    // (Total Amount - Credit Sales) - Total Paid  == 0
                    if (isDirectSettlement) {
                        let total_amount = parseFloat((($("#total_amount").val() || "0") + "").replace(/,/g, ""));
                        let total_paid = parseFloat((($("#total_paid").val() || "0") + "").replace(/,/g, ""));
                        let credit_sales_net = parseFloat((($("#credit_sale_total").val() || "0") + "").replace(/,/g, ""));

                        if (isNaN(total_amount)) total_amount = 0;
                        if (isNaN(total_paid)) total_paid = 0;
                        if (isNaN(credit_sales_net)) credit_sales_net = 0;

                        finalize_balance = (total_amount - credit_sales_net) - total_paid;
                    }

                    finalize_balance = parseFloat(finalize_balance.toFixed(__currency_precision));

                    let shouldShow = (finalize_balance == 0);

                    if ($('#enable_cash_denoms').is(':checked')) {
                        let denom_bal = parseFloat((($(".denom_bal").val() || "0") + "").replace(/,/g, ""));
                        if (isNaN(denom_bal)) denom_bal = 0;
                        shouldShow = shouldShow && (denom_bal == 0);
                    }

                    set_settlement_finalize_visible(shouldShow);
                }

                $(function () {
                    if (typeof update_finalize_button_from_current_state === 'function') {
                        update_finalize_button_from_current_state();
                    }
                });

                function calculateTotal(table_name, class_name_td, output_element) {
                    window.calculateTotal = calculateTotal;

                    let total = 0.0;

                    $(table_name + " tbody")

                        .find(class_name_td)

                        .each(function () {

                            total += parseFloat(__number_uf($(this).text()));

                        });

                    $(output_element).text(__number_f(total, false, false, __currency_precision));

                }



                function myFloatNumber(i) {

                    var value = Math.floor(i * 100) / 100;

                    return value;

                }

                $(document).on("change", "#expense_category", function () {

                    /*
                     * IS1982 #1: the route is registered as
                     *     get-expense-account-category-id/{category_id}
                     * with a REQUIRED parameter. Resetting the form after an expense is added
                     * fires this change event while the dropdown still reads "Please Select",
                     * so the URL ended in a bare slash, matched no route, and Laravel returned
                     * "The route get-expense-account-category-id could not be found".
                     */
                    var categoryId = $(this).val();

                    if (categoryId === null || categoryId === undefined || categoryId === "") {
                        $("#expense_account").empty();
                        return;
                    }

                    $.ajax({

                        method: "get",

                        url: "/get-expense-account-category-id/" + encodeURIComponent(categoryId),

                        data: {},

                        success: function (result) {

                            $("#expense_account").empty().append(`<option value="${result.expense_account_id}" selected>${result.name}</option>`);

                        },

                    });

                });



                //Check Active Tab

                $(document).on("click", "#add_payment", function () {

                    // Call immediately when modal opens
                    show_hide_excess_shortage_tab();

                    var myVar = setInterval(() => {

                        if ($('.add_payment').hasClass('in')) {

                            if ($("#cash_tab").hasClass("active")) {

                                $("#cash_amount").focus();

                            }

                        }

                        if ($("#cash_amount").is(":focus")) {

                            clearInterval(myVar);

                        }

                        show_hide_excess_shortage_tab();

                        calculateDenoms();

                    }, 1000);

                });

                $(document).on("click", ".tabs", function () {

                    var tab_id = $(this).attr("href");



                    if (tab_id == "#expense_tab" && ($("#expense_tab").hasClass("active")) && ($(".total_balance").val()) <= 0) {

                        $('#expense_category').focus();

                        $('.excess_amount').prop('disabled', false);

                    } else if (tab_id == "#credit_sales_tab" && ($("#credit_sales_tab").hasClass("active"))) {

                        $('#order_number').focus();

                    } else {

                        $(tab_id + ' :input:enabled:visible:first').focus();

                        $('.excess_amount').prop('disabled', true);

                    }

                });







                $('#show_bulk_tank').on('ifChecked', function (event) {

                    $('.store_field').addClass('hide');

                    $('.bulk_tank_field').removeClass('hide');

                });



                $('#show_bulk_tank').on('ifUnchecked', function (event) {

                    $('.store_field').removeClass('hide');

                    $('.bulk_tank_field').addClass('hide');

                });

                var total_balance = "{{$total_balance}}";

                var total_excess = "{{$total_excess}}";

                $('#excess_amount').prop('disabled', false);

                $(document).find('li.disabled a').off('click');
                $(".shortage_tab").parents("li:first").removeClass("disabled");
                $(".excess_tab").parents("li:first").removeClass("disabled");

                if (total_balance < 0) {
                    // When balance is negative (excess) - show excess tab
                    $(".excess_tab").click();
                } else if (total_balance > 0) {
                    // When balance is positive (shortage) - show shortage tab
                    $(".shortage_tab").click();
                }
                // If balance is zero, don't auto-switch tabs





                // if (total_balance === 0) {

                //     $("#settlement_save_btn").removeClass("hide");

                // } else {

                //     $("#settlement_save_btn").addClass("hide");

                // }

                // if(total_excess >0){

                //   $('#shortage_add').prop('disabled', true);

                //   $('#shortage_amount').prop('disabled', true);

                // }

                $('#shortage_amount').on('input', function () {

                    if ($(this).val().length) {

                        //$('#excess_amount').prop('disabled', true);

                        $('.excess_amount_err').removeClass('hidden');

                    } else {

                        //$('#excess_amount').prop('disabled', false);

                        $('.excess_amount_err').addClass('hidden');



                    }

                });



                $('#excess_amount').on('input', function () {

                    if ($(this).val().length) {

                        $('#shortage_amount').prop('disabled', true);

                        $('.shortage_amount_err').removeClass('hidden');



                    } else {

                        $('#shortage_amount').prop('disabled', false);

                        $('.shortage_amount_err').addClass('hidden');

                    }



                });

                $(document).find('li.disabled a').on('click', function (e) { e.preventDefault(); return false; });

                $(document).find('#settlement_form .settlement_tabs li a').on('click', function (e) {

                    setTimeout(() => {

                        $('#excess_amount').prop('disabled', false);

                    }, 500);

                });

                $('#settlement_form .settlement_tabs li').removeClass('active');
                $('#settlement_form .settlement_tabs .tab-pane').removeClass('active in');
                $('#settlement_form .settlement_tabs li:first').addClass('active');
                $('#settlement_form #cash_tab').addClass('active in');

                // Ensure only one tab pane is visible in the settlement modal (fixes Excess tab showing Credit Sales content)
                
        (function() {
            var __currency_precision = {{ $currency_precision }};
            var __quantity_precision = {{ $quantity_precision }};

            // Disallow comma in amount fields and warn user
            var amount_selectors = '.cust_input_number, .input_number, #cash_amount, #card_amount, #cheque_amount, #shortage_amount, #excess_amount, #credit_total_amount, #credit_discount_amount, #loan_payments_amount, #drawing_payments_amount, #customer_loans_amount, #expense_amount, #cash_deposit_amount, #unit_price, #unit_discount';
            var quantity_selectors = '#credit_sale_qty';

            $(document).on('input', amount_selectors + ', ' + quantity_selectors, function() {
                var val = $(this).val();
                if (val.includes(',')) {
                    toastr.error('Please enter the value without commas.');
                    $(this).val(val.replace(/,/g, ''));
                }
            });

            $(document).on('change', amount_selectors, function() {
                var val = $(this).val();
                if (val !== '') {
                    var parts = val.split('.');
                    if (parts[1] && parts[1].length > __currency_precision) {
                        parts[1] = parts[1].substring(0, __currency_precision);
                        $(this).val(parts.join('.'));
                    }
                }
            });

            $(document).on('change', quantity_selectors, function() {
                var val = $(this).val();
                if (val !== '') {
                    var parts = val.split('.');
                    if (parts[1] && parts[1].length > __quantity_precision) {
                        parts[1] = parts[1].substring(0, __quantity_precision);
                        $(this).val(parts.join('.'));
                    }
                }
            });
        })();

            })();
        </script>

{{--
|--------------------------------------------------------------------------
| IS1409 Direct Settlement Payment Tab Isolation Fix
|--------------------------------------------------------------------------
| Keeps each payment method tab isolated. This prevents Cash/Card/Cheque/etc.
| sections from appearing inside the wrong payment tab after modal reloads,
| edit reloads, or repeated DataTable/AJAX rendering.
--}}
<style>
    #add_payment, #add_payment_modal, .settlement_modal {
        overflow: visible;
    }

    #settlement_form .settlement_tabs .tab-content > .tab-pane,
    .settlement_tabs .tab-content > .tab-pane {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    #settlement_form .settlement_tabs .tab-content > .tab-pane.active,
    #settlement_form .settlement_tabs .tab-content > .tab-pane.in,
    .settlement_tabs .tab-content > .tab-pane.active,
    .settlement_tabs .tab-content > .tab-pane.in {
        display: block !important;
        visibility: visible !important;
        height: auto !important;
        overflow: visible !important;
    }

    #settlement_form .settlement_tabs .nav-tabs > li.active > a,
    .settlement_tabs .nav-tabs > li.active > a {
        font-weight: 700;
    }
</style>
<script>
(function () {
    function isolateSettlementPaymentTabs(scope) {
        var $scope = scope ? $(scope) : $(document);
        var $tabsWrap = $scope.find('.settlement_tabs').first();

        if (!$tabsWrap.length) {
            $tabsWrap = $('.settlement_tabs').first();
        }

        if (!$tabsWrap.length) {
            return;
        }

        var $navLinks = $tabsWrap.find('> ul.nav-tabs a[data-toggle="tab"]');
        var $panes = $tabsWrap.find('> .tab-content > .tab-pane');

        if (!$navLinks.length || !$panes.length) {
            return;
        }

        var $activeLink = $navLinks.filter(function () {
            return $(this).closest('li').hasClass('active');
        }).first();

        if (!$activeLink.length) {
            $activeLink = $navLinks.first();
        }

        var target = $activeLink.attr('href');

        $navLinks.closest('li').removeClass('active');
        $activeLink.closest('li').addClass('active');

        $panes.removeClass('active in').hide();
        if (target && target.charAt(0) === '#') {
            $tabsWrap.find('> .tab-content > ' + target).addClass('active in').show();
        }
    }

    $(document).off('shown.bs.tab.is1409SettlementTabs').on('shown.bs.tab.is1409SettlementTabs', '.settlement_tabs > ul.nav-tabs a[data-toggle="tab"]', function () {
        var $tabsWrap = $(this).closest('.settlement_tabs');
        var target = $(this).attr('href');
        var $panes = $tabsWrap.find('> .tab-content > .tab-pane');

        $tabsWrap.find('> ul.nav-tabs > li').removeClass('active');
        $(this).closest('li').addClass('active');

        $panes.removeClass('active in').hide();
        if (target && target.charAt(0) === '#') {
            $tabsWrap.find('> .tab-content > ' + target).addClass('active in').show();
        }
    });

    $(document).off('click.is1409SettlementTabs').on('click.is1409SettlementTabs', '.settlement_tabs > ul.nav-tabs a[data-toggle="tab"]', function () {
        var el = this;
        setTimeout(function () {
            isolateSettlementPaymentTabs($(el).closest('.settlement_tabs'));
        }, 50);
    });

    $(document).ready(function () {
        isolateSettlementPaymentTabs(document);
        setTimeout(function () { isolateSettlementPaymentTabs(document); }, 300);
    });

    $(document).ajaxComplete(function () {
        setTimeout(function () { isolateSettlementPaymentTabs(document); }, 100);
    });
})();
</script>


<script>
/* S271 ZIP 056 fallback: visible container contains only selected payment tab content. */
$(document).ready(function () {
    if (typeof window.s271LoadOnlySelectedPaymentTab === 'function') {
        setTimeout(function () { window.s271LoadOnlySelectedPaymentTab(document, '#cash_tab'); }, 300);
    }
});
</script>
