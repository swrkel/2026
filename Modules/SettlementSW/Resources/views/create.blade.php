@extends('layouts.app')

@section('title', 'Settlement Sw')

@section('css')
    <style>
        /* -------------------------------------------------------
                 * Base Styles
                 * ----------------------------------------------------- */
        body,
        .form-control,
        .table-custom th,
        .table-custom td {
            font-family: Calibri, sans-serif;
            font-size: 12px;
        }

        .settlement-page-wrapper {
            padding: 10px 20px;
        }

        /* -------------------------------------------------------
                 * Form Input Styling
                 * ----------------------------------------------------- */
        #meter-sales-form input.form-control,
        #meter-sales-form select.form-control {
            font-size: 13px;
            /* +1pt from default */
        }

        .form-group-custom {
            flex: 1 1 14.28%;
            /* 7 columns per row */
            max-width: 14.28%;
            padding: 8px;
        }

        .form-group-custom2 {
            flex: 1 1 14.28%;
            max-width: 7%;
            padding: 8px;
        }

        input[type="text"].input_number,
        input[type="number"].form-control,
        .table-custom td:nth-child(n+4):not(:last-child) {
            text-align: right;
        }

        /* -------------------------------------------------------
                 * Table Column Adjustments
                 * ----------------------------------------------------- */
        .table-custom th:nth-child(2),
        .product-column {
            width: 12%;
        }

        .starting-meter,
        .closing-meter {
            width: calc(current_width + 4%);
        }

        /* -------------------------------------------------------
                 * Scrollable Table Wrapper
                 * ----------------------------------------------------- */
        .meter-sale-scroll {
            overflow-x: auto;
            max-width: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }

        /* -------------------------------------------------------
                 * SweetAlert: Custom (Medium)
                 * ----------------------------------------------------- */
        .swal-modal.custom-swal {
            width: 350px !important;
            padding: 10px 12px !important;
            border-radius: 5px !important;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
        }

        .swal-modal.custom-swal .swal-title {
            font-size: 16px !important;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }

        .swal-modal.custom-swal .swal-text {
            font-size: 14px !important;
            text-align: center;
            margin-bottom: 10px;
        }

        .swal-modal.custom-swal .swal-footer {
            display: flex !important;
            flex-direction: row-reverse !important;
            justify-content: right !important;
            gap: 10px;
            padding: 5px 0;
        }

        .swal-modal.custom-swal .swal-button {
            padding: 5px 10px !important;
            font-size: 13px !important;
            min-width: unset !important;
        }

        /* -------------------------------------------------------
                 * SweetAlert: Small Variant
                 * ----------------------------------------------------- */
        .swal-modal.small-swal {
            width: 250px !important;
            padding: 10px 15px !important;
            font-size: 12px !important;
            line-height: 1.3 !important;
            box-sizing: border-box;
        }

        .swal-modal.small-swal .swal-title {
            font-size: 12px !important;
            margin: 5px 0 8px !important;
            line-height: 1.2 !important;
        }

        .swal-modal.small-swal .swal-text {
            font-size: 12px !important;
            margin: 0 0 10px !important;
            padding: 0 !important;
            line-height: 1.3 !important;
            text-align: center;
        }

        .swal-modal.small-swal .swal-footer {
            padding: 5px 0 0 0 !important;
            margin: 0 !important;
            text-align: center;
        }

        .swal-modal.small-swal .swal-button {
            padding: 4px 10px !important;
            font-size: 12px !important;
            margin: 0 5px !important;
        }

        /* -------------------------------------------------------
                 * SweetAlert Buttons
                 * ----------------------------------------------------- */
        .swal-btn-no {
            background-color: #dc3545 !important;
            color: #fff !important;
            font-size: 20px !important;
            padding: 10px 25px !important;
        }

        .swal-btn-yes {
            background-color: #28a745 !important;
            color: #fff !important;
            font-size: 20px !important;
            padding: 10px 25px !important;
        }
    </style>
    {{--
|--------------------------------------------------------------------------
| CUSTOM STYLES
|--------------------------------------------------------------------------
--}}
    <style>
        /* Global Container */
        .settlement-container {
            padding: 1rem;
        }

        /* Modal Fix (Adjust if needed, but keeping original width override) */
        .modal-dialog {
            width: 100% !important;
        }

        /* Card */
        .card-custom {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card-custom .card-header {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.6rem 1rem;
            border-radius: 4px 4px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e9ecef;
            border-left: 4px solid #007bff;
        }

        .card-custom .card-body {
            padding: 1rem;
        }

        /* Form row (Flexbox based 6-column grid) */
        .form-row-custom {
            display: flex;
            flex-wrap: wrap;
            margin: 0.4rem;
            /* Counter padding for children */
        }

        .form-group-custom {
            flex: 1 1 14%;
            /* 7 fields per row by default */
            min-width: 160px;
        }

        .form-group-custom.full-width {
            flex: 1 1 100%;
            /* max-width: 100%; */
        }

        .form-group-custom label {
            font-size: 12px;
            color: #333;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .form-group-custom .form-control {
            font-size: 12px;
            padding: 0.3rem 0.6rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Tables */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table-custom th,
        .table-custom td {
            border: 1px solid #dee2e6;
            padding: .5rem;
            font-size: 12px;
        }

        .table-custom th {
            background: #f1f1f1;
            color: #333;
        }

        .table-custom tfoot td {
            font-weight: 600;
        }

        /* Buttons */
        .btn-add {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: .25rem .5rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        .btn-primary-custom {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: .5rem 1rem;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary-custom:hover {
            background-color: #0069d9;
        }

        .product_summary {
            white-space: nowrap;
            font-weight: 500;
        }

        /* Lists */
        .list-unstyled-custom {
            list-style: none;
            padding-left: 0;
            margin-top: 1rem;
        }

        .list-unstyled-custom li {
            display: flex;
            justify-content: space-between;
            padding: .25rem 0;
            border-bottom: 1px dashed #ccc;
        }

        /* Summary */
        .summary-text {
            text-align: right;
            font-weight: 600;
            font-size: 12px;
            margin-top: .5rem;
        }

        .list-item-inline {
            display: inline-block;
            margin-right: 0.5rem;
            position: relative;
        }

        .list-item-inline:not(:last-child)::after {
            content: ",";
            position: absolute;
            right: -8px;
            top: 60%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #333;
            line-height: 1;
        }

        .page-title {
            font-weight: 600
        }
    </style>
@endsection


@section('content')
    @php
        $business_id = session()->get('user.business_id');

        $meter_sale_arr =
            !empty($active_settlement) && request()->segment(2) == 'edit'
                ? $active_settlement->meter_sales->toArray()[0]
                : [];

        $pump_no = $pump_starting_meter = $pump_closing_meter = $sold_qty = $meter_sale_unit_price = null;
        $testing_qty = '0.00';
        $meter_sale_discount_type = null;
        $meter_sale_discount = '0.00';
        $meter_sale_id = null;
        $final_total = 0.0;

        if (!empty($meter_sale)) {
            $pump_no = $meter_sale->pump_id;
            $pump_starting_meter = number_format($meter_sale->starting_meter, 3);
            $pump_closing_meter = number_format($meter_sale->closing_meter, 3);
            $sold_qty = $meter_sale->qty;
            $meter_sale_unit_price = $meter_sale->price;
            $testing_qty = $meter_sale->testing_qty;
            $meter_sale_discount_type = $meter_sale->discount_type;
            $meter_sale_discount = $meter_sale->discount;
            $meter_sale_id = $meter_sale->id;
        }

        $default_store = session()->get('business.default_store');

        $permissions = [
            'settlement_sw_cash',
            'settlement_sw_cash_deposit',
            'settlement_sw_cards',
            'settlement_sw_cheque',
            'settlement_sw_payment_expenses',
            'settlement_sw_shortage',
            'settlement_sw_excess',
            'settlement_sw_credit',
            'settlement_sw_credit_sales',
            'settlement_sw_loan_payments',
            'settlement_sw_owners_drawings',
            'settlement_sw_loan_to_customer',
        ];

        foreach ($permissions as $index => $perm) {
            ${'p' . ($index + 1)} = \App\Utils\ModuleUtil::hasThePermissionInSubscription($business_id, $perm);
        }

        $settlement_sw_cash = $p1 ?? false;
        $settlement_sw_cash_deposit = $p2 ?? false;
        $settlement_sw_cards = $p3 ?? false;
        $settlement_sw_cheque = $p4 ?? false;
        $settlement_sw_payment_expenses = $p5 ?? false;
        $settlement_sw_shortage = $p6 ?? false;
        $settlement_sw_excess = $p7 ?? false;
        $settlement_sw_credit = $p8 ?? false;
        $settlement_sw_credit_sales = $p9 ?? false;
        $settlement_sw_loan_payments = $p10 ?? false;
        $settlement_sw_owners_drawings = $p11 ?? false;
        $settlement_sw_loan_to_customer = $p12 ?? false;

        $other_income_final_total = 0.0;
        $customer_payment_total = 0.0;

    @endphp

    {{-- <div class="breadcrumb-bar" style="margin-bottom: 15px;">
    <strong>Settlement SW</strong>
</div> --}}

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <h2 class="page-title font-weight-bold" style="margin-top: 15px;">
                    @lang('SettlementSW::lang.settlement_sw')
                </h2>
            </div>
        </div>
    </div>

    <section class="content main-content-inner">
        <div class="settlement-page-wrapper">
            <div class="settlement-container" id="setlementForm">
                @php
                    $settlement_no_new = !empty($active_settlement)
                        ? $active_settlement->settlement_no
                        : $settlement_no;
                @endphp

                <!-- Filters -->
                <div class="card-custom">

                    <div class="card-header">Filters</div>

                    <div class="card-body">

                        <form id="filters-form" class="form-row-custom">

                            <div class="form-group-custom ">

                                {!! Form::label('settlement_no', __('settlementsw::lang.settlement_no') . ':') !!}

                                {!! Form::text('settlement_no', $settlement_no_new, ['class' => 'form-control', 'readonly']) !!}

                            </div>



                            <div class="form-group-custom ">

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
                                
                                        'placeholder' => __('settlementsw::lang.all'),
                                        'style' => 'width:100%',
                                    ],
                                ) !!}

                            </div>

                            <div class="form-group-custom ">

                                {!! Form::label('pump_operator', __('settlementsw::lang.pump_operator') . ':') !!}

                                {!! Form::select(
                                    'pump_operator_id',
                                    $pump_operators,
                                    !empty($active_settlement) ? $active_settlement->pump_operator_id : null,
                                    [
                                        'class' => 'form-control select2',
                                        'id' => 'pump_operator_id',
                                        'disabled' => !empty($select_pump_operator_in_settlement) ? false : true,
                                
                                        'placeholder' => __('settlementsw::lang.please_select'),
                                    ],
                                ) !!}

                            </div>

                            <div class="form-group-custom">

                                {!! Form::label('transaction_date', __('settlementsw::lang.transaction_date') . ':*') !!}

                                {!! Form::date(
                                    'transaction_date',
                                    $active_settlement ? date('Y-m-d', strtotime($active_settlement->transaction_date)) : null,
                                    ['class' => 'form-control first_date', 'placeholder' => __('fleet::lang.date'), 'id' => 'transaction_date'],
                                ) !!}

                            </div>

                            <div class="form-group-custom ">

                                {!! Form::label('work_shift', __('settlementsw::lang.work_shift') . ':') !!}

                                {!! Form::select(
                                    'work_shift[]',
                                    $wrok_shifts,
                                    !empty($active_settlement) ? $active_settlement->work_shift ?? [] : [],
                                    ['class' => 'form-control select2', 'id' => 'work_shift', 'multiple'],
                                ) !!}

                            </div>



                            <div class="form-group-custom ">

                                {!! Form::label('shift_number', __('settlementsw::lang.daily_shift_number') . ':') !!}

                                {!! Form::select('shift_number[]', $shift_numbers, null, [
                                    'id' => 'shift_number',
                                    'class' => 'form-control select2',
                                    'multiple' => true,
                                ]) !!}

                            </div>

                            <div class="form-group-custom  full-width">
                                {!! Form::label('note', __('SettlementSW::lang.note') . ':') !!}

                                {!! Form::text(
                                    'note',
                                
                                    !empty($active_settlement)
                                        ? (is_array($active_settlement->note)
                                            ? implode(', ', $active_settlement->note)
                                            : $active_settlement->note)
                                        : null,
                                
                                    [
                                        'class' => 'form-control note',
                                
                                        'placeholder' => __('settlementsw::lang.note'),
                                    ],
                                ) !!}

                            </div>

                        </form>

                    </div>

                </div>

                <!-- Meter Sales -->
                @include(
                    'settlementsw::partials.meter_sales',
                    compact(
                        'pump_no',
                        'pump_starting_meter',
                        'pump_closing_meter',
                        'sold_qty',
                        'meter_sale_unit_price',
                        'testing_qty',
                        'meter_sale_discount_type',
                        'meter_sale_discount',
                        'final_total',
                        'temp_data'))

                <!-- Other Sales -->
                @include(
                    'settlementsw::partials.other_sales',
                    compact('default_store', 'temp_data', 'pump_other_sale_final_total', 'check_qty'))

                <!-- Other Income & Customer Payments -->
                @include(
                    'settlementsw::partials.other_income_customer_payments',
                    compact(
                        'is_other_income',
                        'is_customer_payments',
                        'temp_data',
                        'other_income_final_total',
                        'customer_payment_total'))

                <!-- Credit Sales -->
                @if ($settlement_sw_credit)
                    @include(
                        'settlementsw::partials.credit_sales',
                        compact('credit_customers', 'only_walkin', 'temp_data', 'products'))
                @endif

                <!-- Expenses -->
                @if (isset($is_expenses) && $is_expenses != 0)
                    @include(
                        'settlementsw::partials.expenses',
                        compact('expense_payment_settlement_no', 'expense_categories', 'temp_data'))
                @endif
            </div>
        </div>


        @if (isset($is_payment) && $is_payment != 0)
            <!-- Payments Summary -->
            <div id="add_payment_container"></div>
        @endif
        <!--  -->
        <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

        </div>

        <div class="modal fade add_payment" role="dialog" aria-labelledby="gridSystemModalLabel" style="overflow-y: auto;">

        </div>

        <div class="modal fade preview_settlement" role="dialog" aria-labelledby="gridSystemModalLabel">

        </div>

        <div id="settlement_print"></div>
        <!--  -->
    @endsection

    @section('javascript')


        <script src="{{ asset('modules/settlementsw/js/swsettlement/add-payment.js') }}?v={{ time() }}"></script>

        <script>
            $(document).ready(function() {

                /* ------------------------------------------------------------
                 * 1️⃣ GLOBAL VARIABLES
                 * ------------------------------------------------------------ */
                let tank_qty = 0,
                    code = '',
                    price = 0.0,
                    product_name = '',
                    pump_name = '',
                    pump_closing_meter = 0.0,
                    pump_starting_meter = 0.0,
                    meter_sale_total = parseFloat($('#meter_sale_total').val()),
                    product_id = null,
                    pump_id = null;

                const currencyPrecision = {{ session('business.currency_precision', 2) }};
                const $belowBox = $('#below_box');
                const $storeSelect = $('#store_id');
                const $pumpOperator = $('#pump_operator_id');
                const currencyPrecision2 = {{ $currency_precision }};



                /* -------------------------------------------------------
                 * Settlement Payment Initialization
                 * ----------------------------------------------------- */
                @if (isset($is_payment) && $is_payment != 0)
                    // alert('ddd');
                    const operatorId = $('#pump_operator_id').val();
                    const shiftIds = $('#shift_number').val();

                    if (!operatorId || !shiftIds) {
                        console.warn('Missing operator or shift ID');
                    } else {
                        const url =
                            "{{ route('sw-add-payment.create') }}" +
                            "?settlement_no={{ $settlement_no }}" +
                            "&operator_id=" + operatorId +
                            "&shift_ids=" + shiftIds +
                            "&provider=SET_SW" +
                            "&settlement_page=1";

                        $('#add_payment_container').load(url, function(response, status, xhr) {
                            if (status === "error") {
                                console.error("Error loading Add Payment HTML:", xhr.status, xhr.statusText);
                                $('#add_payment_container').html(
                                    '<p class="text-danger">Failed to load payment section.</p>');
                                return;
                            }

                            const tabVisibility = {
                                cash_tab: {{ $p1 ?? 0 }},
                                cash_deposit_tab: {{ $p2 ?? 0 }},
                                cards_tab: {{ $p3 ?? 0 }},
                                cheques_tab: {{ $p4 ?? 0 }},
                                expense_tab: {{ $p5 ?? 0 }},
                                shortage_tab: {{ $p6 ?? 0 }},
                                excess_tab: {{ $p7 ?? 0 }},
                                credit_sales_tab: {{ $p8 ?? 0 }},
                                loan_payments_tab: {{ $p9 ?? 0 }},
                                drawing_payments_tab: {{ $p10 ?? 0 }},
                                settlement_customer_loans_tab: {{ $p11 ?? 0 }}
                            };

                            Object.entries(tabVisibility).forEach(([cls, visible]) => {
                                if (!visible) {
                                    const el = document.querySelector(`.${cls}`);
                                    if (el) el.closest('li').style.display = 'none';
                                }
                            });
                        });
                    }
                @endif

                // 

                /* ------------------------------------------------------------
                 * 2️⃣ HELPER FUNCTIONS
                 * ------------------------------------------------------------ */
                const calculate_discount = (type, val, amount) =>
                    type === 'fixed' ?
                    parseFloat(val) || 0 :
                    type === 'percentage' ?
                    ((amount * parseFloat(val)) / 100) || 0 :
                    0;

                const disableBelowBox = () => $belowBox.find('*').attr('disabled', true);
                const enableBelowBox = () => $belowBox.find('*').attr('disabled', false);

                const formatNum = (val) => formatWithCommasFixed(val, currencyPrecision);

                /* ------------------------------------------------------------
                 * 3️⃣ PUMP OPERATOR CHANGE
                 * ------------------------------------------------------------ */
                $pumpOperator.change(function() {
                    const op_id = $(this).val();
                    const store_id = $storeSelect.val();

                    if (!op_id) {
                        toastr.error('Please Select the Pump operator and continue');
                        return;
                    }

                    $.ajax({
                        method: 'GET',
                        url: `/settlement-sw/get_pumps/${op_id}`,
                        data: {
                            settlement_no: $('#settlement_no').val(),
                            location_id: $('#location_id').val(),
                            pump_operator_id: op_id,
                            transaction_date: $('#transaction_date').val(),
                            work_shift: $('#work_shift').val(),
                            note: $('#note').val(),
                        },
                        success: function(result) {
                            if (!result.success) return toastr.error(result.msg);
                            if (result.should_reload) return window.location.reload();

                            enableBelowBox();
                            if (!store_id) $('.other_sale_fields#item').attr('disabled', true);

                            if (result.shift_numbers) {
                                const $shift = $('#shift_number').empty();
                                // If you want a placeholder option for multi-select you can skip it
                                $.each(result.shift_numbers, function(k, v) {
                                    $shift.append(`<option value="${k}">${v}</option>`);
                                });
                                // re-init/select2 refresh
                                if ($shift.data('select2')) {
                                    $shift.trigger('change.select2');
                                } else {
                                    $shift.select2({
                                        width: '100%'
                                    });
                                }
                            }

                            const $dropdown = $('#pump_no').empty();
                            $dropdown.append('<option value="">Please select</option>');
                            $.each(result.pumps, (key, value) =>
                                $dropdown.append(`<option value="${key}">${value}</option>`)
                            );

                            addPaymentSection();
                        },
                    });
                });

                $('#shift_number').on('change', function() {
                    addPaymentSection();
                });

                /* ------------------------------------------------------------
                 * 5️⃣ AUTO SAVE TEMP DATA
                 * ------------------------------------------------------------ */
                @if (auth()->user()->can('unfinished_form.settlement_sw'))
                    setInterval(() => {
                        $.post('{{ route('settlement-sw.temp.save') }}', getAllSettlementData());
                    }, 10000);
                @endif

                /* ------------------------------------------------------------
                 * 6️⃣ NOTE MODAL HANDLING
                 * ------------------------------------------------------------ */
                $('#noteButton').click(() => {
                    $('#noteInput').val($('#noteHidden').val()).prop('readonly', false);
                    $('#saveNote').show();
                    $('#noteOverlay, #noteModal').fadeIn(200);
                });

                $(document).on('click', '.btn-note-view', function(e) {
                    e.preventDefault();
                    const note = $(this).data('note') || '';
                    $('#noteInput').val(note).prop('readonly', true);
                    $('#saveNote').hide();
                    $('#noteOverlay, #noteModal').fadeIn(200);
                });

                $('#closeNote').click(() => $('#noteModal, #noteOverlay').fadeOut(200));
                $('#saveNote').click(() => {
                    const note = $('#noteInput').val();
                    $('#noteHidden').val(note);
                    $('#noteDisplay').text(note);
                    $('#noteModal, #noteOverlay').fadeOut(200);
                });

                /* ------------------------------------------------------------
                 * 7️⃣ VEHICLE FIELD INSERTION
                 * ------------------------------------------------------------ */
                $('#add-credit-sale').closest('.form-group-custom').before(`
        <div class="form-group-custom field-with-comma" id="customer-reference-one-time-wrapper">
          <label for="customer_reference_one_time">{{ __('SettlementSW::lang.enter_customer_vehicle_no') }}</label>
          <input type="text" class="form-control customer_reference_one_time"
                id="customer_reference_one_time" name="customer_reference"
                placeholder="{{ __('SettlementSW::lang.enter_customer_vehicle_no') }}">
        </div>
      `);

                /* ------------------------------------------------------------
                 * 8️⃣ INITIAL ENABLE/DISABLE STATE
                 * ------------------------------------------------------------ */
                if (!$pumpOperator.val()) {
                    disableBelowBox();
                } else {
                    enableBelowBox();
                    if (!$storeSelect.val()) $('.other_sale_fields#item').attr('disabled', true);
                }

                updateTotalSoldQty();

                /* ------------------------------------------------------------
                 * 9️⃣ PUMP DETAILS FETCH
                 * ------------------------------------------------------------ */

                $(document).on('change', '#pump_no', function() {
                    const pumpId = $(this).val();
                    if (!pumpId) return;

                    $.get(`/settlement-sw/get-pump-details/${pumpId}`, function(result) {
                        // Reload payment section
                        addPaymentSection();

                        // Check if pump is already open
                        if (result.is_open > 0) {
                            toastr.error('Please close the pump first before adding a meter sale!');
                            return;
                        }

                        // Set starting meter
                        $('#pump_starting_meter').val(formatWithCommas(result.colsing_value));

                        // Handle all pump response details
                        handlePumpResponse(result);
                    });
                });

                function handlePumpResponse(result) {
                    const {
                        po_closing,
                        po_testing,
                        colsing_value,
                        tank_remaing_qty,
                        product,
                        pump_name: name,
                        pump_id: pid,
                        product_id: prodId,
                        bulk_sale_meter,
                        assignment_id,
                        pumper_entry_id
                    } = result;

                    // Closing meter
                    if (po_closing > 0) {
                        $('#pump_closing_meter').val(formatWithCommas(po_closing)).prop('readonly', true).trigger(
                            'change');
                        $("#is_from_pumper").val(1);
                    } else {
                        $('#pump_closing_meter').val('').prop('readonly', false);
                        $("#is_from_pumper").val(0);
                    }

                    // Testing quantity
                    if (po_testing > 0) {
                        $('#testing_qty').val(formatWithCommas(po_testing)).prop('readonly', true).trigger('change');
                    } else {
                        $('#testing_qty').val(formatWithCommas(0)).prop('readonly', false);
                    }

                    // Update global variables
                    pump_starting_meter = colsing_value;
                    tank_qty = tank_remaing_qty;
                    code = product.sku;
                    price = product.default_sell_price;
                    product_name = product.name;
                    pump_name = name;
                    pump_id = pid;
                    product_id = prodId;

                    // Bulk sale check
                    $('#bulk_sale_meter').val(bulk_sale_meter);
                    $('#meter_sale_unit_price').val(price);

                    if (bulk_sale_meter == '1') {
                        $('.pump_starting_meter_div, .pump_closing_meter_div').addClass('hide');
                        $('#sold_qty').prop('disabled', false);
                    } else {
                        $('.pump_starting_meter_div, .pump_closing_meter_div').removeClass('hide');
                        $('#sold_qty').prop('disabled', true);
                    }

                    // Assignment info
                    $('#assignment_id').val(assignment_id);
                    $('#pumper_entry_id').val(pumper_entry_id);

                    console.log(product_id, 'product_id inside handlePumpResponse');
                }

                $(document).ready(function() {
                    const opId = $('#pump_operator_id').val();

                    if (opId) {
                        $('#pump_operator_id').trigger('change');
                    }
                });


                /* ------------------------------------------------------------
                 * 🔟 METER CLOSING CHANGE VALIDATION
                 * ------------------------------------------------------------ */
                // $(document).on('change', '#pump_closing_meter', function () {
                //   const closing = parseFloat($(this).val().replace(/,/g, '')) || 0;
                //   const starting = parseFloat($('#pump_starting_meter').val().replace(/,/g, '')) || 0;

                //   if (closing < starting) {
                //     toastr.error('Closing meter value should not be less than starting meter value');
                //     $(this).val('');
                //   } else {
                //     $('#sold_qty').val(formatWithCommas(closing - starting));
                //   }
                // });

                $(document).on('change', '#pump_closing_meter', function() {
                    const closingRaw = $(this).val();
                    const closing = parseFloat(String(closingRaw).replace(/,/g, '')) || 0;
                    const starting = parseFloat($('#pump_starting_meter').val().replace(/,/g, '')) || 0;
                    const selectedPump = $('#pump_no').val();

                    // If user clears closing meter, remove pump from usedPumps so it becomes selectable again
                    if (closingRaw === '' || closing === 0) {
                        if (selectedPump) {
                            usedPumps = usedPumps.filter(id => id !== String(selectedPump));
                            refreshPumpDropdown();
                        }
                        $('#sold_qty').val('');
                        return;
                    }

                    if (closing < starting) {
                        toastr.error('Closing meter value should not be less than starting meter value');
                        $(this).val('');
                        return;
                    } else {
                        $('#sold_qty').val(formatWithCommas(closing - starting));
                    }

                    // Mark pump as used (hide/disable in dropdown) so it cannot be selected again before settlement is saved
                    // if (selectedPump && !usedPumps.includes(String(selectedPump))) {
                    //     usedPumps.push(String(selectedPump));
                    //     refreshPumpDropdown();
                    // }
                });

                /* ------------------------------------------------------------
                 * 🧩 ADD OTHER-SALE (Shortened - same logic retained)
                 * ------------------------------------------------------------ */
                $('#item').change(function() {
                    const item_id = $(this).val();
                    if (!item_id) return;

                    $.get('/settlement-sw/get_balance_stock_by_id/' + item_id, {
                        store_id: $storeSelect.val(),
                        location_id: $('#location_id').val()
                    }, function(res) {
                        $('#balance_stock').val(formatNum(res.balance_stock));
                        $('#other_sale_price').val(formatWithCommas(res.price, currencyPrecision));
                        other_sale_code = res.code;
                        other_sale_product_name = res.product_name;
                        other_sale_price = res.price;
                    });
                });

                // //
                $('#settlement_print').css('visibility', 'hidden');

                function formatWithCommas(value) {
                    if (isNaN(value) || value === '') return value;
                    const parts = parseFloat(value).toFixed(3).split(".");
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    return parts.join(".");
                }

                function formatWithCommasFixed(value, decimals = 3) {
                    if (isNaN(value) || value === '') return value;
                    const parts = parseFloat(value).toFixed(decimals).split(".");
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    return parts.join(".");
                }

                function calculateTotal_interne(tableName, classSelector, outputElement) {
                    let total = 0.0;
                    $(tableName + " tbody")
                        .find(classSelector)
                        .each(function() {
                            total += parseFloat(__number_uf($(this).text()));
                        });
                    $(outputElement).text(__number_f(total, false, false, currencyPrecision));
                }

                /* -------------------------------------------------------
                 * Expense Payment Management
                 * ----------------------------------------------------- */

                $(document).on("click", ".sw_expense_add", function() {
                    if (!$("#sw_expense_amount").val()) {
                        toastr.error("Please enter amount");
                        return false;
                    }

                    const data = {
                        settlement_no: $("#settlement_no").val(),
                        expense_number: $("#sw_expense_number").val(),
                        reference_no: $("#sw_reference_no").val(),
                        account_id: $("#sw_expense_account").val(),
                        category_id: $("#sw_expense_category").val(),
                        reason: $("#sw_expense_reason").val(),
                        amount: $("#sw_expense_amount").val(),
                        is_edit: $("#is_edit").val() ?? 0
                    };

                    const accountName = $("#sw_expense_account :selected").text();
                    const categoryName = $("#sw_expense_category :selected").text();

                    $.post("/settlement-sw/save-expense-payment", data, function(result) {
                        if (!result.success) return toastr.error(result.msg);
                        addPaymentSection();
                        const rowHtml = `
                <tr>
                    <td>${data.expense_number}</td>
                    <td>${categoryName}</td>
                    <td>${data.reference_no}</td>
                    <td>${accountName}</td>
                    <td>${data.reason}</td>
                    <td class="sw_expense_amount">${formatWithCommasFixed(data.amount, currencyPrecision)}</td>
                    <td>
                        <button type="button" class="btn btn-xs btn-danger sw_delete_expense_payment"
                            data-href="/settlement-sw/sw-add-payment/expense-payment/${result.settlement_expense_payment_id}">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;

                        $("#expense-tbody").prepend(rowHtml);
                        calculateTotal("#expense_table_new", ".sw_expense_amount", ".sw_expense_total");
                        calculate_payment_tab_total();
                        saveTempData();
                    });
                });

                $(document).on("click", ".sw_delete_expense_payment", function() {
                    const url = $(this).data("href");
                    const row = $(this).closest("tr");
                    const is_edit = $("#is_edit").val() ?? 0;

                    $.ajax({
                        method: "delete",
                        url,
                        data: {
                            is_edit
                        },
                        success: function(result) {
                            if (!result.success) return toastr.error(result.msg);
                            addPaymentSection();
                            toastr.success(result.msg);
                            row.remove();
                            calculateTotal("#expense_table_new", ".sw_expense_amount",
                                ".sw_expense_total");
                            calculate_payment_tab_total();
                            saveTempData();
                        }
                    });
                });

                /* -------------------------------------------------------
                 * Credit Sales Management
                 * ----------------------------------------------------- */
                function appendCreditSaleRow(data) {
                    const row = `
            <tr>
                <td>${data.customer_name}</td>
                <td>${formatWithCommasFixed(data.outstanding)}</td>
                <td>${formatWithCommasFixed(data.limit)}</td>
                <td>${data.order_no}</td>
                <td>${data.order_date}</td>
                <td>${data.customer_reference}</td>
                <td>${data.product}</td>
                <td>${formatWithCommasFixed(data.unit_price, currencyPrecision)}</td>
                <td>${formatWithCommasFixed(data.qty, currencyPrecision)}</td>
                <td>${formatWithCommasFixed(data.sub_total, currencyPrecision)}</td>
                <td>${formatWithCommasFixed(data.discount_total, currencyPrecision)}</td>
                <td>${formatWithCommasFixed(data.total, currencyPrecision)}</td>
                <td>
                    <a href="#" class="btn-note-view" data-note="${data.note ?? ''}">
                        <i class="fa fa-eye"></i> {{ __('messages.view') }}
                    </a>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-credit-sale-row">X</button>
                </td>
            </tr>
        `;
                    $('#credit-sales-tbody').append(row);
                    updateCreditSaleTotals();
                }

                function updateCreditSaleTotals() {
                    let subTotal = 0,
                        discountTotal = 0,
                        total = 0;

                    $('#credit-sales-tbody tr').each(function() {
                        const tds = $(this).find('td');
                        subTotal += parseFloat(tds.eq(9).text()) || 0;
                        discountTotal += parseFloat(tds.eq(10).text()) || 0;
                        total += parseFloat(tds.eq(11).text()) || 0;
                    });

                    $('#credit-sales-subtotal').text(formatWithCommasFixed(subTotal, currencyPrecision));
                    $('#credit-sales-discount-total').text(formatWithCommasFixed(discountTotal, currencyPrecision));
                    $('#credit-sales-total').text(formatWithCommasFixed(total, currencyPrecision));
                }

                $(document).on('click', '.remove-credit-sale-row', function() {
                    $(this).closest('tr').remove();
                    updateCreditSaleTotals();
                    calculate_payment_tab_total();
                    saveTempData();
                    addPaymentSection();
                });

                /* -------------------------------------------------------
                 * Pump Management
                 * ----------------------------------------------------- */
                // let usedPumps = [];

                // function refreshPumpDropdown() {
                //     $('#pump_no option').each(function () {
                //         $(this).toggle(!usedPumps.includes($(this).val()));
                //     });
                //     $('#pump_no').select2(); // Refresh dropdown
                // }

                // function refreshPumpDropdown() {
                //     const $pump = $('#pump_no');
                //     const selectedVal = $pump.val();

                //     $pump.find('option').each(function () {
                //         const val = $(this).val();
                //         if (!val) return;

                //         if (usedPumps.includes(String(val))) {
                //             // keep the currently selected pump enabled so user doesn't lose selection
                //             if (String(selectedVal) === String(val)) {
                //                 $(this).prop('disabled', false).show();
                //             } else {
                //                 $(this).prop('disabled', true).hide();
                //             }
                //         } else {
                //             $(this).prop('disabled', false).show();
                //         }
                //     });

                //     // Refresh select2
                //     if ($pump.data('select2')) {
                //         $pump.trigger('change.select2');
                //     } else {
                //         $pump.select2({ width: '100%' });
                //     }
                // }

                let usedPumps = {};

                // Remove option from dropdown and keep HTML for restoration
                function removePumpOption(pumpId) {
                    if (!pumpId) return;
                    const $pump = $('#pump_no');
                    const $opt = $pump.find(`option[value="${pumpId}"]`);
                    if ($opt.length) {
                        // store full option HTML (preserve data-attributes if any)
                        usedPumps[String(pumpId)] = $opt.prop('outerHTML');
                        $opt.remove();
                        if ($pump.data('select2')) $pump.trigger('change.select2');
                    }
                }

                // Restore option previously removed
                function restorePumpOption(pumpId) {
                    if (!pumpId) return;
                    const $pump = $('#pump_no');
                    const key = String(pumpId);
                    if (usedPumps[key]) {
                        $pump.append(usedPumps[key]);
                        delete usedPumps[key];
                        if ($pump.data('select2')) $pump.trigger('change.select2');
                    } else {
                        // fallback: if server returned pump name, calling code may append manually
                        if ($pump.data('select2')) $pump.trigger('change.select2');
                    }
                }

                $('#add-meter-sale').on('click', function() {

                    if (!$('#pump_operator_id').val()) {
                        toastr.error("Please select pump operator first.");
                        return false;
                    }
                    console.log(product_id, 'product_idproduct_idproduct_idproduct_id on click')
                    addMeterSale();
                    // const selectedPump = $('#pump_no').val();
                    // if (selectedPump && !usedPumps.includes(selectedPump)) {
                    //     usedPumps.push(selectedPump);
                    //     refreshPumpDropdown();
                    // }

                    // calculate_payment_tab_total();
                    // saveTempData();
                });

                // $(document).on('click', '.delete_meter_sale', function (e) {
                //     e.preventDefault();
                //     const row = $(this).closest('tr');
                //     const pumpId = row.data('pump-id');

                //     usedPumps = usedPumps.filter(id => id !== pumpId.toString());
                //     refreshPumpDropdown();
                //     row.remove();

                //     calculate_payment_tab_total();
                //     saveTempData();
                // });

                $(document).on('click', '.delete_meter_sale', function(e) {
                    e.preventDefault();
                    const row = $(this).closest('tr');
                    const pumpId = row.data('pump-id');
                    restorePumpOption(pumpId);
                    row.remove();
                    calculate_payment_tab_total();
                    saveTempData();
                });

                /* -------------------------------------------------------
                 * Modal: Customer Creation
                 * ----------------------------------------------------- */
                $('#customerModal').on('hidden.bs.modal', function() {
                    // Example: Replace with actual new customer data
                    const newCustomer = {
                        id: 123,
                        name: 'John Doe'
                    };
                    const newOption = new Option(newCustomer.name, newCustomer.id, true, true);
                    $('#sw_credit_sale_customer_iddelete_expense_payment')
                        .append(newOption)
                        .trigger('change');
                });

                // //


                function getAllSettlementData() {

                    console.log('i am in get all settlement data function');
                    // Serialize static forms
                    const filtersData = $('#filters-form').serializeArray();
                    const meterSalesData = $('#meter-sales-form').serializeArray();
                    const otherSalesData = $('#other-sales-form').serializeArray();
                    const creditSalesData = $('#credit-sales-form').serializeArray();
                    const otherIncomeData = $('#other-income-form').serializeArray();
                    const custPaymentsData = $('#cust-payments-form').serializeArray();
                    const paymentSummaryData = $('#payments-summary').serializeArray();

                    // ---------------------------
                    // Extract dynamic table data
                    // ---------------------------

                    /** -----------------------
                     *  Meter Sales Table
                     * ----------------------- */
                    const meterSalesRows = [];

                    $('#meter-sales-tbody tr').each(function() {
                        const row = $(this);
                        const parseValue = (index) => parseFloat(row.find(`td:eq(${index})`).text().replace(
                            /,/g, ''));

                        meterSalesRows.push({
                            code: row.find('td:eq(0)').text(),
                            pump_id: row.data('pump-id'),
                            product_name: row.find('.product_name').text(),
                            pump_no: row.find('td:eq(2)').text(),
                            pump_start: row.find('td:eq(3)').text(),
                            pump_close: row.find('td:eq(4)').text(),
                            unit_price: row.find('td:eq(5)').text(),
                            sold_qty: parseValue(6),
                            discount_type: parseValue(7),
                            discount_val: parseValue(8),
                            testing_qty: parseValue(9),
                            total_qty: parseValue(10),
                            before_discount: parseValue(11),
                            after_discount: parseValue(12),
                            meter_sale_total: parseValue(13),
                            id: row.find('.delete_meter_sale').data('href')?.split('/').pop(),
                        });
                    });

                    /** -----------------------
                     *  Other Sales Table
                     * ----------------------- */
                    const OtherSalesRows = [];
                    $('#other-sales-tbody tr').each(function() {
                        const row = $(this);
                        const sub_total = parseFloat(row.find('td:eq(7)').text());
                        const with_discount = parseFloat(row.find('td:eq(8)').text());
                        const discount_amount = sub_total - with_discount;

                        OtherSalesRows.push({
                            code: row.find('td:eq(0)').text(),
                            product_name: row.find('td:eq(1)').text(),
                            product_id: row.find('td:eq(1)').data('product-id'),
                            balance_stock: row.find('td:eq(2)').text(),
                            price: row.find('td:eq(3)').text(),
                            qty: row.find('td:eq(4)').text(),
                            discount_type: row.find('td:eq(5)').text(),
                            discount: row.find('td:eq(6)').text(),
                            sub_total: sub_total,
                            with_discount: with_discount,
                            discount_amount: discount_amount,
                            id: row.find('.delete_other_sale').data('href')?.split('/').pop(),
                        });
                    });

                    /** -----------------------
                     *  Expenses Table
                     * ----------------------- */
                    const ExpenseRows = [];
                    $('#expense-tbody tr').each(function() {
                        const row = $(this);
                        ExpenseRows.push({
                            expense_number: row.find('td:eq(0)').text(),
                            expense_category_name: row.find('td:eq(1)').text(),
                            reference_no: row.find('td:eq(2)').text(),
                            expense_account_name: row.find('td:eq(3)').text(),
                            expense_reason: row.find('td:eq(4)').text(),
                            expense_amount: row.find('td:eq(5)').text(),
                            delete_url: row.find('.sw_delete_expense_payment').data('href'),
                        });
                    });

                    /** -----------------------
                     *  Credit Sales Table
                     * ----------------------- */
                    const CreditSalesRows = [];
                    $('#credit-sales-tbody tr').each(function() {
                        const row = $(this);
                        const parseValue = (index) => parseFloat(row.find(`td:eq(${index})`).text().replace(
                            /,/g, ''));

                        CreditSalesRows.push({
                            customer_name: row.find('td:eq(0)').text(),
                            outstanding: row.find('td:eq(1)').text(),
                            limit: row.find('td:eq(2)').text(),
                            order_no: row.find('td:eq(3)').text(),
                            order_date: row.find('td:eq(4)').text(),
                            customer_reference: row.find('td:eq(5)').text(),
                            product: row.find('td:eq(6)').text(),
                            unit_price: parseValue(7),
                            qty: parseValue(8),
                            sub_total: parseValue(9),
                            discount_total: parseValue(10),
                            total: parseValue(11),
                            note: row.find('td:eq(12)').text(),
                        });
                    });

                    /** -----------------------
                     *  Payments Tables
                     * ----------------------- */

                    // Card Payments
                    const CardRows = [];
                    $('#card_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(3)').text().replace(/,/g, ''));
                        CardRows.push({
                            customer_name: row.find('td:eq(0)').text(),
                            card_type: row.find('td:eq(1)').text(),
                            card_number: row.find('td:eq(2)').text(),
                            card_amount: amount,
                            slip_no: row.find('td:eq(4)').text(),
                            card_note: row.find('td:eq(5)').text(),
                            delete_url: row.find('.delete_card_payment').data('href'),
                        });
                    });

                    // Cash Payments
                    const CashRows = [];
                    $('#cash_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(1)').text().replace(/,/g, ''));
                        CashRows.push({
                            customer_name: row.find('td:eq(0)').text(),
                            cash_amount: amount,
                            cash_note: row.find('td:eq(2)').text(),
                            delete_url: row.find('.delete_cash_payment').data('href'),
                        });
                    });

                    // Cheque Payments
                    const ChequeRows = [];
                    $('#cheque_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(4)').text().replace(/,/g, ''));
                        ChequeRows.push({
                            customer_name: row.find('td:eq(0)').text(),
                            bank_name: row.find('td:eq(1)').text(),
                            cheque_number: row.find('td:eq(2)').text(),
                            cheque_date: row.find('td:eq(3)').text(),
                            cheque_amount: amount,
                            cheque_note: row.find('td:eq(5)').text(),
                            delete_url: row.find('.delete_cheque_payment').data('href'),
                        });
                    });

                    // Cash Deposit
                    const cashDepositRows = [];
                    $('#cash_deposit_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(2)').text().replace(/,/g, ''));
                        cashDepositRows.push({
                            bank_name: row.find('td:eq(0)').text(),
                            account: row.find('td:eq(1)').text(),
                            cash_deposit_amount: amount,
                            time: row.find('td:eq(3)').text(),
                            delete_url: row.find('.delete_cash_payment').data('href'),
                        });
                    });

                    /** -----------------------
                     *  Additional Payment Tables
                     * ----------------------- */
                    const AddPaymentExpenseRows = [];
                    $('#expense_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(5)').text().replace(/,/g, ''));
                        AddPaymentExpenseRows.push({
                            expense_number: row.find('td:eq(0)').text(),
                            expense_category_name: row.find('td:eq(1)').text(),
                            reference_no: row.find('td:eq(2)').text(),
                            expense_account_name: row.find('td:eq(3)').text(),
                            expense_reason: row.find('td:eq(4)').text(),
                            expense_amount: amount,
                            delete_url: row.find('.delete_expense_payment').data('href'),
                        });
                    });

                    const ShortageRows = [];
                    $('#shortage_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(1)').text().replace(/,/g, ''));
                        ShortageRows.push({
                            shortage_amount: amount,
                            shortage_note: row.find('td:eq(2)').text(),
                            delete_url: row.find('.delete_shortage_payment').data('href'),
                        });
                    });

                    const ExcessRows = [];
                    $('#excess_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(1)').text().replace(/,/g, ''));
                        ExcessRows.push({
                            excess_amount: amount,
                            excess_note: row.find('td:eq(2)').text(),
                            delete_url: row.find('.delete_excess_payment').data('href'),
                        });
                    });

                    const LoanPaymentRows = [];
                    $('#loan_payments_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(1)').text().replace(/,/g, ''));
                        LoanPaymentRows.push({
                            bank_name: row.find('td:eq(0)').text(),
                            loan_payments_amount: amount,
                            loan_payments_note: row.find('td:eq(2)').text(),
                            delete_url: row.find('.delete_loan_payment').data('href'),
                        });
                    });

                    const DrawingPaymentsRows = [];
                    $('#drawing_payments_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(1)').text().replace(/,/g, ''));
                        DrawingPaymentsRows.push({
                            cash_payment_loan_account_name: row.find('td:eq(0)').text(),
                            cash_payment_amount: amount,
                            cash_payment_note: row.find('td:eq(2)').text(),
                            delete_url: row.find('.delete_drawing_payment').data('href'),
                        });
                    });

                    const CustomerLoansRows = [];
                    $('#customer_loans_table_body tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(1)').text().replace(/,/g, ''));
                        CustomerLoansRows.push({
                            customer_name: row.find('td:eq(0)').text(),
                            customer_loan_amount: amount,
                            customer_loan_note: row.find('td:eq(2)').text(),
                            delete_url: row.find('.delete_customer_loans_payment').data('href'),
                        });
                    });


                    const AddPaymentCreditSalesRows = [];

                    $('#credit_sale_table_body tr').each(function() {
                        const row = $(this);

                        // Extract and clean numeric values
                        const unitPrice = parseFloat(row.find('td:eq(7)').text().trim().replace(/,/g, '')) || 0;
                        const qty = parseFloat(row.find('td:eq(8)').text().trim().replace(/,/g, '')) || 0;
                        const subTotal = parseFloat(row.find('td:eq(9)').text().trim().replace(/,/g, '')) || 0;
                        const discountTotal = parseFloat(row.find('td:eq(10)').text().trim().replace(/,/g,
                            '')) || 0;
                        const total = parseFloat(row.find('td:eq(11)').text().trim().replace(/,/g, '')) || 0;

                        // Push formatted data into array
                        AddPaymentCreditSalesRows.push({
                            customer_name: row.find('td:eq(0)').text().trim(),
                            outstanding: row.find('td:eq(1)').text().trim(),
                            limit: row.find('td:eq(2)').text().trim(),
                            order_no: row.find('td:eq(3)').text().trim(),
                            order_date: row.find('td:eq(4)').text().trim(),
                            customer_reference: row.find('td:eq(5)').text().trim(),
                            product: row.find('td:eq(6)').text().trim(),
                            unit_price: unitPrice,
                            qty: qty,
                            sub_total: subTotal,
                            discount_total: discountTotal,
                            total: total,
                            note: row.find('td:eq(12)').text().trim(),
                        });
                    });


                    /** -----------------------
                     *  Other Income Table
                     * ----------------------- */
                    const OtherIncomeItems = [];
                    $('#other-income-tbody tr').each(function() {
                        const row = $(this);
                        const amount = parseFloat(row.find('td:eq(2)').text().replace(/,/g, ''));
                        OtherIncomeItems.push({
                            service: row.find('td:eq(0)').text(),
                            details: row.find('td:eq(1)').text(),
                            amount: amount,
                            delete_url: row.find('.sw_delete_other_income').data('href'),
                        });
                    });

                    /** -----------------------
                     *  Customer Payments Summary
                     * ----------------------- */
                    const customerPayments = [];
                    $('#cust-payments-list .custum-name').each(function() {
                        const nameText = $(this).clone().children().remove().end().text().trim();
                        const payment = parseFloat($(this).find('.custum-val').text().replace(/,/g, ''));
                        customerPayments.push({
                            customer_name: nameText,
                            total_customer_payments: payment,
                        });
                    });

                    /** -----------------------
                     *  Combine and return all data
                     * ----------------------- */
                    const combinedData = [
                        ...filtersData,
                        ...meterSalesData,
                        ...otherSalesData,
                        ...creditSalesData,
                        ...otherIncomeData,
                        ...custPaymentsData,
                        ...paymentSummaryData,
                    ];

                    const finalData = {};
                    combinedData.forEach((item) => {
                        if (finalData[item.name]) {
                            if (!Array.isArray(finalData[item.name])) finalData[item.name] = [finalData[item
                                .name]];
                            finalData[item.name].push(item.value);
                        } else {
                            finalData[item.name] = item.value;
                        }
                    });

                    // Attach dynamic data arrays
                    Object.assign(finalData, {
                        meter_sales: meterSalesRows,
                        other_sales: OtherSalesRows,
                        credit_sales: CreditSalesRows,
                        other_income: OtherIncomeItems,
                        cust_payments_list: customerPayments,
                        expense_rows: ExpenseRows,
                        card_rows: CardRows,
                        cash_rows: CashRows,
                        cash_deposit_rows: cashDepositRows,
                        cheque_rows: ChequeRows,
                        add_payment_expense_rows: AddPaymentExpenseRows,
                        shortage_rows: ShortageRows,
                        add_payment_credit_sale: AddPaymentCreditSalesRows,
                        excess_rows: ExcessRows,
                        loan_payment_rows: LoanPaymentRows,
                        drawing_payments_rows: DrawingPaymentsRows,
                        customer_loans_rows: CustomerLoansRows,
                    });

                    console.log(finalData);
                    return finalData;
                }




                /* ------------------------------------------------------------
                 * 🧮  Utility Functions
                 * ------------------------------------------------------------ */
                function parseNumber(value) {
                    return parseFloat(String(value || '').replace(/,/g, '')) || 0;
                }

                function updateOtherSalesTotal() {
                    let total = 0;
                    $('#other-sales-tbody tr').each(function() {
                        const afterDiscount = parseNumber($(this).find('td:eq(8)').text());
                        total += afterDiscount;
                    });
                    $('#other-sale-after-discount-total').text(formatWithCommasFixed(total, currencyPrecision));
                }

                function updateOtherIncomeTotal() {
                    let total = 0;
                    $('#other-income-tbody tr').each(function() {
                        const amount = parseNumber($(this).find('td:eq(2)').text());
                        total += amount;
                    });
                    $('#other-income-total').text(formatWithCommasFixed(total, currencyPrecision));
                    $('#other_income_total').val(total);
                }

                function capitalizeFirstLetter(string) {
                    return string.charAt(0).toUpperCase() + string.slice(1);
                }

                /* ------------------------------------------------------------
                 * 💰  Calculate Payment Tab Total
                 * ------------------------------------------------------------ */
                function calculate_payment_tab_total(comingAmount = 0) {
                    const currencyPrecision = {{ session('business.currency_precision', 2) }};

                    const meter_sale_totals = parseNumber($('#meter_sale_total').val());
                    const shift_operator_other_sale_total = parseNumber($('#shift_operator_other_sale_total').val());
                    const other_sale_totals = parseNumber($('#other-sale-after-discount-total').text());
                    const other_income_totals = parseNumber($('#other_income_total').val());
                    const customer_payment_totals = parseNumber($('#customer_payment_total').val());

                    const all_totals = meter_sale_totals + other_sale_totals + other_income_totals +
                        customer_payment_totals;

                    // Update display sections
                    const numf = (val) => __number_f(val, false, false, currencyPrecision);
                    $('.payment_meter_sale_total, .meter_sale_total').text(numf(meter_sale_totals));
                    $('.payment_other_sale_total, .other_sale_total').text(numf(other_sale_totals));
                    $('.payment_other_income_total, .other_income_total').text(numf(other_income_totals));
                    $('.payment_customer_payment_total, .customer_payment_total').text(numf(customer_payment_totals));

                    $('#amount_total').val(numf(all_totals));
                    $('.total_amount').text(numf(all_totals));

                    console.log('All Total:', all_totals);

                    if (comingAmount > 0) add_payment(comingAmount);
                }

                /* ------------------------------------------------------------
                 * 🧾  Other Income Tab
                 * ------------------------------------------------------------ */
                $(document).on('click', '#add-other-income', function() {
                    const qty = parseNumber($('.other_income_qty').val());
                    const reason = $('#other_income_reason').val();
                    const selectedText = $('.other_income_product option:selected').text();
                    const is_edit = $("#is_edit").val() ?? 0;

                    const data = {
                        settlement_no: $('#settlement_no').val(),
                        product_id: product_id,
                        qty: qty,
                        other_income_reason: reason,
                        is_edit: is_edit
                    };

                    $.post('/settlement-sw/save-other-income', data, function(result) {
                        addPaymentSection();

                        if (!result.success) {
                            toastr.error(result.msg);
                            return;
                        }

                        $('#other-income-tbody').append(`
      <tr>
        <td>${selectedText}</td>
        <td>${result.data.reason}</td>
        <td>${formatWithCommasFixed(result.data.qty, currencyPrecision)}</td>
        <td>
          <button type="button" class="btn btn-xs btn-danger sw_delete_other_income"
                  data-href="/settlement-sw/delete-other-income/${result.other_income_id}">
            <i class="fa fa-times"></i>
          </button>
        </td>
      </tr>
    `);

                        updateOtherIncomeTotal();
                        calculate_payment_tab_total();
                        updateBalance();
                    });
                });
                /* ------------------------------------------------------------
                 * 💳  Customer Payment Tab - Table Version
                 * ------------------------------------------------------------ */
                $(document).on('click', '#add-payment', function() {
                    const operatorId = $('#pump_operator_id').val();
                    if (!operatorId) {
                        toastr.error("Please select pump operator first.");
                        return;
                    }

                    const amount = parseNumber($('#customer_payment_amount').val());
                    const customerName = $('#customer_payment_customer_id :selected').text();
                    const paymentMethod = $('#customer_payment_payment_method').val();
                    const bankName = $('#customer_payment_bank_name').val();
                    const chequeDate = $('#customer_payment_cheque_date').val();
                    const chequeNumber = $('#customer_payment_cheque_number').val();
                    const postDated = $('#customer_payment_post_dated_cheque').val();
                    const is_edit = $("#is_edit").val() ?? 0;

                    const data = {
                        settlement_no: $("#settlement_no").val(),
                        location_id: $('#location_id').val(),
                        pump_operator_id: operatorId,
                        transaction_date: $('#transaction_date').val(),
                        work_shift: $('#work_shift').val(),
                        note: $('#note').val(),
                        settlement_customer_payment_no: $('#settlement_customer_payment_no').val(),
                        customer_id: $('#customer_payment_customer_id').val(),
                        payment_method: paymentMethod,
                        bank_name: bankName,
                        cheque_date: chequeDate,
                        cheque_number: chequeNumber,
                        amount: amount,
                        sub_total: amount,
                        is_edit: is_edit,
                        post_dated_cheque: postDated
                    };

                    $.post('/settlement-sw/save-customer-payment', data, function(result) {
                        addPaymentSection();

                        if (!result.success) {
                            toastr.error(result.msg);
                            return;
                        }

                        // Show table container
                        $('#business-payments-cust-wrapper').removeClass('d-none');

                        // Append row to existing table
                        const rowHtml = `
            <tr class="custum-name paymt-${result.customer_payment_id}" data-id="${result.customer_payment_id}">
                <td>${result.customer_name}</td>
                <td>${result.payment_method}</td>
                <td class="payment_amount">${formatWithCommasFixed(result.amount, currencyPrecision)}</td>
                <td>
                    <button type="button" class="btn btn-xs btn-danger sw_delete_cust_payment"
                        data-href="/settlement-sw/delete-customer-payment/${result.customer_payment_id}">
                        <i class="fa fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
                        $('#cust-payments-list').prepend(rowHtml);

                        // Recalculate total
                        let total = 0;
                        $("#cust-payments-list .payment_amount").each(function() {
                            total += parseNumber($(this).text());
                        });
                        $('#customer_payment_total').val(formatWithCommasFixed(total,
                            currencyPrecision));
                        $('#cust-payments-total').text(formatWithCommasFixed(total, currencyPrecision));

                        // Reset fields
                        $('#customer_payment_amount').val('');
                        $('#customer_payment_customer_id').val('').trigger('change');
                        $('#settlement_customer_payment_no').val(result.settlement_no);

                        // Recalculate balance & save temp
                        addPaymentSection();
                        calculate_payment_tab_total(amount);
                        updateBalance();
                        saveTempData();
                    });
                });



                /**
                 * Delete custom payment row and recalculate totals.
                 */
                function deleteUpdateCustomPaymentInAddPaymentForm(row_id) {
                    $(`.paymt-${row_id}`).remove();
                    calculateTotal("#cash_table", ".cash_amount", ".cash_total");
                    calculateTotal("#cheque_table", ".cheque_amount", ".cheque_total");
                    calculateTotal("#card_table", ".card_amount", ".card_total");
                    addPaymentSection();
                }

                /**
                 * Delete payment from list and trigger API deletion.
                 */
                function deleteCustumePaymentDataId(id) {
                    const li = $(`#cust-payments-list li[data-id="${id}"]`);
                    const url = li.find("button.sw_delete_cust_payment").data("href");
                    const is_edit = $("#is_edit").val() ?? 0;

                    const amountText = li.find(".custum-val").text();
                    const amount = parseFloat(amountText.replace(/,/g, ""));
                    deleteCustomerPaymer(url, is_edit, amount, id, li);
                    addPaymentSection();
                }

                /**
                 * Toggle fields visibility based on selected payment method.
                 */
                function togglePaymentFields() {
                    const method = $("#customer_payment_payment_method").val();
                    $(".card_div, .cheque_divs, .bank_div").addClass("hide");

                    if (method === "cheque") $(".cheque_divs").removeClass("hide");
                    else if (method === "card") $(".card_div").removeClass("hide");
                    else if (method === "bank") $(".bank_div").removeClass("hide");
                }

                $("#customer_payment_payment_method").on("change", togglePaymentFields);
                togglePaymentFields();
                $("#customer_payment_cheque_date").datepicker("setDate", new Date());

                /**
                 * Document ready initialization.
                 */
                $(document).ready(function() {
                    const $customerSelect = $("#sw_credit_sale_customer_iddelete_expense_payment");
                    $customerSelect.val($customerSelect.find("option:eq(0)").val()).trigger("change");

                    $("#sw_order_date, .transaction_date").datepicker("setDate", new Date());
                    $("#sw_credit_sale_product_id, #sw_customer_reference, #sw_credit_sale_customer_iddelete_expense_payment")
                        .select2();
                });

                /**
                 * Disable reference selection when one-time reference is entered.
                 */
                $(document).on("change", "#customer_reference_one_time", function() {
                    const hasValue = $(this).val()?.trim();
                    $("#sw_customer_reference, .quick_add_customer_reference").prop("disabled", !!hasValue);
                });

                /**
                 * Add new customer reference form submission.
                 */
                $(document).on("submit", "#customer_reference_add_form", function(e) {
                    e.preventDefault();
                    const form = $(this);

                    $.post(form.attr("action"), form.serialize(), function(result) {
                        if (result.success) {
                            $("#sw_credit_sale_customer_iddelete_expense_payment").trigger("change");
                        }
                        $(".view_modal").modal("hide");
                    }, "json");
                });

                /**
                 * Handle qty or discount/price change for recalculations.
                 */
                function recalcCreditSale() {
                    const price = __read_number($("#sw_unit_price")) ?? 0;
                    const qty = __read_number($("#sw_credit_sale_qty")) ?? 0;
                    const totalDiscount = __read_number($("#credit_discount_amount")) ?? 0;

                    const totalAmount = price * qty;
                    const unitDiscount = totalDiscount / (qty || 1);
                    const netAmount = totalAmount - totalDiscount;

                    __write_number($("#sw_credit_total_amount"), totalAmount);
                    __write_number($("#sw_unit_discount"), unitDiscount);
                    __write_number($("#sw_credit_sale_amount"), netAmount);
                }

                $(document).on("input", "#sw_credit_sale_qty", function() {
                    $("#sw_credit_total_amount").attr("disabled", true);
                    recalcCreditSale();
                });

                $(document).on("change", "#credit_discount_amount, #sw_unit_price", function() {
                    const price = __read_number($("#sw_unit_price")) ?? 0;
                    let qty = __read_number($("#sw_credit_sale_qty")) ?? 0;
                    let totalAmount = 0;

                    if (qty > 0) {
                        totalAmount = price * qty;
                    } else {
                        totalAmount = __read_number($("#sw_credit_total_amount")) ?? 0;
                        qty = totalAmount / (price || 1);
                        __write_number_without_decimal_format($("#sw_credit_sale_qty"), qty);
                    }

                    const totalDiscount = __read_number($("#credit_discount_amount")) ?? 0;
                    const unitDiscount = totalDiscount / (qty || 1);
                    const amount = totalAmount - totalDiscount;

                    __write_number($("#sw_unit_discount"), unitDiscount);
                    __write_number($("#sw_credit_sale_amount"), amount);
                });

                /**
                 * Fetch customer details when selected.
                 */
                $(document).on("change", "#sw_credit_sale_customer_iddelete_expense_payment", function() {
                    const customerId = $(this).val();
                    if (!customerId) return console.log("No customer selected, skipping API call.");

                    $.get(`/settlement-sw/sw-add-payment/customer-details/${customerId}`, function(result) {
                        $(".current_outstanding").text(result.total_outstanding);
                        $(".credit_limit").text(result.credit_limit);

                        const $ref = $("#sw_customer_reference");
                        $ref.empty().append(`<option value="">Please Select</option>`);
                        result.customer_references.forEach(ref => {
                            $ref.append(
                                `<option value="${ref.reference}">${ref.reference}</option>`
                            );
                        });
                    });
                });

                /**
                 * Fetch product price when product changes.
                 */
                $(document).on("change", "#sw_credit_sale_product_id", function() {
                    const productId = $(this).val();
                    if (!productId) {
                        $("#sw_credit_total_amount, #sw_credit_sale_qty, #credit_discount_amount").attr(
                            "disabled", true);
                        return;
                    }

                    $.get("/settlement-sw/sw-add-payment/product-price", {
                        product_id: productId
                    }, function(result) {
                        $("#sw_unit_price").val(formatWithCommasFixed(result.price, currencyPrecision))
                            .trigger("change");
                        $("#sw_credit_total_amount, #sw_credit_sale_qty").attr("disabled", false);

                        if ($("#manual_discount").val() == 1)
                            $("#credit_discount_amount").attr("disabled", false);
                    });
                });

                /**
                 * Handle add credit sale.
                 */
                $(document).on("click", "#sw_add-credit-sale", function() {
                    if (!$("#sw_credit_sale_amount").val()) return toastr.error("Please enter amount");

                    const operator_Id = $('#pump_operator_id').val();
                    if (!operator_Id) {
                        toastr.error("Please select pump operator first.");
                        return;
                    }

                    const getVal = id => __read_number($(id)) ?? 0;
                    const is_edit = $("#is_edit").val() ?? 0;

                    const payload = {
                        settlement_no: $("#settlement_no").val(),
                        customer_id: $("#sw_credit_sale_customer_iddelete_expense_payment").val(),
                        product_id: $("#sw_credit_sale_product_id").val(),
                        order_number: $("#sw_order_number").val(),
                        order_date: $("#sw_order_date").val(),
                        price: getVal("#sw_unit_price"),
                        unit_discount: getVal("#sw_unit_discount"),
                        operator_Id: operator_Id,
                        qty: getVal("#sw_credit_sale_qty"),
                        amount: getVal("#sw_credit_total_amount"),
                        sub_total: getVal("#sw_credit_sale_amount"),
                        total_discount: getVal("#credit_discount_amount"),
                        outstanding: $(".current_outstanding").text(),
                        credit_limit: $(".credit_limit").text(),
                        customer_reference: $("#customer_reference_one_time").val() || $(
                            "#sw_customer_reference").val(),
                        note: $("#credit_note").val(),
                        is_edit
                    };

                    $.post("/settlement-sw/save-credit-sale-payment", payload, function(result) {
                        addPaymentSection();

                        if (!result.success) return toastr.error(result.msg);

                        const data = {
                            customer_name: $(
                                "#sw_credit_sale_customer_iddelete_expense_payment option:selected"
                            ).text(),
                            outstanding: "0.00",
                            limit: "0.00",
                            order_no: payload.order_number,
                            order_date: payload.order_date,
                            customer_reference: payload.customer_reference,
                            product: $("#sw_credit_sale_product_id option:selected").text(),
                            unit_price: payload.price,
                            qty: payload.qty,
                            sub_total: payload.sub_total,
                            discount_total: payload.total_discount,
                            total: payload.sub_total - payload.total_discount,
                            note: $("#noteInput").val() || ""
                        };

                        appendCreditSaleRow(data);
                        settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;

                        if (result.total_credit_sales !== undefined) {
                            $("#credit-sales-total").text(formatWithCommasFixed(result
                                .total_credit_sales, currencyPrecision));
                            $(".total-credit-sales-value").removeClass("hidden").show();
                        }

                        $("#noteHidden").val("");
                        $("#noteDisplay").text("");
                        $("#credit-sales-form")[0].reset();
                        $("#credit-sales-form select.select2").val(null).trigger("change");

                        calculate_payment_tab_total();
                        saveTempData();
                    });
                });

                $('#add-other-sale').click(function(e) {
                    e.preventDefault();

                    // Validate pump operator
                    const pumpOperatorId = $('#pump_operator_id').val();
                    if (!pumpOperatorId) {
                        toastr.error("Please select pump operator first.");
                        return false;
                    }

                    // Get input values
                    let otherSaleQty = parseFloat($('#other_sale_qty').val()) || 0;
                    let otherSalePriceRaw = $('#other_sale_price').val();
                    // Remove all commas in the the price
                    const cleanedOtherSalePrice = otherSalePriceRaw.replace(/,/g, '');
                    const otherSalePrice = parseFloat(cleanedOtherSalePrice) || 0;
                    let otherSaleDiscount = parseFloat($('#other_sale_discount').val()) || 0;
                    let otherSaleDiscountType = $('#other_sale_discount_type').val() || 'fixed';
                    const balanceStockText = $('#balance_stock').val().trim();
                    const allowOverSelling = Boolean($("#allowoverselling").val());
                    const balanceStock = parseFloat(balanceStockText.replace(/,/g, '')) || 0;

                    // Stock validation
                    if (otherSaleQty > balanceStock && !allowOverSelling) {
                        toastr.error('Out of Stock');
                        $(this).val('').focus();
                        return false;
                    }

                    // Calculate totals
                    const subTotal = otherSaleQty * otherSalePrice;
                    const otherSaleDiscountAmount = calculate_discount(otherSaleDiscountType, otherSaleDiscount,
                        subTotal);
                    const withDiscount = subTotal - otherSaleDiscountAmount;

                    let otherSaleTotal = parseFloat($('#other_sale_total').val().replace(/,/g, '')) || 0;
                    otherSaleTotal += withDiscount;

                    const isEdit = $("#is_edit").val() ?? 0;
                    let otherSaleId = null;

                    // AJAX request to save other sale
                    $.ajax({
                        method: 'POST',
                        url: '/settlement-sw/save-other-sale',
                        data: {
                            settlement_no: $('#settlement_no').val(),
                            location_id: $('#location_id').val(),
                            pump_operator_id: pumpOperatorId,
                            transaction_date: $('#transaction_date').val(),
                            work_shift: $('#work_shift').val(),
                            note: $('#note').val(),
                            product_id: $('#item').val(),
                            store_id: $('#store_id').val(),
                            price: otherSalePrice,
                            qty: otherSaleQty,
                            balance_stock: balanceStock,
                            discount: otherSaleDiscount,
                            discount_type: otherSaleDiscountType,
                            discount_amount: otherSaleDiscountAmount,
                            sub_total: withDiscount,
                            is_edit: isEdit
                        },
                        success: function(result) {
                            addPaymentSection();

                            if (!result.success) {
                                toastr.error(result.msg);
                                return false;
                            }

                            $('#other_sale_total').val(formatWithCommas(otherSaleTotal));
                            otherSaleId = result.other_sale_id;

                            const currencyPrecision2 = {{ $currency_precision }};
                            const subTotalFormatted = __number_f(subTotal);

                            // Append new row
                            $('#other-sales-tbody').append(`
                <tr>
                    <td>${other_sale_code}</td>
                    <td data-product-id="${$('#item').val()}">${other_sale_product_name}</td>
                    <td>${formatWithCommasFixed(balanceStock, currencyPrecision2)}</td>
                    <td>${formatWithCommasFixed(otherSalePrice, currencyPrecision)}</td>
                    <td>${formatWithCommasFixed(otherSaleQty, currencyPrecision2)}</td>
                    <td>${capitalizeFirstLetter(otherSaleDiscountType)}</td>
                    <td>${formatWithCommasFixed(otherSaleDiscount, currencyPrecision)}</td>
                    <td>${formatWithCommasFixed(subTotalFormatted, currencyPrecision)}</td>
                    <td>${formatWithCommasFixed(withDiscount, currencyPrecision)}</td>
                    <td>
                        <button class="btn btn-xs btn-danger delete_other_sale" 
                                data-href="/settlement-sw/delete-other-sale/${otherSaleId}">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);

                            // Reset fields
                            $('.other_sale_fields').val('').trigger('change');

                            // Update totals
                            updateOtherSalesTotal();

                            sleep(300)
                                .then(() => {
                                    calculate_payment_tab_total();
                                    updateBalance();
                                    saveTempData();
                                })
                                .catch(err => console.log(err));
                        }
                    });
                });


                const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

                function handleDelete(url, data, onSuccess, onError) {
                    $.ajax({
                        method: 'DELETE',
                        url,
                        data,
                        success: result => {
                            if (result.success) {
                                toastr.success(result.msg);
                                if (typeof onSuccess === 'function') onSuccess(result);
                            } else {
                                toastr.error(result.msg);
                                if (typeof onError === 'function') onError(result);
                            }
                        },
                    });
                }

                function refreshTotals() {
                    calculate_payment_tab_total();
                    updateBalance();
                }

                async function updateBalance() {
                    await sleep(300);
                    const total = parseFloat(($('.total_amount').text() ?? '0').replace(/,/g, '')) || 0;
                    const paid = parseFloat(($('#total_paid').val() ?? '0').replace(/,/g, '')) || 0;
                    const balance = total - paid;

                    $('#total_balance').val(__number_f(balance, false, false, currencyPrecision));
                    $('.total_balance').text(__number_f(balance, false, false, currencyPrecision));

                    await sleep(100);
                    calculateDenoms();
                }

                /* ===============================
                   🔹 DELETE EVENT HANDLERS
                   =============================== */
                $(document).on('click', '.delete_meter_sale', function() {
                    const url = $(this).data('href');
                    const tr = $(this).closest('tr');
                    const is_edit = $('#is_edit').val() ?? 0;

                    handleDelete(url, {
                        is_edit
                    }, result => {
                        tr.remove();
                        const newTotal = parseFloat($('#meter_sale_total').val()) - parseFloat(result
                            .amount);
                        const formatted = __number_f(newTotal, false, false, currencyPrecision);
                        $('.meter_sale_total').text(formatted);
                        $('#meter_sale_total').val(newTotal);

                        // $('#pump_no').append(`<option value="${result.pump_id}">${result.pump_name}</option>`);
                        if (typeof restorePumpOption === 'function') {
                            restorePumpOption(result.pump_id);
                            // If it wasn't stored, append simple option as fallback
                            if (!$('#pump_no option[value="' + result.pump_id + '"]').length) {
                                $('#pump_no').append(
                                    `<option value="${result.pump_id}">${result.pump_name}</option>`
                                );
                            }
                        } else {
                            $('#pump_no').append(
                                `<option value="${result.pump_id}">${result.pump_name}</option>`);
                        }
                        updateTotalSoldQty();
                        updateMeterSalesTotal();
                        refreshTotals();
                    });
                });

                $(document).on('click', '.delete_other_sale', function() {
                    const url = $(this).data('href');
                    const tr = $(this).closest('tr');
                    const is_edit = $('#is_edit').val() ?? 0;

                    handleDelete(url, {
                        is_edit
                    }, () => {
                        tr.remove();
                        updateOtherSalesTotal();
                        refreshTotals();
                    });
                });

                $(document).on('click', '.sw_delete_other_income', function() {
                    const url = $(this).data('href');
                    const tr = $(this).closest('tr');
                    const is_edit = $('#is_edit').val() ?? 0;

                    handleDelete(url, {
                        is_edit
                    }, () => {
                        tr.remove();
                        updateOtherIncomeTotal();
                        refreshTotals();
                    });
                });

                function deleteCustomerPayment(url, is_edit, amount, cust_pay_id, li, deleteReverse = false) {
                    handleDelete(url, {
                        is_edit
                    }, result => {
                        li.remove();
                        let total = 0;

                        $('#cust-payments-list .custum-name').each(function() {
                            const paymentText = $(this).find('.custum-val').text().trim();
                            total += parseFloat(paymentText.replace(/,/g, '')) || 0;
                        });

                        if (deleteReverse) {
                            let total_paid = parseFloat($('#total_paid').val()) - amount;
                            $('#total_paid').val(total_paid);
                            $('.total_paid').text(__number_f(total_paid, false, false, __currency_precision));
                            deleteUpdateCustomPaymentInAddPaymentForm(cust_pay_id);
                        }

                        $('#customer_payment_total').val(formatWithCommasFixed(total, currencyPrecision));
                        $('#cust-payments-total').text(formatWithCommasFixed(total, currencyPrecision));
                        refreshTotals();
                    });
                }

                $(document).on('click', '.sw_delete_cust_payment', function() {
                    const url = $(this).data('href');
                    const li = $(this).closest('li');
                    const is_edit = $('#is_edit').val() ?? 0;

                    const amount = parseFloat(li.find('.custum-val').text().replace(/,/g, '')) || 0;
                    const cust_pay_id = li.data('id');
                    deleteCustomerPayment(url, is_edit, amount, cust_pay_id, li, true);
                });

                /* ===============================
                   🔹 CREDIT SALE HANDLER
                   =============================== */
                $(document).on('input', '#sw_credit_total_amount', function() {
                    $('#sw_credit_sale_qty').attr('disabled', true);

                    const price = __read_number($('#sw_unit_price')) || 0;
                    const total_amount = __read_number($('#sw_credit_total_amount')) || 0;
                    const qty = total_amount / price;

                    const total_discount = __read_number($('#credit_discount_amount')) || 0;
                    const unit_discount = total_discount / qty;
                    const amount = total_amount - total_discount;

                    __write_number($('#sw_credit_sale_amount'), amount);
                    __write_number($('#sw_unit_discount'), unit_discount);
                    __write_number_without_decimal_format($('#sw_credit_sale_qty'), qty);
                });

                function saveTempData() {
                    $.ajax({
                        method: 'POST',
                        url: '{{ route('settlement-sw.temp.save') }}',
                        dataType: 'json',
                        data: getAllSettlementData(),
                    });
                }

                $(document).on('custom:payment_added', () => saveTempData());
                $(document).on('custom:payment_deleted', (e, id) => deleteCustumePaymentDataId(id));

                $(document).on('click', '.edit_price_other_income', () => $('#edit_price_other_income').modal('show'));

                $(document).on('click', '#save_edit_price_other_income_btn', () => {
                    const newPrice = $('#other_income_edit_price').val();
                    $('#other_income_price').val(newPrice);
                    $('#edit_price_other_income').modal('hide');
                    calculate_payment_tab_total();
                    saveTempData();
                });

                setTimeout(() => {
                    $('.input_number').each(function() {
                        const raw = $(this).val().replace(/,/g, '');
                        $(this).val(formatWithCommas(raw));
                    });
                }, 100);

                function addMeterSale() {

                    console.log(product_id, 'product_idproduct_idproduct_idproduct_id in function')
                    // Validate pump operator
                    if ($('#pump_operator_id').val() == "") {
                        toastr.error("Please select pump operator first.");
                        return false;
                    }

                    // Get values
                    let is_from_pumper = $("#is_from_pumper").val() ?? 0;
                    let assignment_id = $("#assignment_id").val() ?? 0;
                    let pumper_entry_id = $("#pumper_entry_id").val() ?? 0;

                    let pump_id = $('#pump_no').val();
                    // let product_id = product_id;
                    let price = parseFloat($('#meter_sale_unit_price').val()) || 0;
                    let pump_starting_meter = parseFloat($('#pump_starting_meter').val()) || 0;

                    let sold_qty = parseFloat(($('#sold_qty').val() ?? '0').replace(/,/g, '')) || 0;
                    let testing_qty = parseFloat(($('#testing_qty').val() ?? '0').replace(/,/g, '')) || 0;

                    sold_qty = sold_qty - testing_qty;

                    let meter_sale_discount = parseFloat($('#meter_sale_discount').val()) || 0;
                    let meter_sale_discount_type = $('#meter_sale_discount_type').val();
                    let meter_sale_discount_type_text = meter_sale_discount_type ?
                        $('#meter_sale_discount_type option:selected').text() :
                        '';

                    let sub_total = sold_qty * price;
                    let meter_sale_discount_amount = sub_total - calculate_discount(meter_sale_discount_type,
                        meter_sale_discount, sub_total);

                    let meter_sale_total = parseFloat(($('#meter_sale_total').val() ?? '0').replace(/,/g, '')) || 0;

                    meter_sale_total += meter_sale_discount_amount;

                    let is_edit = $("#is_edit").val() ?? 0;
                    let currencyPrecision = {{ $currency_precision ?? 2 }};

                    // Confirm before adding
                    swal({
                        title: "You are going to add a new meter sale",
                        text: "Are you sure to add it?",
                        icon: "warning",
                        buttons: {
                            cancel: {
                                text: "Yes",
                                visible: true,
                                className: "btn btn-danger swal-btn-yes"
                            },
                            confirm: {
                                text: "No",
                                visible: true,
                                className: "btn btn-success swal-btn-no"
                            }
                        },
                        dangerMode: true,
                        className: 'custom-swal'
                    }).then((confirmed) => {
                        if (!confirmed) {
                            $.ajax({
                                url: '/settlement-sw/save-meter-sale',
                                type: 'POST',
                                data: {
                                    settlement_no: $('#settlement_no').val(),
                                    location_id: $('#location_id').val(),
                                    pump_operator_id: $('#pump_operator_id').val(),
                                    transaction_date: $('#transaction_date').val(),
                                    work_shift: $('#work_shift').val(),
                                    note: $('#note').val(),
                                    pump_id: pump_id,
                                    starting_meter: pump_starting_meter,
                                    closing_meter: $('#pump_closing_meter').val(),
                                    product_id: product_id,
                                    price: price,
                                    qty: sold_qty,
                                    discount: meter_sale_discount,
                                    discount_type: meter_sale_discount_type,
                                    discount_amount: meter_sale_discount_amount,
                                    testing_qty: testing_qty,
                                    sub_total: meter_sale_discount_amount,
                                    is_edit: is_edit,
                                    is_from_pumper: is_from_pumper,
                                    assignment_id: assignment_id,
                                    pumper_entry_id: pumper_entry_id,
                                },
                                success: function(response) {
                                    addPaymentSection();
                                    toastr.success('Meter Sale Added Successfully!');
                                    console.log(response)
                                    // Update total and table
                                    $('#meter_sale_total').val(meter_sale_total);
                                    appendMeterSaleRow(response.data, currencyPrecision);

                                    try {
                                        // const savedPumpId = String(response.data?.pump_id ?? $('#pump_no').val());
                                        // if (savedPumpId && !usedPumps.includes(savedPumpId)) {
                                        //     usedPumps.push(savedPumpId);
                                        //     refreshPumpDropdown();
                                        // }
                                        const savedPumpId = String(response.data?.pump_id ?? $(
                                                '#pump_no')
                                            .val()); +
                                        // remove the selected option from dropdown so it cannot be reused
                                        +removePumpOption(savedPumpId);
                                    } catch (err) {
                                        console.error(err);
                                    }

                                    // Reset form
                                    $('#meter-sales-form')[0].reset();
                                    $('#pump_no').val(null).trigger('change');

                                    saveTempData();
                                },
                                error: function(xhr) {
                                    toastr.error(
                                        'Failed to save meter sale. Please check the fields.');
                                }
                            });
                        }
                    });
                }

                function appendMeterSaleRow(data, currencyPrecision) {
                    $('#meter-sales-tbody').append(`
          <tr data-pump-id="${data?.pump_id}">
              <td>${data?.product?.sku?.slice(-2)}</td>
              <td><span class="product_name">${data?.product?.name}</span></td>
              <td>${data?.pump_no}</td>
              <td>${formatWithCommas(parseFloat(data?.pump_start))}</td>
              <td>${formatWithCommas(parseFloat(data?.pump_close))}</td>
              <td>${formatWithCommas(data?.unit_price)}</td>
              <td><span class="sold_qty">${formatWithCommas(parseFloat(data?.sold_qty, currencyPrecision))}</span></td>
              <td>${data?.discount_type}</td>
              <td>${formatWithCommas(data?.discount_val, currencyPrecision)}</td>
              <td>${formatWithCommas(data?.testing_qty, currencyPrecision)}</td>
              <td>${formatWithCommas(data?.total_qty, currencyPrecision)}</td>
              <td>${formatWithCommas(data?.unit_price * data?.sold_qty, currencyPrecision)}</td>
              <td>${formatWithCommas(data?.after_discount, currencyPrecision)}</td>
              <td>
                  <button class="btn btn-xs btn-danger delete_meter_sale"
                      data-href="/settlement-sw/delete-meter-sale/${data?.meter_sale_id}">
                      <i class="fa fa-times"></i>
                  </button>
              </td>
          </tr>
      `);
                    updateTotalSoldQty();
                    updateMeterSalesTotal();
                    calculate_payment_tab_total();
                    updateBalance();
                }
                /* ------------------------------------------------------------
                 * 4️⃣ ADD PAYMENT SECTION
                 * ------------------------------------------------------------ */
                function addPaymentSection() {
                    const operatorId = $('#pump_operator_id').val();
                    const shiftIds = $('#shift_number').val();

                    if (!operatorId || !shiftIds) return console.warn('Missing operator or shift ID');

                    const url =
                        "{{ route('sw-add-payment.create') }}" +
                        `?settlement_no={{ $settlement_no }}&operator_id=${operatorId}&shift_ids=${shiftIds}&provider=SET_SW&settlement_page=1`;
                    // console.log('hi i am in main card part');

                    $('#add_payment_container').load(url, function(response, status, xhr) {
                        if (status === "error") {
                            console.error("Error loading Add Payment HTML:", xhr.status, xhr.statusText);
                            $(this).html('<p class="text-danger">Failed to load payment section.</p>');
                            return;
                        }

                    });
                }

                $('#shift_number').on('change', function() {
                    addPaymentSection();
                });


                function updateMeterSalesTotal() {
                    let total = 0;
                    $('#meter-sales-tbody tr').each(function() {
                        const afterDiscountText = $(this).find('td:eq(12)').text().trim();
                        const afterDiscount = parseFloat(afterDiscountText.replace(/,/g, ''));
                        total += afterDiscount;
                    });
                    let currencyPrecision2 = {{ $currency_precision }};
                    $('#meter-sales-total').text(formatWithCommasFixed(total, currencyPrecision2));
                    $('#meter_sale_total').val(formatWithCommasFixed(total, currencyPrecision2));
                }

                function updateTotalSoldQty() {
                    const productSoldQty = {};

                    // 🔹 Loop through each meter sale row
                    $('#meter_sale_table tbody tr').each(function() {
                        const productName = $(this).find('.product_name').text().trim();
                        const soldQtyText = $(this).find('span.sold_qty').text().replace(/,/g, '');
                        const soldQty = parseFloat(soldQtyText) || 0;

                        if (soldQty > 0) {
                            productSoldQty[productName] = (productSoldQty[productName] || 0) + soldQty;
                        }
                    });

                    // 🔹 Build product summary text
                    const productSummaryParts = Object.entries(productSoldQty).map(([name, qty]) => {
                        const formattedQty = qty.toLocaleString(undefined, {
                            minimumFractionDigits: 3,
                            maximumFractionDigits: 3
                        });
                        return `${name} = ${formattedQty}`;
                    });

                    // 🔹 Display the summary with line breaks
                    const productSummaryHtml = productSummaryParts.join('<br>');
                    $('.product_summary').html(productSummaryHtml);
                }






                console.log('Temp data:', @json($temp_data));

                // ========================
                // Meter Sales
                // ========================
                @if (!empty($temp_data->meter_sales))
                    let meterSalesRows = @json($temp_data->meter_sales);

                    meterSalesRows.forEach(row => {
                        $('#meter-sales-tbody').append(`
        <tr data-pump-id="${row.pump_id}">
          <td>${row.code ?? ''}</td>
          <td><span class="product_name">${row.product_name ?? ''}</span></td>
          <td>${row.pump_no ?? ''}</td>
          <td>${formatWithCommasFixed(row.starting_meter)}</td>
          <td>${formatWithCommasFixed(row.closing_meter)}</td>
          <td>${formatWithCommasFixed(row.price, currencyPrecision)}</td>
          <td><span class="sold_qty">${formatWithCommasFixed(row.qty, currencyPrecision)}</span></td>
          <td>${row.discount_type ?? ''}</td>
          <td>${formatWithCommasFixed(row.discount, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.testing_qty, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(Number(row.qty) + Number(row.testing_qty), currencyPrecision)}</td>
          <td>${formatWithCommasFixed(Number(row.qty) * Number(row.price), currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.sub_total, currencyPrecision)}</td>
          <td>
            <button class="btn btn-xs btn-danger delete_meter_sale" 
                    data-href="/settlement-sw/delete-meter-sale/${row.id}">
              <i class="fa fa-times"></i>
            </button>
          </td>
        </tr>
      `);
                    });

                    updateTotalSoldQty();
                    updateMeterSalesTotal();
                    calculate_payment_tab_total();
                @endif


                // ========================
                // Other Sales
                // ========================
                @if (!empty($temp_data->other_sales))
                    let otherSalesRows = @json($temp_data->other_sales);

                    otherSalesRows.forEach(row => {
                        $('#other-sales-tbody').append(`
        <tr>
          <td>${row.code}</td>
          <td data-product-id="${row.product_id}">${row.product_name ?? ''}</td>
          <td>${formatWithCommasFixed(row.balance_stock, currencyPrecision2)}</td>
          <td>${formatWithCommasFixed(row.price, currencyPrecision2)}</td>
          <td>${formatWithCommasFixed(row.qty, currencyPrecision2)}</td>
          <td>${capitalizeFirstLetter(row.discount_type)}</td>
          <td>${formatWithCommasFixed(row.discount, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.qty * row.price, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.sub_total, currencyPrecision)}</td>
          <td>
            <button class="btn btn-xs btn-danger delete_other_sale" 
                    data-href="/settlement-sw/delete-other-sale/${row.id}">
              <i class="fa fa-times"></i>
            </button>
          </td>
        </tr>
      `);
                    });

                    updateOtherSalesTotal();
                    calculate_payment_tab_total();
                @endif


                // ========================
                // Expense Rows
                // ========================
                @if (!empty($temp_data->expense_rows))
                    let expenseRows = @json($temp_data->expense_rows);

                    expenseRows.forEach(row => {
                        $("#expense-tbody").prepend(`
        <tr>
          <td>${row.expense_number}</td>
          <td>${row.expense_category_name}</td>
          <td>${row.reference_no}</td>
          <td>${row.expense_account_name}</td>
          <td>${row.expense_reason}</td>
          <td class="sw_expense_amount">${formatWithCommasFixed(row.expense_amount, currencyPrecision)}</td>
          <td>
            <button type="button" class="btn btn-xs btn-danger sw_delete_expense_payment"
                    data-href="${row.delete_url}">
              <i class="fa fa-times"></i>
            </button>
          </td>
        </tr>
      `);

                        calculateTotal("#expense_table_new", ".sw_expense_amount", ".sw_expense_total");
                        calculate_payment_tab_total();
                    });
                @endif


                // ========================
                // Credit Sales
                // ========================
                @if (!empty($temp_data->credit_sales))
                    let creditSalesRows = @json($temp_data->credit_sales);

                    creditSalesRows.forEach(row => {
                        $('#credit-sales-tbody').append(`
        <tr>
          <td>${row.customer_name}</td>
          <td>${formatWithCommasFixed(row.outstanding)}</td>
          <td>${formatWithCommasFixed(row.limit)}</td>
          <td>${row.order_no}</td>
          <td>${row.order_date}</td>
          <td>${row.customer_reference}</td>
          <td>${row.product}</td>
          <td>${formatWithCommasFixed(row.unit_price, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.qty, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.sub_total, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.discount_total, currencyPrecision)}</td>
          <td>${formatWithCommasFixed(row.total, currencyPrecision)}</td>
          <td>
            <a href="#" class="btn-note-view" data-note="${row.note ?? ''}">
              <i class="fa fa-eye"></i> {{ __('messages.view') }}
            </a>
          </td>
          <td>
            <button type="button" class="btn btn-danger btn-sm remove-credit-sale-row">X</button>
          </td>
        </tr>
      `);
                    });

                    updateCreditSaleTotals();
                    calculate_payment_tab_total();
                @endif


                // ========================
                // Other Income
                // ========================
                @if (!empty($temp_data->other_income))
                    let otherIncomeList = @json($temp_data->other_income);

                    otherIncomeList.forEach(row => {
                        $('#other-income-tbody').append(`
        <tr>
          <td>${row.service}</td>
          <td>${row.reason}</td>
          <td>${formatWithCommasFixed(row.qty, currencyPrecision)}</td>
          <td>
            <button type="button" class="btn btn-xs btn-danger sw_delete_other_income"
                    data-href="/settlement-sw/delete-other-income/${row.id}">
              <i class="fa fa-times"></i>
            </button>
          </td>
        </tr>
      `);
                    });

                    updateOtherIncomeTotal();
                    calculate_payment_tab_total();
                @endif


                // ========================
                // Customer Payments from Temp Data
                // ========================
                @if (!empty($temp_data->cust_payments_list))
                    let custPaymentsList = @json($temp_data->cust_payments_list);
                    let total = 0;

                    // Show the table wrapper
                    $('#business-payments-cust-wrapper').removeClass('d-none');

                    custPaymentsList.forEach(row => {
                        total += Number(row.amount);

                        const rowHtml = `
            <tr class="custum-name" data-id="${row.id}">
                <td>${row.customer_name}</td>
                <td>${row.payment_method}</td>
                <td class="custum-val">${formatWithCommasFixed(row.amount, currencyPrecision)}</td>
                <td>
                    <button type="button" class="btn btn-xs btn-danger sw_delete_cust_payment"
                        data-href="/settlement-sw/delete-customer-payment/${row.id}">
                        <i class="fa fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
                        $('#cust-payments-list').append(rowHtml);
                    });

                    // Update totals
                    $('#customer_payment_total').val(total);
                    $('#cust-payments-total').text(formatWithCommasFixed(total, currencyPrecision));

                    calculate_payment_tab_total();
                @endif


            });

            $(document).on('custom:payment_added', (e, payload) => {
                addPaymentSection();
                saveTempData();
            });

            $(document).on('custom:payment_deleted', (e, id) => {
                addPaymentSection();
                deleteCustumePaymentDataId(id);
            });
        </script>

    @endsection
