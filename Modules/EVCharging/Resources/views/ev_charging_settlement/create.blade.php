@extends('layouts.app')
@section('title', __('petro::lang.settlement_pd'))

@section('content')
    @php
        $business_id = session('user.business_id');
        $business_details = App\Business::find($business_id);
        $currency_precision = $business_details->currency_precision ?? 2;
        $meeter_precision = 3;

        $asset_vapps = filemtime(public_path('js/app.js'));
        $asset_vpayment = filemtime(public_path('js/payment.js'));
    @endphp

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('petro::lang.petro')</a></li>
                        <li><span>@lang('petro::lang.settlement_pd')</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            body.modal-open {
                height: 100vh;
                overflow-y: hidden;
            }

            .col-md-1-7 {
                flex: 0 0 14.2857% !important;
                max-width: 14.2857% !important;
                padding: 6px 12px !important;
            }

            .select2-selection {
                line-height: 21px !important;
            }
        </style>
    @endpush

    <section class="content main-content-inner">
        @if (!empty($message))
            {!! $message !!}
        @endif

        {{-- Filters --}}
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="row">
                        <div class="col-md-1">
                            <div class="form-group">
                                {!! Form::label('settlement_no', __('petro::lang.settlement_no') . ':') !!}
                                {!! Form::text('settlement_no', $active_settlement->settlement_no ?? $settlement_no, [
                                    'class' => 'form-control',
                                    'readonly',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select(
                                    'location_id',
                                    $business_locations,
                                    $active_settlement->location_id ?? ($default_location ?? null),
                                    [
                                        'class' => 'form-control select2',
                                        'id' => 'location_id',
                                        'placeholder' => __('petro::lang.all'),
                                        'style' => 'width:100%',
                                    ],
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('pump_operator', __('petro::lang.pump_operator') . ':') !!}
                                {!! Form::select('pump_operator_id', $pump_operators, $active_settlement->pump_operator_id ?? null, [
                                    'class' => 'form-control select2',
                                    'id' => 'pump_operator_id',
                                    'disabled' => !empty($select_pump_operator_in_settlement) ? false : true,
                                    'placeholder' => __('petro::lang.please_select'),
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('transaction_date', __('petro::lang.transaction_date') . ':*') !!}
                                {!! Form::text('transaction_date', $active_settlement->transaction_date ?? null, [
                                    'class' => 'form-control transaction_date',
                                    'required',
                                    'placeholder' => __('petro::lang.transaction_date'),
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('work_shift', __('petro::lang.work_shift') . ':') !!}
                                {!! Form::select('work_shift[]', $wrok_shifts, $active_settlement->work_shift ?? [], [
                                    'class' => 'form-control select2',
                                    'id' => 'work_shift',
                                    'multiple',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('shift_number', __('petro::lang.shift_number') . ':') !!}
                                {!! Form::select('shift_number[]', $shift_numbers, null, [
                                    'id' => 'shift_number',
                                    'class' => 'form-control select2',
                                    'multiple' => true,
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="form-group">
                                {!! Form::label('note', __('petro::lang.note') . ':') !!}
                                {!! Form::text('note', $active_settlement->note ?? null, [
                                    'class' => 'form-control note',
                                    'id' => 'note',
                                    'placeholder' => __('petro::lang.note'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        {{-- Widget area with tabs --}}
        @component('components.widget', ['class' => 'box-primary below_box', 'id' => 'below_box'])
            <div class="row">
                <div class="col-md-12">
                    <div class="settlement_tabs">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#meter_sale_tab" class="meter_sale_tab" data-toggle="tab">
                                    <i class="fa fa-tachometer"></i> <strong>@lang('petro::lang.meter_sale')s</strong>
                                </a>
                            </li>
                            <li>
                                <a href="#other_sale_tab" class="other_sale_tab" data-toggle="tab">
                                    <i class="fa fa-balance-scale"></i> <strong>@lang('petro::lang.other_sale')</strong>
                                </a>
                            </li>
                            <li>
                                <a href="#other_income_tab" class="other_income_tab" data-toggle="tab">
                                    <i class="fa fa-thermometer"></i> <strong>@lang('petro::lang.other_income')</strong>
                                </a>
                            </li>
                            <li>
                                <a href="#customer_payment_tab" class="customer_payment_tab" data-toggle="tab">
                                    <i class="fa fa-money"></i> <strong>@lang('petro::lang.customer_payment')</strong>
                                </a>
                            </li>
                            <li>
                                <a href="#payment_tab" class="payment_tab" data-toggle="tab">
                                    <i class="fa fa-book"></i> <strong>@lang('petro::lang.payment')</strong>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane active" id="meter_sale_tab">
                                @include('petro::settlement_pd.partials.meter_sale')
                            </div>

                            <div class="tab-pane" id="other_sale_tab">
                                @include('petro::settlement_pd.partials.other_sale')
                                <input type="hidden" value="{{ $check_qty }}" id="allowoverselling">
                            </div>

                            <div class="tab-pane" id="other_income_tab">
                                @include('petro::settlement_pd.partials.other_income')
                            </div>

                            <div class="tab-pane" id="customer_payment_tab">
                                @include('petro::settlement_pd.partials.customer_payment')
                            </div>

                            <div class="tab-pane" id="payment_tab">
                                @include('petro::settlement_pd.partials.payment')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcomponent

        {{-- Modals --}}
        <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade add_payment" role="dialog" aria-labelledby="gridSystemModalLabel" style="overflow-y: auto;">
        </div>
        <div class="modal fade preview_settlement" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div id="settlement_print"></div>
    </section>
    <!-- /.content -->
@endsection

@section('javascript')
    <script src="{{ url('js/app.js?v=' . $asset_vapps) }}"></script>
    <script src="{{ url('js/payment.js?v=' . $asset_vpayment) }}"></script>
    <script src="{{ url('js/petro_payment.js?v=' . $asset_vapps) }}"></script>

    <input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">
    <input type="hidden" id="shift_closed" value="{{ !empty($shift_closed) ? $shift_closed : 'yes' }}">

    <script>
        /**
         * Optimized Settlement Blade JS
         * - Cached selectors
         * - Helper functions for ajax & datatables
         * - Modern syntax and clearer flow
         */

        (() => {
            // Cached selectors
            let skipUnsettledCheck = false;
            const $doc = $(document);
            const $window = $(window);
            const $note = $('#note');
            const $workShift = $('#work_shift');
            const $transactionDate = $('.transaction_date');
            const $pumpOperator = $('#pump_operator_id');
            const $location = $('#location_id');
            const $shiftNumber = $('#shift_number');
            const $belowBox = $('#below_box');
            const $shiftClosed = $('#shift_closed');
            const $activeSettlement = $('#active_settlement_id');

            const activeSettlementId = $activeSettlement.val();
            const hasActiveSettlement = {!! json_encode(!empty($active_settlement)) !!}; // boolean

            // CSRF setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // ---------- Helpers ----------
            function toastError(msg = 'Enter closing meter greater than the Starting meter') {
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

            // Check previous unsettled (returns Promise<boolean>)
            async function checkPreviousUnsettled(shift_id) {
                try {
                    const res = await apiGet("{{ url('petro/pdsettlement-pd/check-prev-settlement') }}", {
                        shift_id
                    });
                    if (res && res.status) return true;
                    if (res && res.msg) toastError(res.msg);
                    return false;
                } catch (e) {
                    toastError("Something went wrong.");
                    return false;
                }
            }

            // Update pump dropdown options (Select2 aware)
            function updatePumpDropdown(pump_nos = {}) {
                const $select = $('#pump_no');
                $select.empty().append('<option value="">' + "@lang('petro::lang.please_select')" + '</option>');
                $.each(pump_nos, (id, name) => $select.append(`<option value="${id}">${name}</option>`));
                $select.trigger('change.select2');
            }

            // Save small state to localStorage (if no active settlement_pd)
            // function persistLocalUpdate(data = {}) {
            //     if (!hasActiveSettlement) localStorage.setItem('lastUpdateData', JSON.stringify(data));
            // }

            // function persistLocalUpdate(data = {}) {
            //     var pump_operator_id = $('#pump_operator_id').val();
            //     var shift_number = $('#shift_number').val(); // could be array

            //     if (pump_operator_id && Array.isArray(shift_number) && shift_number.length > 0) {
            //         console.log(pump_operator_id, 'with pump', shift_number);
            //         localStorage.setItem('lastUpdateData', JSON.stringify(data));
            //     } else {
            //         console.log('pump_operator_id or shift_number is empty, Do nothing');
            //         // localStorage.removeItem('lastUpdateData');
            //     }
            // }
            let persistTimer;

            function persistLocalUpdate(data = {}) {
                clearTimeout(persistTimer); // clear previous timer if user keeps changing

                persistTimer = setTimeout(() => {
                    var pump_operator_id = $('#pump_operator_id').val();
                    var shift_number = $('#shift_number').val(); // could be string or array

                    // Normalize shift_number to array
                    if (shift_number && !Array.isArray(shift_number)) {
                        shift_number = [shift_number];
                    }

                    if (pump_operator_id && shift_number && shift_number.length > 0) {
                        console.log(pump_operator_id, 'with pump', shift_number);
                        localStorage.setItem('lastUpdateData', JSON.stringify(data));
                    } else {
                        console.log('pump_operator_id or shift_number is empty, removing stored data');
                        // localStorage.removeItem('lastUpdateData'); // remove if invalid
                    }
                }, 5000); // 5-second delay
            }


            // Build data object saved to localStorage
            function buildPersistableData() {
                return {
                    note: $note.val(),
                    work_shift: $workShift.val(),
                    transaction_date: $transactionDate.val(),
                    pump_operator_id: $pumpOperator.val(),
                    location_id: $location.val(),
                    pump_no: $('#pump_no').val(),
                    pump_starting_meter: $('#pump_starting_meter').val(),
                    sold_qty: $('#sold_qty').val(),
                    meter_sale_unit_price: $('#meter_sale_unit_price').val(),
                    testing_qty: $('#testing_qty').val(),
                    meter_sale_discount_type: $('#meter_sale_discount_type').val(),
                    meter_sale_discount: $('#meter_sale_discount').val()
                };
            }

            // Generic DataTable initializer / reload helper
            function initOrReloadDataTable(selector, opts) {
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

            // ---------- Toggle tab behaviour ----------
            function setTabToOtherIncome() {
                $belowBox.addClass('hide');
                $('.settlement_tabs .nav-tabs li, .settlement_tabs .tab-pane').removeClass('active show');
                $('.settlement_tabs .other_income_tab').closest('li').addClass('active show');
                $('#other_income_tab').addClass('active show');
            }

            function toggle_check_operator_shift_status(e) {
                if ($shiftClosed.val() !== "yes") {
                    e.preventDefault();
                    toastError("Operator shift not closed.");
                    setTimeout(setTabToOtherIncome, 1000);
                    return false;
                }
                $belowBox.removeClass('hide');
                return true;
            }

            // Attach tab click handlers (only block when shift_closed isn't 'yes')
            $doc.on('click', '.settlement_tabs .nav-tabs a.meter_sale_tab, .settlement_tabs .nav-tabs a.other_sale_tab',
                function(e) {
                    if ($shiftClosed.val() !== "yes") {
                        return toggle_check_operator_shift_status(e);
                    }
                });

            // ---------- Update settlement_pd (AJAX) ----------
            async function handleFieldChanges() {
                const pumpOperator = $pumpOperator.val();
                const workShift = $workShift.val();
                var shift_number = $('#shift_number').val();

                // If pumpOperator or workShift missing, show meter tab (do not call api)
                if (!pumpOperator || !workShift || pumpOperator === "" || workShift === "") {
                    // $('#meter_sale_tab').addClass('active show');



                    return;
                }
                if (pumpOperator && Array.isArray(shift_number) && shift_number.length > 0) {
                    // persist current fields locally
                    persistLocalUpdate(buildPersistableData());
                } else {
                    console.log(
                        'pump_operator_id or shift_number is empty, removing stored data i am calling in handfile changes function'
                        );
                    //c
                }

                const url = hasActiveSettlement ?
                    "{{ action('\Modules\Petro\Http\Controllers\SettlementPDController@update', $active_settlement->id ?? 0) }}" :
                    "/petro/settlement-pd/" + activeSettlementId;

                try {
                    const result = await apiPut(url, {
                        note: $note.val(),
                        work_shift: workShift,
                        transaction_date: $transactionDate.val(),
                        pump_operator_id: pumpOperator,
                        location_id: $location.val()
                    });

                    if (result && result.success == 1 && result.optionHtml) {
                        toastSuccess(result.msg);
                        $('#shift_number').html(result.optionHtml);
                        // alert($("#shift_number option").length);
                        if ($("#shift_number option").length >= 1) {
                            // alert('in');
                            $("#shift_number option:first").prop("selected", true);
                        }

                        // if ($("#shift_number option").length === 1) {
                        //     alert('in');
                        //     $("#shift_number option:first").prop("selected", true);
                        // }
                        $shiftClosed.val("yes");
                        $belowBox.removeClass('hide');
                        skipUnsettledCheck = true;
                        $('#shift_number').trigger('change');
                        skipUnsettledCheck = false;
                    } else {
                        toastError(result.msg || "Unable to update settlement_pd.");
                        $('#shift_number').empty();
                        $belowBox.addClass('show');
                        $shiftClosed.val("yes");
                        // $('#meter_sale_tab').addClass('active show');
                        $('#outside_meter_sale_table').hide();
                        $('#meter_sale_table').show();
                        fetchOtherSales();
                    }
                } catch (err) {
                    console.error("API Error:", err);
                    toastError("An error occurred while updating settlement_pd.");
                }




            }

            // wire handlers for field changes
            $doc.on('change', '#note, #work_shift, #transaction_date, #pump_operator_id, #location_id',
                handleFieldChanges);

            // persist small changes when there's no active settlement_pd
            // if (!hasActiveSettlement) {
            //     $doc.on('change', '#pump_no, #pump_starting_meter, #sold_qty, #meter_sale_unit_price, #testing_qty, #meter_sale_discount_type, #meter_sale_discount', () =>
            //         persistLocalUpdate(buildPersistableData())
            //     );
            // }
            // let persistTimer;

            $doc.on('change',
                '#pump_no,#pump_operator_id,#pump_starting_meter,#sold_qty,#meter_sale_unit_price,#testing_qty,#meter_sale_discount_type,#meter_sale_discount',
                () => {
                    // clearTimeout(persistTimer); // clear previous timer if user keeps changing

                    // persistTimer = setTimeout(() => {
                    persistLocalUpdate(buildPersistableData());
                    // }, 5000); // 5 seconds delay
                });

            // ---------- Shift number change handler ----------
            $doc.on('change', '#shift_number', async function() {
                const shift_numbers = $(this).val() || [];
                let shift_id = Array.isArray(shift_numbers) ? shift_numbers[0] : shift_numbers;

                if (!shift_id) {
                    $belowBox.addClass('hide');
                    $('#add_payment').prop('disabled', true).addClass('disabled');
                    return;
                }

                // check unsettled previous shifts (skip if triggered programmatically)
                if (!skipUnsettledCheck) {
                    const ok = await checkPreviousUnsettled(shift_id);
                    if (!ok) return;
                }

                // toggle below_box visibility
                $belowBox.toggleClass('hide', shift_numbers.length === 0);
                $('#add_payment').prop('disabled', shift_numbers.length === 0).toggleClass('disabled',
                    shift_numbers.length === 0);

                // build readable label list
                const labels = (shift_numbers || []).map(id => $shiftNumber.find(`option[value="${id}"]`)
                    .text()).filter(Boolean);
                $('.shift_number').html(labels.join(', '));

                if (shift_numbers.length > 0) {
                    loadOtherSalesData();
                    loadMeterSalesData();
                }
            });

            // ---------- Data loading functions ----------
            function loadOtherSalesData() {
                $('#outside_other_sale_table').show();
                $('#other_sale_table').hide();

                initOrReloadDataTable('#pump_operator_other_sale_table', {
                    ajax: {
                        url: "{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}",
                        data: function(d) {
                            console.log($('#shift_number').val());
                            d.shift_ids = $('#shift_number').val();
                        },
                    },
                    columnDefs: [{
                        targets: 0,
                        orderable: false,
                        searchable: false
                    }],
                    columns: [{
                            data: 'product_sku',
                            name: 'products.sku'
                        },
                        {
                            data: 'product_name',
                            name: 'products.name'
                        },
                        {
                            data: 'qty_available',
                            name: 'qty_available',
                            className: 'text-right'
                        },
                        {
                            data: 'price',
                            name: 'price',
                            className: 'text-right'
                        },
                        {
                            data: 'quantity',
                            name: 'quantity',
                            className: 'text-right'
                        },
                        {
                            data: 'discount_type',
                            name: 'discount_type'
                        },
                        {
                            data: 'discount',
                            name: 'discount',
                            className: 'text-right'
                        },
                        {
                            data: 'sub_total',
                            name: 'sub_total',
                            className: 'text-right'
                        },
                        {
                            data: 'with_discount',
                            name: 'with_discount',
                            className: 'text-right'
                        }
                    ],
                    fnDrawCallback() {
                        const total = sum_table_col($('#pump_operator_other_sale_table'), 'with_discount');
                        $('#footer_list_other_sales_amount').val(total).text(total);
                        __currency_convert_recursively($('#pump_operator_other_sale_table'));
                    }
                });

                // fetch totals & pump nos

                fetchOtherSales();
                // apiGet("{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}", {
                //     shift_ids: $shiftNumber.val(),
                //     get_total: true
                // }).done(result => {
                //     if (result && result.success == 1) {
                //         updatePumpDropdown(result.pump_nos || {});
                //         $('#shift_operator_other_sale_total').val(parseFloat(result.total) || 0);
                //         $('#other_sale_total').val(0);
                //         calculate_payment_tab_total();
                //     } else {
                //         toastError("Error fetching other sale total");
                //     }
                // }).fail(() => toastError("Error fetching other sale data"));
            }


            function fetchOtherSales() {
                var new_shift_ids = $('#shift_number').val();
                // console.log(new_shift_ids,'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm');
                if (!new_shift_ids || new_shift_ids.length === 0) {
                    // alert('exit');
                    return; // nothing selected, exit
                }
                apiGet("{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}", {
                    shift_ids: new_shift_ids,
                    get_total: true
                }).done(result => {
                    if (result && result.success == 1) {
                        updatePumpDropdown(result.pump_nos || {});

                        $('#shift_operator_other_sale_total').val(parseFloat(result.total) || 0);
                        $('#other_sale_total').val(0);
                        calculate_payment_tab_total();
                    } else {
                        toastError("Error fetching other sale total");
                    }
                }).fail(() => toastError("Error fetching other sale data"));
            }


            function loadMeterSalesData() {
                $('#outside_meter_sale_table').show();
                $('#meter_sale_table').hide();

                initOrReloadDataTable('#pump_operator_meter_sale_table', {
                    ajax: {
                        url: "{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@meterSalesList') }}",
                        data: d => {
                            d.shift_ids = $shiftNumber.val();
                            d.active_settlement_id = $activeSettlement.val();
                        }
                    },
                    columnDefs: [{
                        targets: 0,
                        orderable: false,
                        searchable: false
                    }],
                    columns: [{
                            data: 'product_sku',
                            name: 'products.sku'
                        },
                        {
                            data: 'product_name',
                            name: 'products.name'
                        },
                        {
                            data: 'pump_name',
                            name: 'pump_name'
                        },
                        {
                            data: 'starting_meter',
                            name: 'starting_meter'
                        },
                        {
                            data: 'closing_meter',
                            name: 'closing_meter'
                        },
                        {
                            data: 'price',
                            name: 'price'
                        },
                        {
                            data: 'quantity',
                            name: 'quantity'
                        },
                        {
                            data: 'discount_type',
                            name: 'discount_type'
                        },
                        {
                            data: 'discount',
                            name: 'discount_value'
                        },
                        {
                            data: 'testing_qty',
                            name: 'testing_qty'
                        },
                        {
                            data: 'total_qty',
                            name: 'total_qty'
                        },
                        {
                            data: 'sub_total',
                            name: 'sub_total'
                        },
                        {
                            data: 'discount_amount',
                            name: 'discount_amount'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            render(data, type, row) {
                                const editButton =
                                    `<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro/settlement-pd/get-meter-sale-form/${row.id}"><i class="fa fa-edit"></i></button>`;
                                const deleteButton = (row.later_settlements < 1 || !row.transaction_id ||
                                        row.bulk_tank == 1) ?
                                    `<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro/settlement-pd/delete-meter-sale/${row.id}"><i class="fa fa-times"></i></button>` :
                                    '';
                                return editButton + ' ' + deleteButton;
                            }
                        }
                    ],
                    fnDrawCallback() {
                        const total = sum_table_col($('#pump_operator_meter_sale_table'), 'sub_total');
                        $('#footer_list_meter_sales_amount').val(total).text(total);

                        // Keep Payments tab totals in sync with the shift-based meter sales table.
                        // Payments summary reads from `#meter_sale_total` via `calculate_payment_tab_total()`.
                        const numericTotal = parseFloat(total) || 0;
                        $('#meter_sale_total').val(numericTotal);
                        if (typeof calculate_payment_tab_total === 'function') {
                            calculate_payment_tab_total();
                        }
                        __currency_convert_recursively($('#pump_operator_meter_sale_table'));

                        // ✅ Get all pump IDs from the table
                        setTimeout(function() {
                            const tableData = $('#pump_operator_meter_sale_table').DataTable().rows()
                                .data();

                            tableData.each(function(row) {
                                const pump_id = row
                                .pump_id; // assuming your AJAX returns `pump_id`
                                console.log(pump_id, 'pump of table');

                                $('#pump_no')
                                    .find('option[value="' + pump_id +
                                    '"]') // added quotes around value
                                    .remove();
                            });
                        }, 3000); // 10000 ms = 10 seconds

                    }
                });
            }


            // ---------- LocalStorage restore ----------
            function restoreLocalData() {
                const lastData = localStorage.getItem('lastUpdateData');
                if (!lastData) return;
                try {
                    const data = JSON.parse(lastData);
                    $note.val(data.note || '');
                    $transactionDate.val(data.transaction_date || '');
                    $location.val(data.location_id || '').trigger('change');
                    $workShift.val(data.work_shift || '').trigger('change');

                    if (data.pump_operator_id) {
                        setTimeout(() => $pumpOperator.val(data.pump_operator_id).trigger('change'), 500);
                    }
                    //  alert('hello');
                    console.log(data.pump_operator_id, 'pump operatro id in restore local data');
                    if (!hasActiveSettlement) {
                        setTimeout(() => {
                            $('#pump_no').val(data.pump_no || '').trigger('change');
                            // $('#pump_starting_meter').val(data.pump_starting_meter || '');
                            // $('#sold_qty').val(data.sold_qty || '');
                            // $('#meter_sale_unit_price').val(data.meter_sale_unit_price || '');
                            // $('#testing_qty').val(data.testing_qty || '');
                            // $('#meter_sale_discount_type').val(data.meter_sale_discount_type || '').trigger('change');
                            // $('#meter_sale_discount').val(data.meter_sale_discount || '');
                        }, 1000);
                    }
                } catch (e) {
                    console.error('Error parsing saved data:', e);
                }

            }

            // ---------- Modal loaders & buttons ----------
            // `#add_payment` is handled by the global `.btn-modal` loader in `public/js/app.js`.

            $doc.on('click', '#payment_review_btn, #product_preview_btn', function() {
                const url = $(this).data('href');
                $('.preview_settlement').load(url, function() {
                    $('.preview_settlement').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                });
            });

            // bulk tank change
            $doc.on('change', '#bulk_tank', function() {
                const tank_id = $(this).val();
                if (!tank_id) return;
                apiGet("{{ action('\Modules\Petro\Http\Controllers\FuelTankController@getTankProduct') }}/" +
                        tank_id)
                    .done(result => {
                        const html =
                            `<option value="">Please Select</option><option value="${result.id}">${result.name}</option>`;
                        $('#item').empty().append(html);
                    })
                    .fail(() => toastError("Unable to fetch tank product"));
            });

            // Show/hide bulk_tank fields
            $doc.on('ifChecked', '#show_bulk_tank', function() {
                $('.store_field').addClass('hide');
                $('.bulk_tank_field').removeClass('hide');
            }).on('ifUnchecked', '#show_bulk_tank', function() {
                $('.store_field').removeClass('hide');
                $('.bulk_tank_field').addClass('hide');
            });

            // Save other income edit
            $doc.on('click', '#save_edit_price_other_income_btn', function() {
                const edit_price = $('#other_income_edit_price').val() || 0;
                $('#other_income_price').val(edit_price);
                $('#other_income_edit_price').val('0');
                $('#edit_price_other_income').modal('hide');
            });

            // ---------- Initialization on ready ----------
            $(function() {
                // datepickers & select2 & datetimepickers initialization
                $('.transaction_date').datepicker("setDate",
                    @if (!empty($active_settlement))
                        "{{ \Carbon::parse($active_settlement->transaction_date)->format('m/d/Y') }}"
                    @else
                        new Date()
                    @endif );
                $('#customer_payment_cheque_date').datepicker("setDate", new Date());
                $('#location_id, #item, #store_id, #bulk_tank, #pump_operator_id, #work_shift, #card_customer_id, #customer_payment_customer_id')
                    .select2();
                $('#shif_time_in, #shif_time_out').datetimepicker({
                    format: 'LT'
                });
                $('#settlement_print').css('visibility', 'hidden');

                // restore local data if any
                restoreLocalData();
            });

        })();
    </script>
@endsection
