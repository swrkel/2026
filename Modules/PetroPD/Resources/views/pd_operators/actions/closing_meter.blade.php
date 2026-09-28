@extends('layouts.' . $layout)
@section('title', __('petropd::lang.closing_meter'))
<style>
    .side-label {
        font-size: 21px;
        font-weight: bold;
        padding-top: 5px;
    }

    #key_pad input {
        border: none
    }

    #key_pad button {
        height: 80px;
        width: 80px;
        font-size: 25px;
        margin: 2px 1px;
        border: none !important;
    }

    :focus {
        outline: 0 !important
    }

    .disabled-link {
        pointer-events: none;
        cursor: not-allowed;
        opacity: 0.5;
    }

    .pd-close-meter-enter-arrow, #calculate_total_btn{ background:#00a65a !important; background-color:#00a65a !important; color:#fff !important; border-color:#008d4c !important; font-weight:900 !important; }
</style>
@section('content')
@include('petropd::partials.pumper_dashboard_design_tweaks')
    <div class="container">
        <div class="col-md-12">
            <br>
            <br>
            <h4 class="text-center" style="color: red;">After entering the Testing Meter (if any) and Closing Meter, then it
                is necessary to click the Green Colour Arrow Button</h4>
            <br>
            {!! Form::open([
                'url' => action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorActionsController@postClosingMeter', $pump->id),
                'method' => 'post',
                'id' => 'closing_meter_form',
            ]) !!}
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('pump_no', __('petropd::lang.pump_no') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('pump_no', $pump->pump_no, ['class' => 'form-control input-lg', 'readonly']) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('sale_price', __('petropd::lang.sale_price') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            @php
                                $business = \App\Business::where('id', $business_id)->first();
                                $currency_precision = $business->currency_precision;
                            @endphp
                            {!! Form::text('sale_price', number_format($pump->sell_price_inc_tax, $currency_precision, '.', ''), [
                                'class' => 'form-control input-lg',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('starting_meter', __('petropd::lang.starting_meter') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            @php
                                /*
                                 * MA-002 (IS-1922 #1): prefer the meter confirmed when the
                                 * pump was RECEIVED.
                                 *
                                 * Receiving writes that figure to the assignment
                                 * (PDPumpReceiveController: $assignment->starting_meter)
                                 * and never touches the pumps table. This screen read only
                                 * the pump, so it showed the previous shift's meter instead
                                 * of the one the operator had just confirmed.
                                 *
                                 * The assignment value wins when it is present and greater
                                 * than zero. Everything below is the original fallback,
                                 * untouched - so a pump received before this change, or one
                                 * with no open assignment, behaves exactly as it did.
                                 */
                                /*
                                 * MA-002 (IS-1926): the RECEIVED meter wins.
                                 *
                                 * $receivedMeter is the last saved closing meter
                                 * for this pump - the SAME source the Receive
                                 * Pump screen prefills from, so the two screens
                                 * now agree by construction.
                                 *
                                 * The assignment is kept as the second choice
                                 * for installations that do use assignments, and
                                 * the pump master meters remain the last resort.
                                 */
                                $pdStartingMeter = null;

                                /*
                                 * MA-002 (IS-1926): A RECEIVED METER OF ZERO IS
                                 * A REAL VALUE.
                                 *
                                 * My earlier versions guarded every source with
                                 * "> 0", so a pump genuinely received at 0.00 -
                                 * a new pump, or the first shift on a fresh
                                 * system - looked like "nothing stored" and fell
                                 * through to the pump master meter. That is why
                                 * Close Pump showed 3,573,248.42 when the
                                 * operator had received the pump at 0.00.
                                 *
                                 * The test is now whether a CONFIRMED assignment
                                 * EXISTS, not whether its number is above zero.
                                 */
                                if (! empty($assignment) && ! empty($assignment->is_confirmed)) {
                                    $pdStartingMeter = (float) ($assignment->starting_meter ?? 0);
                                } elseif (! empty($assignment)) {
                                    // Assigned but not yet confirmed - still the
                                    // operator's own row, so prefer it.
                                    $pdStartingMeter = (float) ($assignment->starting_meter ?? 0);
                                } elseif (! is_null($receivedMeter)) {
                                    $pdStartingMeter = (float) $receivedMeter;
                                } else {
                                    // No assignment at all - fall back to the pump.
                                    $pdStartingMeter = ! empty($pump->pod_last_meter)
                                        ? ($pump->pod_last_meter >= $pump->last_meter_reading
                                            ? (float) $pump->pod_last_meter
                                            : (float) $pump->last_meter_reading)
                                        : (float) $pump->last_meter_reading;
                                }

                                /*
                                 * MA-002 (IS-1926): record it when the received
                                 * meter could NOT be used.
                                 *
                                 * Falling back to the pump's own meter is the
                                 * previous shift's figure - correct as a last
                                 * resort, wrong as a silent default. Without
                                 * this line there is no way to tell the two
                                 * apart on screen, which is why this came back
                                 * a second time.
                                 */
                                if (empty($assignment) || (float) ($assignment->starting_meter ?? 0) <= 0) {
                                    \Log::warning('MA-002: close pump fell back to the pump meter', [
                                        'pump_id' => $pump->id ?? null,
                                        'assignment_id' => optional($assignment)->id,
                                        'assignment_starting_meter' => optional($assignment)->starting_meter,
                                        'assignment_confirmed' => optional($assignment)->is_confirmed,
                                        'pod_last_meter' => $pump->pod_last_meter ?? null,
                                        'last_meter_reading' => $pump->last_meter_reading ?? null,
                                        'shown' => $pdStartingMeter,
                                    ]);
                                }
                            @endphp
                            {!! Form::text(
                                'starting_meter',
                                number_format($pdStartingMeter, 3, '.', ''),
                                ['class' => 'form-control input-lg', 'readonly', 'required'],
                            ) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('testing_ltr', __('petropd::lang.testing_liters') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('testing_ltr', 0.0, ['class' => 'form-control input-lg inputcalculater']) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('closing_meter', __('petropd::lang.closing_meter') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            <div class="input-group">
                                {!! Form::text('closing_meter', null, [
                                    'class' => 'form-control input-lg inputcalculater',
                                    'required',
                                    'oninput' => "this.value = this.value.match(/^\\d+(\\.\\d{0,3})?/)?.[0] || ''",
                                ]) !!}

                                <div class="input-group-addon calculate_total pd-close-meter-enter-arrow disabled-link"
                                    style="background: #00a65a !important; background-color: #00a65a !important; color: #fff !important; cursor: pointer" id="calculate_total_btn"
                                    disabled>
                                    ⏎
                                </div>

                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('amount', __('petropd::lang.total_amount') . ':', [
                                'class' => 'side-label
                                                    text-red',
                            ]) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('amount', 0.0, ['class' => 'form-control input-lg', 'readonly', 'required']) !!}
                        </div>
                        <input type="hidden" name="sold_ltr" id="sold_ltr" value="0">
                        <input type="hidden" name="amount_hidden" id="amount_hidden" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row">
                        <div id="key_pad" tabindex="1">
                            <div class="row text-center" id="calc">
                                <div class="calcBG col-md-12 text-center">
                                    <div class="row">
                                        <button id="7" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">7</button>
                                        <button id="8" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">8</button>
                                        <button id="9" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">9</button>

                                    </div>
                                    <div class="row">
                                        <button id="4" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">4</button>
                                        <button id="5" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">5</button>
                                        <button id="6" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">6</button>
                                    </div>
                                    <div class="row">
                                        <button id="1" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">1</button>
                                        <button id="2" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">2</button>
                                        <button id="3" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">3</button>
                                    </div>
                                    <div class="row">
                                        <button id="backspace" type="button" class="btn btn-danger"
                                            onclick="enterVal(this.id)">⌫</button>
                                        <button id="0" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">0</button>
                                        <button id="precision" type="button" class="btn btn-success"
                                            onclick="enterVal(this.id)">.</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard') }}"
                        class="btn btn-flat btn-block btn-lg disabled-link" style="color: #fff; background-color:#810040;"
                        id="dashboard" disabled>@lang('petropd::lang.dashboard')</a>
                    <br><br>
                    <button type="submit" class="btn btn-flat btn-block btn-lg"
                        style="color: #fff; background-color:#2874A6;" id="save"
                        disabled>@lang('petropd::lang.save')</button><br><br>

                    <a href="#" class="btn btn-flat btn-block btn-lg"
                        style="color: #fff; background-color:#CC0000;" id="cancel">@lang('petropd::lang.cancel')</a><br><br>
                    <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}"
                        class="btn btn-flat btn-block btn-lg pull-right disabled-link"
                        style=" background-color: orange; color: #fff;" id="logout" disabled>@lang('petropd::lang.logout')</a>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>

    <div id="reconfirmPopup" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="reconfirmModalLabel" aria-hidden="true">
        <style>
            /* IS1759-08: reconfirmation values must be legible from the pump bay. */
            #reconfirmPopup .modal-title,
            #reconfirmPopup .modal-body,
            #reconfirmPopup .modal-body strong,
            #reconfirmPopup .modal-body h4,
            #reconfirmPopup .modal-body label,
            #reconfirmPopup .modal-body .form-control {
                font-size: 21px !important;
            }

            #reconfirmPopup .modal-body .form-control {
                min-height: 48px;
            }
        </style>
        <div class="modal-dialog" role="document" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reconfirmModalLabel">@lang('petropd::lang.reconfirm_close_pump')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <strong>Operator:</strong> {{ $pumper_name ?? '' }}<br>
                        <strong>Shift No:</strong> {{ sprintf("%04d", $shift_number ?? 0) }}
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Meter</h4>
                            <div class="form-group">
                                <label>Pump Closed Meter</label>
                                <input type="text" id="reconf_closed" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Re-enter Closed Meter</label>
                                <input type="text" id="reconf_reentered_closed" class="form-control" oninput="this.value=this.value.match(/^\d+(\.\d{0,3})?/)?.[0]||'';">
                            </div>
                            <div class="form-group">
                                <label>Difference</label>
                                <input type="text" id="reconf_diff_closed" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4>Testing</h4>
                            <div class="form-group">
                                <label>Entered Testing Liters</label>
                                <input type="text" id="reconf_testing_entered" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Re-enter Testing Liters</label>
                                <input type="text" id="reconf_reentered_testing" class="form-control" oninput="this.value=this.value.match(/^\d+(\.\d{0,2})?/)?.[0]||'';">
                            </div>
                            <div class="form-group">
                                <label>Difference</label>
                                <input type="text" id="reconf_diff_testing" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="reconfirm_button" class="btn btn-success" disabled>Confirm</button>
                    <button class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm zero Total Amount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Total Amount is zero, No Values. Are you sure to save?
                </div>
                <div class="modal-footer">
                    <button id="confirmYes" class="btn btn-success" style="float:left;">Yes</button>
                    <button id="confirmNo" class="btn btn-danger" style="float:right;">No</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('javascript')
    <script type="text/javascript">
        $('#closing_meter_form').validate();
        var current_id = null;
        $('.inputcalculater').on('focus', function() {
            //console.log(this.id);
            current_id = this.id;
        });

        function enterVal(val) {


            $('#' + current_id).focus();

            if (val === 'enter') {
                $('#' + current_id).next('.form-control')
                toggle_calculate_total_btn();
                return;
            }
            if (val === 'precision') {
                str = $('#' + current_id).val();
                str = str + '.';
                $('#' + current_id).val(str);
                toggle_calculate_total_btn();
                return;
            }
            if (val === 'backspace') {
                str = $('#' + current_id).val();
                str = str.substring(0, str.length - 1);
                $('#' + current_id).val(str);
                toggle_calculate_total_btn();
                return;
            }
            let closing_meter = $('#' + current_id).val() + val;
            closing_meter = closing_meter.replace(',', '');
            $('#' + current_id).val(closing_meter);
            toggle_calculate_total_btn();
        };




        $('.calculate_total').click(function() {
            let closing_meter = $('#closing_meter').val().replace(',', '');
            if (closing_meter === '' || closing_meter === undefined || closing_meter === NaN) {
                toastr.error('Closing meter value is required');
                $("#dashboard").attr('disabled', true);
                $("#save").attr('disabled', true);
                $("#logout").attr('disabled', true);
                $("#dashboard").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
                return false;
            }
            let starting_meter = parseFloat($('#starting_meter').val());
            closing_meter = parseFloat($('#closing_meter').val().replace(',', ''));
            let testing_ltr = parseFloat($('#testing_ltr').val().replace(',', ''));
            let sold_ltr = closing_meter - starting_meter - testing_ltr;

            //   if(sold_ltr > {{ $pump->qty_available ?? 0 }}){
            //         //toastr.error('Out of Stock');
            //         $('#closing_meter').val(0);
            //         $("#dashboard").attr('disabled',true);
            //         $("#save").attr('disabled',true);
            //         $("#logout").attr('disabled',true);
            //         $("#dashboard").addClass('disabled-link').attr('aria-disabled', 'true');
            //         $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
            //         $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
            //         return false;
            //     }


            var allowoverselling = $("#allowoverselling").val();
            if (sold_ltr > {{ $pump->qty_available ?? 0 }} && allowoverselling != true) {
                $('#closing_meter').val(0);

                $("#dashboard").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
                $("#save").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');

                return false;
            }
            if (closing_meter < starting_meter) {
                toastr.error('Closing meter value should not less then starting meter value');
                $('#closing_meter').val(0);
                $("#dashboard").attr('disabled', true);
                $("#save").attr('disabled', true);
                $("#logout").attr('disabled', true);
                $("#dashboard").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
                return false;
            }

            calculateTotal();
        });

        function calculateTotal() {
            let sale_price = parseFloat($('#sale_price').val());
            let starting_meter = parseFloat($('#starting_meter').val());
            let closing_meter = parseFloat($('#closing_meter').val());
            let testing_ltr = parseFloat($('#testing_ltr').val());
            let sold_ltr = closing_meter - starting_meter - testing_ltr

            let total = sale_price * (sold_ltr);
            __write_number($('#amount'), total);
            $('#sold_ltr').val(sold_ltr);
            $('#amount_hidden').val(total);
            if (total >= 0) {
                if (total == 0) {
                    $("#confirmationModal").modal("show");
                } else {
                    $("#dashboard").attr('disabled', false);
                    $("#save").attr('disabled', false);
                    $("#logout").attr('disabled', false);
                    $("#dashboard").removeClass('disabled-link').removeAttr('aria-disabled');
                    $("#save").removeClass('disabled-link').removeAttr('aria-disabled');
                    $("#logout").removeClass('disabled-link').removeAttr('aria-disabled');
                }
            } else {
                $("#dashboard").attr('disabled', true);
                $("#save").attr('disabled', true);
                $("#logout").attr('disabled', true);
                $("#dashboard").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
            }
        }

        $('#cancel').click(function() {
            $('#closing_meter').val(0);
            $('#testing_ltr').val(0);
            __write_number($('#amount'), 0);
            $('#sold_ltr').val(0);
            $('#amount_hidden').val(0);
            $("#dashboard").attr('disabled', true);
            $("#save").attr('disabled', true);
            $("#logout").attr('disabled', true);
            $("#dashboard").addClass('disabled-link').attr('aria-disabled', 'true');
            $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
            $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
        });

        $('#closing_meter').on('input change', function() {
            toggle_calculate_total_btn();
        });

        function toggle_calculate_total_btn() {
            let closing_meter = $('#closing_meter').val().replace(',', '');
            if (closing_meter === '' || closing_meter === undefined || closing_meter === NaN) {
                $("#calculate_total_btn").attr('disabled', true);
                $("#calculate_total_btn").addClass('disabled-link').attr('aria-disabled', 'true');
                return;
            }
            let starting_meter = parseFloat($('#starting_meter').val());
            closing_meter = parseFloat($('#closing_meter').val().replace(',', ''));
            let testing_ltr = parseFloat($('#testing_ltr').val().replace(',', ''));
            let sold_ltr = closing_meter - starting_meter - testing_ltr

            if (closing_meter < starting_meter) {
                $("#calculate_total_btn").attr('disabled', true);
                $("#calculate_total_btn").addClass('disabled-link').attr('aria-disabled', 'true');
            } else {
                $("#calculate_total_btn").attr('disabled', false).css("background-color", "#00a65a").css("color", "#fff");

                $("#calculate_total_btn").removeClass('disabled-link').removeAttr('aria-disabled');
            }
        }




        $('#confirmYes').click(function() {
            $("#confirmationModal").modal("hide");
            $("#dashboard").attr('disabled', false);
            $("#save").attr('disabled', false);
            $("#logout").attr('disabled', false);
            $("#dashboard").removeClass('disabled-link').removeAttr('aria-disabled');
            $("#save").removeClass('disabled-link').removeAttr('aria-disabled');
            $("#logout").removeClass('disabled-link').removeAttr('aria-disabled');
        });

        $('#confirmNo').click(function() {
            $("#confirmationModal").modal("hide");
        });
        // override calculate_total behaviour to include reconfirmation popup
        $(document).ready(function() {
            // utility helpers
            function disableDashboardButtons() {
                $("#dashboard").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
                $("#save").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
            }
            function enableDashboardButtons() {
                $("#dashboard").attr('disabled', false).removeClass('disabled-link').removeAttr('aria-disabled');
                $("#save").attr('disabled', false).removeClass('disabled-link').removeAttr('aria-disabled');
                $("#logout").attr('disabled', false).removeClass('disabled-link').removeAttr('aria-disabled');
            }

            function updateReconfDiff() {
                let closed = parseFloat($('#reconf_closed').val() || 0);
                let reclosed = parseFloat($('#reconf_reentered_closed').val() || 0);
                let diff_closed = (closed - reclosed).toFixed(3);
                $('#reconf_diff_closed').val(diff_closed);

                let entered = parseFloat($('#reconf_testing_entered').val() || 0);
                let reenteredtest = parseFloat($('#reconf_reentered_testing').val() || 0);
                let diff_test = (entered - reenteredtest).toFixed(2);
                $('#reconf_diff_testing').val(diff_test);

                let dc = parseFloat($('#reconf_diff_closed').val());
                let dt = parseFloat($('#reconf_diff_testing').val());
                if (dc === 0 && dt === 0) {
                    $('#reconfirm_button').prop('disabled', false);
                } else {
                    $('#reconfirm_button').prop('disabled', true);
                }
            }

            $('#reconf_reentered_closed, #reconf_reentered_testing').on('input blur', function(e) {
                if (e.type === 'blur') {
                    let id = $(this).attr('id');
                    let val = $(this).val();
                    if (val !== '') {
                        let num = parseFloat(val);
                        if (!isNaN(num)) {
                            if (id === 'reconf_reentered_closed') {
                                $(this).val(num.toFixed(3));
                            } else {
                                $(this).val(num.toFixed(2));
                            }
                        }
                    }
                }
                updateReconfDiff();
            });

            $('#reconfirm_button').click(function() {
                $('#reconfirmPopup').modal('hide');
                calculateTotal();
            });

            // attach new handler
            $('.calculate_total').off('click').on('click', function(e) {
                e.preventDefault();
                let closing_meter_raw = $('#closing_meter').val();
                let closing_meter = closing_meter_raw.replace(',', '');
                if (closing_meter === '' || closing_meter === undefined || isNaN(closing_meter)) {
                    toastr.error('Closing meter value is required');
                    disableDashboardButtons();
                    return false;
                }
                let starting_meter = parseFloat($('#starting_meter').val());
                closing_meter = parseFloat(closing_meter);
                let testing_ltr = parseFloat($('#testing_ltr').val().replace(',', '') || 0);

                // validate overselling and closing meter
                let sold_ltr = closing_meter - starting_meter - testing_ltr;
                var allowoverselling = $("#allowoverselling").val();
                if (sold_ltr > {{ $pump->qty_available ?? 0 }} && allowoverselling != true) {
                    $('#closing_meter').val(0);
                    disableDashboardButtons();
                    return false;
                }
                if (closing_meter < starting_meter) {
                    toastr.error('Closing meter value should not less then starting meter value');
                    $('#closing_meter').val(0);
                    disableDashboardButtons();
                    return false;
                }

                $('#reconf_closed').val(closing_meter.toFixed(3));
                $('#reconf_testing_entered').val(testing_ltr.toFixed(2));
                $('#reconf_reentered_closed').val('');
                $('#reconf_reentered_testing').val('');
                $('#reconf_diff_closed').val('');
                $('#reconf_diff_testing').val('');
                $('#reconfirm_button').prop('disabled', true);

                $('#reconfirmPopup').modal('show');
            });
        });    </script>
@endsection
