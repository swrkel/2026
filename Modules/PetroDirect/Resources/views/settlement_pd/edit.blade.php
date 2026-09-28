@extends('layouts.app')
@section('title', __('petrodirect::lang.settlement_pd'))

@section('content')
    @php
        $business_id = session()->get('user.business_id');
        $business_details = App\Business::find($business_id);
        $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
        $meeter_precision = 3;
    @endphp

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('petrodirect::lang.settlement_pd', ['contacts' => __('petrodirect::lang.settlement_pd')])</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('petrodirect::lang.settlement_pd')</a></li>
                        <li><span>@lang('petrodirect::lang.settlement_pd', ['contacts' => __('petrodirect::lang.settlement_pd')])</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content main-content-inner">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('settlement_no', __('petrodirect::lang.settlement_no') . ':') !!}
                                {!! Form::text(
                                    'settlement_no',
                                    !empty($active_settlement) ? $active_settlement->settlement_no : $settlement_no,
                                    ['class' => 'form-control', 'readonly'],
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select(
                                    'location_id',
                                    $business_locations,
                                    !empty($active_settlement)
                                        ? $active_settlement->location_id
                                        : (!empty($default_location)
                                            ? $default_location
                                            : null),
                                    [
                                        'class' => 'form-control select2',
                                        'id' => 'location_id',
                                        'placeholder' => __('petrodirect::lang.all'),
                                        'style' => 'width:100%',
                                    ],
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('shift_number', __('petrodirect::lang.shift_number') . ':') !!}
                                <select name="shift_number[]" id="shift_number" class="form-control select2" multiple>
                                    @foreach($shift_numbers as $shift_id_key => $shift_data)
                                        @php
                                            $shift_number_display = is_array($shift_data) ? $shift_data['shift_number'] : $shift_data;
                                            $work_shift_id_attr = is_array($shift_data) && isset($shift_data['work_shift_id']) ? $shift_data['work_shift_id'] : '';
                                            $pump_operator_id_attr = is_array($shift_data) && isset($shift_data['pump_operator_id']) ? $shift_data['pump_operator_id'] : '';
                                            $direct_shift_attr = is_array($shift_data) && !empty($shift_data['is_direct_shift']) ? 'data-direct-shift=1' : '';
                                            $selected = isset($shift_id) && (string) $shift_id === (string) $shift_id_key ? 'selected' : '';
                                        @endphp
                                        <option value="{{ $shift_id_key }}" data-work-shift="{{ $work_shift_id_attr }}" data-pump-operator="{{ $pump_operator_id_attr }}" {!! $direct_shift_attr !!} {{ $selected }}>
                                            {{ $shift_number_display }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('pump_operator', __('petrodirect::lang.pump_operator') . ':') !!}
                                {!! Form::select('pump_operator_id', $pump_operators, !empty($active_settlement) ? $active_settlement->pump_operator_id : (!empty($pump_operator_id) ? $pump_operator_id : null), [
                                'class' => 'form-control select2',
                                'id' => 'pump_operator_id',
                                'placeholder' => __('petrodirect::lang.please_select'),
                                ]) !!}
                            </div>

                        </div>

                    </div>

                    <input type="hidden" id="is_edit" value="1">
                    <input type="hidden" id="shift_id" value="{{ $shift_id ?? '' }}">
                    <input type="hidden" id="no_change" value="{{ request()->no_change }}">

                    <div class="row">

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('work_shift', __('petrodirect::lang.work_shift') . ':') !!}
                                {!! Form::select(
                                    'work_shift[]',
                                    $work_shifts,
                                    !empty($active_settlement) ? $active_settlement->work_shift : null,
                                    ['class' => 'form-control select2', 'id' => 'work_shift', 'multiple'],
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('transaction_date', __('petrodirect::lang.transaction_date') . ':*') !!}
                                {!! Form::text('transaction_date', null, [
                                    'class' => 'form-control transaction_date',
                                    'required',
                                    'placeholder' => __('petrodirect::lang.transaction_date'),
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('note', __('petrodirect::lang.note') . ':') !!}
                                {!! Form::text('note', !empty($active_settlement) ? $active_settlement->note : null, [
                                    'class' => 'form-control note',
                                    'placeholder' => __('petrodirect::lang.note'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent

            </div>

            @component('components.widget', ['class' => 'box-primary below_box', 'id' => 'below_box_pd'])
                <div class="row">
                    <div class="col-md-12">
                        <div class="settlement_tabs">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a href="#meter_sale_tab" class="meter_sale_tab" data-toggle="tab">
                                        <i class="fa fa-tachometer"></i> <strong>@lang('petrodirect::lang.meter_sale')</strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#other_sale_tab" class="other_sale_tab" style="" data-toggle="tab">
                                        <i class="fa fa-balance-scale"></i> <strong>
                                            @lang('petrodirect::lang.other_sale') </strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#other_income_tab" class="other_income_tab" style="" data-toggle="tab">
                                        <i class="fa fa-thermometer"></i> <strong>
                                            @lang('petrodirect::lang.other_income') </strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#customer_payment_tab" class="customer_payment_tab" style=""
                                        data-toggle="tab">
                                        <i class="fa fa-money"></i> <strong>
                                            @lang('petrodirect::lang.customer_payment') </strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#payment_tab" class="payment_tab" style="" data-toggle="tab">
                                        <i class="fa fa-book"></i> <strong>
                                            @lang('petrodirect::lang.payment') </strong>
                                    </a>
                                </li>

                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="meter_sale_tab">
                                    @include('petrodirect::settlement_pd.partials.meter_sale', ['edit' => 1])
                                </div>

                                <div class="tab-pane" id="other_sale_tab">
                                    @include('petrodirect::settlement_pd.partials.other_sale')
                                </div>

                                <div class="tab-pane" id="other_income_tab">
                                    @include('petrodirect::settlement_pd.partials.other_income')
                                </div>

                                <div class="tab-pane" id="customer_payment_tab">
                                    @include('petrodirect::settlement_pd.partials.customer_payment')
                                </div>

                                <div class="tab-pane" id="payment_tab">
                                    @include('petrodirect::settlement_pd.partials.payment')
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent

            <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
            </div>
            <div class="modal fade add_payment" role="dialog" aria-labelledby="gridSystemModalLabel"
                style="overflow-y: auto;">
            </div>
            <div class="modal fade preview_settlement" role="dialog" aria-labelledby="gridSystemModalLabel">
            </div>
            <div id="settlement_print"></div>

    </section>
    <!-- /.content -->

    <div class="modal fade edit_disabled" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">@lang('petrodirect::lang.edit_disabled')</h4>
                </div>

                <!-- Modal Body -->
                <div class="modal-body" style="padding: 50px">
                    <p class="text-bold">@lang('petrodirect::lang.edit_disabled_exp')</p>
                    <p class="text-center">{!! isset($can_edit_details[1]) ? $can_edit_details[1] : '' !!}</p>
                </div>

            </div>
        </div>
    </div>


    @include('petrodirect::partials.global_tab_standard')
@endsection
@section('javascript')
    <script src="{{ url('js/app.js?v=34') }}"></script>
    <script src="{{ url('js/payment.js?v=22') }}"></script>
<script>
$(document).ready(function () {
    $('#meter_sale_table').DataTable({
        paging: false,       // no pagination (optional)
        searching: false,    // no search box (optional)
        info: false,         // hide "Showing x of y" (optional)
        ordering: true,      // ENABLE sorting
        order: [],           // no default order
        columnDefs: [
            { orderable: false, targets: [13] } // Disable sorting on Action column
        ]
    });
    $('#other_sale_table').DataTable({
        paging: false,       // no pagination (optional)
        searching: false,    // no search box (optional)
        info: false,         // hide "Showing x of y" (optional)
        ordering: true,      // ENABLE sorting
        order: [],           // no default order
        columnDefs: [
            { orderable: false, targets: [9] } // Disable sorting on Action column
        ]
    });
    $('#other_income_table').DataTable({
        paging: false,       // no pagination (optional)
        searching: false,    // no search box (optional)
        info: false,         // hide "Showing x of y" (optional)
        ordering: true,      // ENABLE sorting
        order: [],           // no default order
        columnDefs: [
            { orderable: false, targets: [4] } // Disable sorting on Action column
        ]
    });
       $('#customer_payment_table').DataTable({
        paging: false,       // no pagination (optional)
        searching: false,    // no search box (optional)
        info: false,         // hide "Showing x of y" (optional)
        ordering: true,      // ENABLE sorting
        order: [],           // no default order
        columnDefs: [
            { orderable: false, targets: [8] } // Disable sorting on Action column
        ]
    });
});
</script>
    <!-- <script src="https://220.kiria.online/Modules/PetroDirect/Resources/assets/js/app.js?v=29"></script>
        <script src="https://220.kiria.online/Modules/PetroDirect/Resources/assets/js/payment.js?v=22"></script> -->
    <input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">
    <input type="hidden" id="shift_closed" value="{{ !empty($shift_closed) ? $shift_closed : 'yes' }}">
    <input type="hidden" id="shift_id" value="{{ $shift_id ?? '' }}">

    <script>
        const $shiftId = $('#shift_number');
        const $activeSettlement = $('#active_settlement_id');

        // ---------- Helper functions (needed by inline scripts) ----------
        function toastError(msg = 'Something went wrong') {
            toastr.error(msg);
        }

        function toastSuccess(msg) {
            toastr.success(msg);
        }

        function apiGet(url, data = {}) {
            return $.ajax({
                method: 'GET',
                url,
                data
            });
        }

        function apiPut(url, data = {}) {
            return $.ajax({
                method: 'PUT',
                url,
                data
            });
        }

        function updatePumpDropdown(pump_nos = {}) {
            const $select = $('#pump_no_pd');
            $select.empty().append('<option value="">' + "{{ __('petrodirect::lang.please_select') }}" + '</option>');
            $.each(pump_nos, (id, name) => $select.append(`<option value="${id}">${name}</option>`));
            $select.trigger('change.select2');
        }

        function normalizeShiftIds(value) {
            if (value == null || value === '') {
                return [];
            }

            return Array.isArray(value) ? value : [value];
        }

        const directShiftByOperator = {};

        function getSelectedDirectShiftNumber() {
            const selectedShiftIds = normalizeShiftIds($shiftId.val());
            if (!selectedShiftIds.length) return '';

            const $option = $('#shift_number').find(`option[value="${selectedShiftIds[0]}"]`);
            return $option.data('direct-shift') ? $.trim($option.text()) : '';
        }

        function getSelectedDirectShiftOperatorId() {
            const selectedShiftIds = normalizeShiftIds($shiftId.val());
            if (!selectedShiftIds.length) return '';

            const $option = $('#shift_number').find(`option[value="${selectedShiftIds[0]}"]`);
            return $option.data('direct-shift') ? ($option.data('pump-operator') || '') : '';
        }

        function rememberSelectedDirectShift(operatorId) {
            const directShiftNumber = getSelectedDirectShiftNumber();
            if (operatorId && directShiftNumber) {
                directShiftByOperator[operatorId] = directShiftNumber;
            }
        }

        function getDirectShiftNumberValue(shiftNumber) {
            const match = (shiftNumber || '').toString().match(/(\d+)$/);
            return match ? parseInt(match[1], 10) : 0;
        }

        function getHighestKnownDirectShiftNumber() {
            const labels = Object.values(directShiftByOperator).filter(Boolean);
            const selectedLabel = getSelectedDirectShiftNumber();
            if (selectedLabel) {
                labels.push(selectedLabel);
            }

            return labels.reduce((highest, label) => {
                return getDirectShiftNumberValue(label) > getDirectShiftNumberValue(highest) ? label : highest;
            }, '');
        }

        function getDirectShiftPayload(operatorId) {
            if (operatorId && directShiftByOperator[operatorId]) {
                return {
                    number: directShiftByOperator[operatorId],
                    operatorId: operatorId
                };
            }

            return {
                number: getHighestKnownDirectShiftNumber(),
                operatorId: getSelectedDirectShiftOperatorId()
            };
        }

        function getSelectedShiftMeta() {
            const selectedShiftIds = normalizeShiftIds($shiftId.val());
            const firstShiftId = selectedShiftIds.length ? selectedShiftIds[0] : null;

            if (!firstShiftId) {
                return {
                    selectedShiftIds,
                    firstShiftId: null,
                    selectedOption: $(),
                    workShiftId: null,
                    pumpOperatorId: null
                };
            }

            const $selectedOption = $('#shift_number').find(`option[value="${firstShiftId}"]`);

            return {
                selectedShiftIds,
                firstShiftId,
                selectedOption: $selectedOption,
                workShiftId: $selectedOption.data('work-shift') || null,
                pumpOperatorId: $selectedOption.data('pump-operator') || null
            };
        }

        let skipHandleFieldChanges = false;

        function syncOperatorAndWorkShiftFromShiftSelection() {
            const meta = getSelectedShiftMeta();

            if (!meta.firstShiftId) {
                return false;
            }

            if (meta.workShiftId) {
                skipHandleFieldChanges = true;
                $('#work_shift').val([meta.workShiftId]).trigger('change.select2');
                skipHandleFieldChanges = false;
            }

            const currentPumpOperatorId = $('#pump_operator_id').val();
            if (meta.pumpOperatorId && String(currentPumpOperatorId || '') !== String(meta.pumpOperatorId)) {
                $('#pump_operator_id').val(String(meta.pumpOperatorId)).trigger('change.select2');
                return true;
            }

            return false;
        }

        function handleFieldChanges() {
            if (skipHandleFieldChanges) {
                return;
            }

            const activeSettlementId = $('#active_settlement_id').val();
            if (!activeSettlementId || activeSettlementId === '0') {
                return;
            }

            const previouslySelectedShiftIds = normalizeShiftIds($('#shift_number').val());
            const pumpOperatorId = $('#pump_operator_id').val();
            const directShiftPayload = getDirectShiftPayload(pumpOperatorId);

            $.ajax({
                method: 'put',
                url: `/petrodirect/settlement-pd/${activeSettlementId}`,
                data: {
                    note: $('#note').val(),
                    work_shift: $('#work_shift').val(),
                    direct_shift_number: directShiftPayload.number,
                    direct_shift_operator_id: directShiftPayload.operatorId,
                    transaction_date: $('#transaction_date').val(),
                    pump_operator_id: pumpOperatorId,
                    location_id: $('#location_id').val()
                },
                success: function(result) {
                    if (result.success == 1) {
                        toastr.success(result.msg);

                        if (typeof result.optionHtml !== 'undefined') {
                            $('#shift_number').html(result.optionHtml);

                            const matchingShiftIds = previouslySelectedShiftIds.filter(id =>
                                $('#shift_number').find(`option[value="${id}"]`).length > 0
                            );

                            if (matchingShiftIds.length > 0) {
                                $('#shift_number').val(matchingShiftIds);
                            } else if ($('#shift_number option').length >= 1) {
                                $('#shift_number option:first').prop('selected', true);
                            }

                            $('#shift_number').trigger('change.select2').trigger('change');
                        }

                        rememberSelectedDirectShift(pumpOperatorId);

                        if (result.pump_nos) {
                            updatePumpDropdown(result.pump_nos || {});
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }

        function calculate_payment_tab_total() {
            var meter_sale_total = parseFloat($('#meter_sale_total').val()) || 0;
            var rendered_other_sale_total = parseFloat($('#other_sale_total').val()) || 0;
            var fetched_other_sale_total = parseFloat($('#shift_operator_other_sale_total').val()) || 0;
            var other_sale_total = fetched_other_sale_total || rendered_other_sale_total;
            var other_income_total = parseFloat($('#other_income_total').val()) || 0;
            var customer_payment_total = parseFloat($('#customer_payment_total').val()) || 0;
            var grand_total = meter_sale_total + other_sale_total + other_income_total + customer_payment_total;
            $('#grand_total').val(grand_total);
            $('.grand_total').text(__number_f(grand_total, false, false, __currency_precision));
            
            // Sync labels in the Payment tab summary
            $('.payment_meter_sale_total').text(__number_f(meter_sale_total, false, false, __currency_precision));
            $('.payment_other_sale_total').text(__number_f(other_sale_total, false, false, __currency_precision));
            $('.payment_other_income_total').text(__number_f(other_income_total, false, false, __currency_precision));
            $('.payment_customer_payment_total').text(__number_f(customer_payment_total, false, false, __currency_precision));
            $('#payment_due').text(__number_f(grand_total, false, false, __currency_precision));
        }
        $(document).on("click", ".credit_sale_add_updated", function() {
            console.log('789');

            if ($("#credit_sale_amount").val() == "") {
                toastr.error("Please enter amount");
                return false;
            }
            var credit_sale_customer_id = $("#credit_sale_customer_id").val();
            var customer_name = $("#credit_sale_customer_id :selected").text();
            var credit_sale_product_id = $("#credit_sale_product_id").val();
            var credit_sale_product_name = $("#credit_sale_product_id :selected").text();
            if (
                $("#customer_reference_one_time").val() !== "" &&
                $("#customer_reference_one_time").val() !== null &&
                $("#customer_reference_one_time").val() !== undefined
            ) {
                var customer_reference = $("#customer_reference_one_time").val();
            } else {
                var customer_reference = $("#customer_reference").val();
            }
            var settlement_no = $("#settlement_no").val();
            var order_date = $("#order_date").val();
            var order_number = $("#order_number").val();

            var credit_sale_price = __read_number($("#unit_price"));
            var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;
            var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;
            var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
            var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
            var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;

            var outstanding = $(".current_outstanding").text();
            var credit_limit = $(".credit_limit").text();
            var credit_note = $("#credit_note").val();
            var is_edit = $("#is_edit").val() ?? 0;

            $.ajax({
                method: "post",
                url: "/petrodirect/settlement/payment/save-credit-sale-payment",
                data: {
                    settlement_no: settlement_no,
                    scsp_id: $("#scsp_id").val(),
                    customer_id: credit_sale_customer_id,
                    product_id: credit_sale_product_id,
                    order_number: order_number,
                    order_date: order_date,

                    price: credit_sale_price,
                    unit_discount: credit_unit_discount,
                    qty: credit_sale_qty,
                    amount: credit_total_amount,
                    sub_total: credit_sub_total,
                    total_discount: credit_total_discount,
                    outstanding: outstanding,
                    credit_limit: credit_limit,
                    customer_reference: customer_reference,
                    note: credit_note,
                    is_edit: is_edit,
                },
                success: function(result) {
                    if (!result.success) {
                        toastr.error(result.msg);
                    } else {
                        settlement_credit_sale_payment_id =
                            result.settlement_credit_sale_payment_id;
                        add_payment_updated(credit_total_amount - credit_total_discount);
                        $("#credit_sale_table tbody").prepend(
                            `
                    <tr>
                        <td>` +
                            customer_name +
                            `</td>
                        <td>` +
                            outstanding +
                            `</td>
                        <td>` +
                            credit_limit +
                            `</td>
                        <td>` +
                            order_number +
                            `</td>
                        <td>` +
                            order_date +
                            `</td>
                        <td>` +
                            customer_reference +
                            `</td>
                        <td>` +
                            credit_sale_product_name +
                            `</td>
                        <td>` +
                            __number_f(credit_sale_price, false, false, __currency_precision) +
                            `</td>
                        <td>` +
                            __number_f(credit_sale_qty, false, false, __currency_precision) +
                            `</td>
                        <td class="credit_sale_amount">` +
                            __number_f(
                                credit_total_amount,
                                false,
                                false,
                                __currency_precision
                            ) +
                            `</td>

                        <td class="credit_tbl_discount_amount">` +
                            __number_f(
                                credit_total_discount,
                                false,
                                false,
                                __currency_precision
                            ) +
                            `</td>
                        <td class="credit_tbl_total_amount">` +
                            __number_f(credit_sub_total, false, false, __currency_precision) +
                            `</td>


                        <td>` +
                            credit_note +
                            `</td>
                        <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petrodirect/settlement/payment/delete-credit-sale-payment/` +
                            settlement_credit_sale_payment_id +
                            `"><i class="fa fa-times"></i></button>
                        </td>
                    </tr>
                `
                        );
                        $("#customer_reference_one_time").val("").trigger("change");
                        $(".credit_sale_fields").val("");
                        $(".cash_fields").val("");
                        $("#credit_sale_product_id").trigger("change");
                        $("#order_number").val(order_number);
                        calculateTotal(
                            "#credit_sale_table",
                            ".credit_sale_amount",
                            ".credit_sale_total"
                        );
                        calculateTotal(
                            "#credit_sale_table",
                            ".credit_tbl_discount_amount",
                            ".credit_tb_discount_total"
                        );
                        calculateTotal(
                            "#credit_sale_table",
                            ".credit_tbl_total_amount",
                            ".credit_tbl_amount_total"
                        );
                    }
                },
            });
        });

        $(document).ready( function(){
            // Do not call these initially, let the auto-selection trigger them via the change event
            // fetchOtherSales();
            // loadMeterSalesData();

            $(document).on('change', '#shift_number', function() {
                var shift_id = normalizeShiftIds($(this).val());
                if (!shift_id || shift_id.length === 0) {
                    $('.below_box').addClass('hide');
                    $('#add_payment').prop('disabled', true).addClass('disabled');
                } else {
                    const changedOperator = syncOperatorAndWorkShiftFromShiftSelection();
                    if (changedOperator && typeof buildPersistableData === 'function') {
                        persistLocalUpdate(buildPersistableData());
                    }
                    $('.below_box').removeClass('hide');
                    $('#add_payment').prop('disabled', false).removeClass('disabled');
                    fetchOtherSales();
                    loadMeterSalesData();
                }
            });


            // Auto-trigger data loading with shift-first logic so the linked operator is derived from the shift.
            setTimeout(function() {
                console.log('=== Auto-selection Logic Starting ===');

                let prefilledShift = normalizeShiftIds($('#shift_number').val());
                let prefilledOperator = $('#pump_operator_id').val();
                const activeSettlementId = $('#active_settlement_id').val();

                console.log('Initial state - Operator:', prefilledOperator, 'Shift:', prefilledShift);

                if (prefilledShift.length) {
                    console.log('Auto-loading data for pre-filled shift number:', prefilledShift);
                    $('#shift_number').trigger('change');
                    return;
                }

                if (activeSettlementId && activeSettlementId !== '0' && prefilledOperator && prefilledOperator !== '') {
                    console.log('Operator already pre-filled:', prefilledOperator);
                    return;
                }

                console.log('Fresh PD create state detected, leaving shift/operator unselected');
            }, 500);
        });

        function fetchOtherSales() {
            var new_shift_ids = $shiftId.val();
            if (!new_shift_ids || new_shift_ids.length === 0) {
                return; // nothing selected, exit
            }
            // Ensure shift_ids is always an array (PHP expects is_array check)
            if (!Array.isArray(new_shift_ids)) {
                new_shift_ids = [new_shift_ids];
            }
            apiGet("{{ action('\Modules\PetroDirect\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}", {
                shift_ids: new_shift_ids,
                pump_operator_id: $('#pump_operator_id').val(),
                get_total: true
            }).done(result => {
                if (result && result.success == 1) {
                    if (!getSelectedDirectShiftNumber()) {
                        updatePumpDropdown(result.pump_nos || {});
                    }

                    $('#shift_operator_other_sale_total').val(parseFloat(result.total) || 0);
                    $('#other_sale_total').val(0);
                    calculate_payment_tab_total();
                } else {
                    toastError("Error fetching other sale total");
                }
            }).fail(() => toastError("Error fetching other sale data"));
        }


        // Generic DataTable initializer / reload helper
        window.initOrReloadDataTable = function(selector, opts) {
            if ($.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().ajax.reload(null, false);
                return;
            }
            // merge default settings
            const defaults = {
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                fnDrawCallback: function() {}
            };
            $(selector).DataTable($.extend(true, {}, defaults, opts));
        }


        function loadMeterSalesData() {
            // Petro Direct renders only its own meter rows.  The former AJAX
            // source was a Pumper Dashboard endpoint and leaked operational
            // shift readings into Direct Settlement.
            $('#outside_meter_sale_table').hide();
            $('#meter_sale_table_wrap').show();
        }


        function add_payment_updated(add_amount) {
            add_amount = parseFloat(add_amount);
            total_balance = parseFloat($("#total_balance").val());
            total_paid = parseFloat($("#total_paid").val());
            total_balance = total_balance + add_amount;
            console.log('total_balance', total_balance);
            total_paid = total_paid + add_amount;
            $("#total_balance").val(__number_f(total_balance, false, false, __currency_precision));
            $("#total_paid").val(total_paid);
            $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
            $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));
            /* if (total_balance === 0) {
                  $("#settlement_save_btn").removeClass("hide");
              } else {
                  $("#settlement_save_btn").addClass("hide");
              }*/
            show_hide_excess_shortage_tab();
            calculateDenoms(add_amount);
        }
    </script>
    <script>
        @if ($can_edit_details[0] == 0)
            $('.edit_disabled').modal({
                backdrop: 'static',
                keyboard: false
            });
        @endif

        @if (!empty($active_settlement))
            $('.transaction_date').datepicker("setDate",
                "{{ \Carbon::parse($active_settlement->transaction_date)->format('m/d/Y') }}");
        @else
            $('.transaction_date').datepicker("setDate", new Date());
        @endif
        $('#customer_payment_cheque_date').datepicker("setDate", new Date());
        $('#location_id').select2();
        $('#shif_time_in').datetimepicker({
            format: 'LT'
        });
        $('#shif_time_out').datetimepicker({
            format: 'LT'
        });
        $('#item').select2();
        $('#store_id').select2();


        // `#add_payment` is handled by the global `.btn-modal` loader in `public/js/app.js`.

        $(document).on("click", ".cash_add_updated", function() {
            console.log('123');
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
                url: "/petrodirect/settlement/payment/save-cash-payment",
                data: {
                    customer_id: cash_customer_id,
                    amount: cash_amount,
                    settlement_no: settlement_no,
                    note: cash_note,
                    is_edit: is_edit,
                },
                success: function(result) {
                    if (!result.success) {
                        toastr.error(result.msg);
                    } else {
                        if ($("#calculate_cash").is(":checked")) {
                            $(".denoms_totals").hide();
                            $(".cash_to_disable").hide();
                            $("#cash_amount").prop("readonly", true);
                        } else {
                            $(".denoms_totals").show();
                            $(".cash_to_disable").show();
                            $("#cash_amount").prop("readonly", false);
                        }

                        console.log("here is cash add data ==>", result);
                        settlement_cash_payment_id = result.settlement_cash_payment_id;
                        add_payment(cash_amount);
                        $("#cash_table tbody").append(
                            `
                            <tr>
                                <td>` +
                            customer_name +
                            `</td>
                                <td class="cash_amount">` +
                            __number_f(cash_amount, false, false, __currency_precision) +
                            `</td>
                                <td>` +
                            cash_note +
                            `</td>
                                <td><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="/petrodirect/settlement/payment/delete-cash-payment/` +
                            settlement_cash_payment_id +
                            `"><i class="fa fa-times"></i></button>
                                </td>
                            </tr>
                        `
                        );
                        $(".cash_fields").val("");
                        calculateTotal("#cash_table", ".cash_amount", ".cash_total");
                    }
                },
            });
        });
        $(document).on("click", ".excess_add_btn", function() {
            if ($(this).closest('.add_payment').length) {
                return;
            }
            console.log('function called')
            var excess_amount_input = $("#excess_amount").val();
            var excess_note = $("#excess_note").val();
            if (excess_amount_input == "") {
                toastr.error("Please enter amount");
                return false;
            }
            var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));
            if (!isNaN(current_balance) && current_balance > 0) {
                toastr.error("Balance is positive. Please use Shortage");
                return false;
            }
            var excess_amount = __read_number($("#excess_amount")) ?? 0;
            if (isNaN(excess_amount) || excess_amount <= 0) {
                toastr.error("Please enter a positive amount");
                return false;
            }
            var settlement_no = $("#settlement_no").val();
            var excess_amount_signed = 0 - Math.abs(excess_amount);
            var is_edit = $("#is_edit").val() ?? 0;

            $.ajax({
                method: "post",
                url: "/petrodirect/settlement/payment/save-excess-payment",
                data: {
                    settlement_no: settlement_no,
                    amount: excess_amount_signed,
                    note: excess_note,
                    is_edit: is_edit
                },
                success: function(result) {
                    console.log(result.success)
                    if (!result.success) {
                        toastr.error(result.msg);
                    } else {

                        settlement_excess_payment_id = result.settlement_excess_payment_id;
                        $("#excess_table tbody").append(
                            `
                        <tr>
                            <td></td>
                            <td class="excess_amount">` +
                            __number_f(Math.abs(excess_amount), false, false,
                                __currency_precision) +
                            `</td>
                            <td>` +
                            excess_note +
                            `</td>
                            <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petrodirect/settlement/payment/delete-excess-payment/` +
                            settlement_excess_payment_id +
                            `"><i class="fa fa-times"></i></button>
                            </td>
                        </tr>
                    `
                        );
                        console.log('working');
                        $(".excess_fields").val("");
                        $(".cash_fields").val("");

                        $("#excess_number").val(result.excess_number);
                        calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                        add_payment(excess_amount_signed);
                    }
                    console.log('result', result)
                },
            });
        });
        $(document).on("click", ".credit_sale_add", function() {
            if ($("#credit_sale_amount").val() == "") {
                toastr.error("Please enter amount");
                return false;
            }
            var credit_sale_customer_id = $("#credit_sale_customer_id").val();
            var customer_name = $("#credit_sale_customer_id :selected").text();
            var credit_sale_product_id = $("#credit_sale_product_id").val();
            var credit_sale_product_name = $("#credit_sale_product_id :selected").text();
            if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !==
                null && $("#customer_reference_one_time").val() !== undefined) {
                var customer_reference = $("#customer_reference_one_time").val();
            } else {
                var customer_reference = $("#customer_reference").val();
            }
            var settlement_no = $("#settlement_no").val();
            var order_date = $("#order_date").val();
            var order_number = $("#order_number").val();

            var credit_sale_price = __read_number($("#unit_price"));
            var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;
            var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;
            var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
            var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
            var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;

            var outstanding = $(".current_outstanding").text();
            var credit_limit = $(".credit_limit").text();
            var credit_note = $("#credit_note").val();
            var is_edit = $("#is_edit").val() ?? 0;

            $.ajax({
                method: "post",
                url: "/petrodirect/settlement/payment/save-credit-sale-payment",
                data: {
                    settlement_no: settlement_no,
                    scsp_id: $("#scsp_id").val(),
                    customer_id: credit_sale_customer_id,
                    product_id: credit_sale_product_id,
                    order_number: order_number,
                    order_date: order_date,

                    price: credit_sale_price,
                    unit_discount: credit_unit_discount,
                    qty: credit_sale_qty,
                    amount: credit_total_amount,
                    sub_total: credit_sub_total,
                    total_discount: credit_total_discount,
                    outstanding: outstanding,
                    credit_limit: credit_limit,
                    customer_reference: customer_reference,
                    note: credit_note,
                    is_edit: is_edit
                },
                success: function(result) {
                    if (!result.success) {
                        toastr.error(result.msg);
                    } else {
                        settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;
                        add_payment(credit_total_amount - credit_total_discount);
                        $("#credit_sale_table tbody").prepend(
                            `
                            <tr>
                                <td>` +
                            customer_name +
                            `</td>
                                <td>` +
                            outstanding +
                            `</td>
                                <td>` +
                            credit_limit +
                            `</td>
                                <td>` +
                            order_number +
                            `</td>
                                <td>` +
                            order_date +
                            `</td>
                                <td>` +
                            customer_reference +
                            `</td>
                                <td>` +
                            credit_sale_product_name +
                            `</td>
                                <td>` +
                            __number_f(credit_sale_price, false, false, __currency_precision) +
                            `</td>
                                <td>` +
                            __number_f(credit_sale_qty, false, false, __currency_precision) +
                            `</td>
                                <td class="credit_sale_amount">` +
                            __number_f(credit_total_amount, false, false, __currency_precision) +
                            `</td>

                                <td class="credit_tbl_discount_amount">` +
                            __number_f(credit_total_discount, false, false, __currency_precision) +
                            `</td>
                                <td class="credit_tbl_total_amount">` +
                            __number_f(credit_sub_total, false, false, __currency_precision) +
                            `</td>


                                <td>` +
                            credit_note +
                            `</td>
                                <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petrodirect/settlement/payment/delete-credit-sale-payment/` +
                            settlement_credit_sale_payment_id +
                            `"><i class="fa fa-times"></i></button>
                                </td>
                            </tr>
                        `
                        );
                        $("#customer_reference_one_time").val("").trigger("change");
                        $(".credit_sale_fields").val("");
                        $(".cash_fields").val("");
                        $("#credit_sale_product_id").trigger('change');
                        $("#order_number").val(order_number);
                        calculateTotal("#credit_sale_table", ".credit_sale_amount",
                            ".credit_sale_total");
                        calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount",
                            ".credit_tb_discount_total");
                        calculateTotal("#credit_sale_table", ".credit_tbl_total_amount",
                            ".credit_tbl_amount_total");

                    }
                },
            });
        });

        @if (!empty($active_settlement))
            $('#note, #work_shift, #transaction_date, #pump_operator_id, #location_id').change(handleFieldChanges);
        @endif


        $('#card_customer_id').select2();
        $('#work_shift').select2();
        $('#customer_payment_customer_id').select2();
        $('#settlement_print').css('visibility', 'hidden');

        // Pump No change handler – load starting/closing meter details
        $(document).off('change', '#pump_no_pd').on('change', '#pump_no_pd', function () {
            var selected_pump_id = $(this).val();
            const shift_id = $shiftId.val();
            if (!selected_pump_id || !shift_id) return;

            pump_closing_meter = 0.0;
            pump_starting_meter = 0.0;

            $.ajax({
                method: 'get',
                url: '/petrodirect/settlement-pd/get-pump-details/' + selected_pump_id + '/' + (Array.isArray(shift_id) ? shift_id[0] : shift_id),
                success: function (result) {
                    $('.pump_starting_meter').val(result.colsing_value);
                    // Make closing meter editable for user input
                    $('.pump_closing_meter').val("").prop('readonly', false);
                    $("#is_from_pumper").val(0);

                    if (result.po_closing > 0) {
                        $('#assignment_id').val(result.assignment_id);
                        $('#pumper_entry_id').val(result.pumper_entry_id);
                    }

                    if (result.po_testing > 0) {
                        $('.testing_qty').val(result.po_testing).prop('readonly', true).trigger('change');
                    } else {
                        $('.testing_qty').val(0).prop('readonly', false);
                    }

                    // Set global variables used by the Add button handler in app.js
                    pump_starting_meter = parseFloat(result.colsing_value);
                    tank_qty = result.tank_remaing_qty;
                    code = result.product.sku;
                    price = result.product.default_sell_price;
                    product_name = result.product.name;
                    pump_name = result.pump_name;
                    pump_id = result.pump_id;
                    product_id = result.product_id;

                    if (result.bulk_sale_meter == '1') {
                        $('#bulk_sale_meter').val(1);
                        $('.pump_starting_meter_div, .pump_closing_meter_div').addClass('hide');
                        $('.sold_qty').prop('disabled', false);
                    } else {
                        $('#bulk_sale_meter').val(0);
                        $('.pump_starting_meter_div, .pump_closing_meter_div').removeClass('hide');
                        $('.sold_qty').prop('disabled', true);
                    }
                    $('#meter_sale_unit_price').val(price);
                }
            });
        });

        // Add Meter Sale button handler (overrides app.js handler which has is_edit bug)
        var pd_meter_sale_submitting = false;
        $(document).off('click.petro_meter_sale_pd').on('click.petro_meter_sale_pd', '.btn_meter_sale_pd', function () {
            if (pd_meter_sale_submitting) return false;

            var $btn = $(this);
            var sold_qty = parseFloat($('#sold_qty').val()) || 0;
            if (sold_qty <= 0 && $('#bulk_sale_meter').val() == 0) {
                toastr.error("Sold quantity must be greater than zero.");
                return false;
            }

            pd_meter_sale_submitting = true;
            $btn.prop('disabled', true).addClass('disabled');

            var testing_qty = $('#testing_qty').val() || 0;
            var discount = $('#meter_sale_discount').val() || 0;
            var discount_type = $('#meter_sale_discount_type').val() || 'fixed';
            var sub_total = sold_qty * parseFloat(price);
            var discount_amount = sub_total - calculate_discount(discount_type, discount, sub_total);
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'post',
                url: '/petrodirect/settlement-pd/save-meter-sale',
                data: {
                    settlement_no: $('#settlement_no').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift: $('#work_shift').val(),
                    direct_shift_number: getSelectedDirectShiftNumber(),
                    note: $('#note').val(),
                    pump_id: pump_id,
                    starting_meter: $('#pump_starting_meter').val(),
                    closing_meter: $('#pump_closing_meter').val(),
                    product_id: product_id,
                    price: price,
                    qty: sold_qty,
                    discount: discount,
                    discount_type: discount_type,
                    discount_amount: discount_amount,
                    testing_qty: testing_qty,
                    sub_total: sub_total,
                    is_edit: is_edit,
                    is_from_pumper: $("#is_from_pumper").val() || 0,
                    assignment_id: $("#assignment_id").val() || 0,
                    pumper_entry_id: $("#pumper_entry_id").val() || 0,
                    shift_id: (Array.isArray($('#shift_number').val()) ? $('#shift_number').val()[0] : $('#shift_number').val())
                },
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg || "Meter sale added.");
                        $('#active_settlement_id').val(result.settlement_id);
                        if (result.table_html) {
                            const $directMeterResult = $('<div>').html(result.table_html);
                            const $newDirectMeterTable = $directMeterResult.find('#meter_sale_table').first();
                            const newDirectMeterTotal = $directMeterResult.find('#meter_sale_total').first().val();
                            if ($newDirectMeterTable.length) {
                                $('#meter_sale_table').replaceWith($newDirectMeterTable);
                            }
                            if (typeof newDirectMeterTotal !== 'undefined') {
                                $('#meter_sale_total').val(newDirectMeterTotal);
                            }
                            $('#meter_sale_table_wrap').show();
                            $('#outside_meter_sale_table').hide();
                        } else if (typeof loadMeterSalesData === 'function') {
                            loadMeterSalesData();
                        }
                        if (typeof refresh_settlement_totals === 'function') refresh_settlement_totals();

                        // Clear fields
                        $('#pump_no_pd').val('').trigger('change.select2');
                        $('.meter_sale_fields').not('select').val('');
                        $('.pump_closing_meter').prop('readonly', true);
                    } else {
                        toastr.error(result.msg);
                    }
                },
                complete: function () {
                    pd_meter_sale_submitting = false;
                    $btn.prop('disabled', false).removeClass('disabled');
                }
            });
        });
    </script>


    <script>
        $(document).on('click', '#save_edit_price_other_income_btn', function() {
            var edit_price = $('#other_income_edit_price').val();

            $('#other_income_price').val(edit_price);
            $('#other_income_edit_price').val('0');
            $('#edit_price_other_income').modal('hide');
        });

        $('#other_sale_qty').change(function() {
            if (parseFloat($(this).val()) > parseFloat($('#balance_stock').val())) {
                toastr.error('Out of Stock');
                $(this).val('').focus();
            }
        })
    </script>
@endsection
