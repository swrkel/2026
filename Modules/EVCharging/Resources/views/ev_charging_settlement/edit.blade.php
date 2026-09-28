@extends('layouts.app')
@section('title', __('petro::lang.settlement_pd'))

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
                    <h4 class="page-title pull-left">@lang('petro::lang.settlement_pd', ['contacts' => __('petro::lang.settlement_pd')])</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('petro::lang.settlement_pd')</a></li>
                        <li><span>@lang('petro::lang.settlement_pd', ['contacts' => __('petro::lang.settlement_pd')])</span></li>
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
                                {!! Form::label('settlement_no', __('petro::lang.settlement_no') . ':') !!}
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
                                        'placeholder' => __('petro::lang.all'),
                                        'style' => 'width:100%',
                                    ],
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('shift_number', __('petro::lang.shift_number') . ':') !!}
                                {!! Form::text('shift_number', !empty($next_shift_number) ? $next_shift_number : $show_shift_no  ?? '' ?? '', [
                                    'class' => 'form-control',
                                    'id' => 'shift_number',
                                    'readonly' => true,
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            {{-- <div class="form-group">
                                {!! Form::label('pump_operator', __('petro::lang.pump_operator') . ':') !!}
                                {!! Form::select(
                                    'pump_operator_id',
                                    $pump_operators,
                                    !empty($active_settlement) ? $active_settlement->pump_operator_id : null,
                                    [
                                        'class' => 'form-control select2',
                                        'id' => 'pump_operator_id_pd',
                                        'placeholder' => __('petro::lang.all'),
                                        'disabled' => true,
                                    ],
                                ) !!}
                            </div> --}}
                            <div class="form-group">
                                {!! Form::label('pump_operator', __('petro::lang.pump_operator') . ':') !!}
                                {!! Form::text('pump_operator_name', $pump_operator_name ?? '-', [
                                    'class' => 'form-control',
                                    'readonly' => true,
                                ]) !!}
                                      {!! Form::hidden(
    'pump_operator_id',
    !empty($pump_operator_id) ? $pump_operator_id : null,
    ['id' => 'pump_operator_id']
) !!}


                            </div>

                        </div>

                    </div>

                    <input type="hidden" id="is_edit" value="1">
                    <input type="hidden" id="no_change" value="{{ request()->no_change }}">

                    <div class="row">

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('work_shift', __('petro::lang.work_shift') . ':') !!}
                                {!! Form::select(
                                    'work_shift[]',
                                    $wrok_shifts,
                                    !empty($active_settlement) ? $active_settlement->work_shift : null,
                                    ['class' => 'form-control select2', 'id' => 'work_shift', 'multiple'],
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('transaction_date', __('petro::lang.transaction_date') . ':*') !!}
                                {!! Form::text('transaction_date', null, [
                                    'class' => 'form-control transaction_date',
                                    'required',
                                    'placeholder' => __('petro::lang.transaction_date'),
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('note', __('petro::lang.note') . ':') !!}
                                {!! Form::text('note', !empty($active_settlement) ? $active_settlement->note : null, [
                                    'class' => 'form-control note',
                                    'placeholder' => __('petro::lang.note'),
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
                                        <i class="fa fa-tachometer"></i> <strong>@lang('petro::lang.meter_sale')</strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#other_sale_tab" class="other_sale_tab" style="" data-toggle="tab">
                                        <i class="fa fa-balance-scale"></i> <strong>
                                            @lang('petro::lang.other_sale') </strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#other_income_tab" class="other_income_tab" style="" data-toggle="tab">
                                        <i class="fa fa-thermometer"></i> <strong>
                                            @lang('petro::lang.other_income') </strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#customer_payment_tab" class="customer_payment_tab" style=""
                                        data-toggle="tab">
                                        <i class="fa fa-money"></i> <strong>
                                            @lang('petro::lang.customer_payment') </strong>
                                    </a>
                                </li>

                                <li>
                                    <a href="#payment_tab" class="payment_tab" style="" data-toggle="tab">
                                        <i class="fa fa-book"></i> <strong>
                                            @lang('petro::lang.payment') </strong>
                                    </a>
                                </li>

                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="meter_sale_tab">
                                    @include('petro::settlement_pd.partials.meter_sale', ['edit' => 1])
                                </div>

                                <div class="tab-pane" id="other_sale_tab">
                                    @include('petro::settlement_pd.partials.other_sale')
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
                    <h4 class="modal-title">@lang('petro::lang.edit_disabled')</h4>
                </div>

                <!-- Modal Body -->
                <div class="modal-body" style="padding: 50px">
                    <p class="text-bold">@lang('petro::lang.edit_disabled_exp')</p>
                    <p class="text-center">{!! isset($can_edit_details[1]) ? $can_edit_details[1] : '' !!}</p>
                </div>

            </div>
        </div>
    </div>

@endsection
@section('javascript')
    <script src="{{ url('Modules/Petro/Resources/assets/js/app.js?v=34') }}"></script>
    <script src="{{ url('Modules/Petro/Resources/assets/js/payment.js?v=22') }}"></script>
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
    <!-- <script src="https://220.kiria.online/Modules/Petro/Resources/assets/js/app.js?v=29"></script>
        <script src="https://220.kiria.online/Modules/Petro/Resources/assets/js/payment.js?v=22"></script> -->
    <input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">
    <input type="hidden" id="shift_closed" value="{{ !empty($shift_closed) ? $shift_closed : 'yes' }}">

    <script>
        const $shiftNumber = $('#shift_number');
        const $activeSettlement = $('#active_settlement_id');
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
                url: "/petro/settlement/payment/save-credit-sale-payment",
                data: {
                    settlement_no: settlement_no,
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
                        <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petro/settlement/payment/delete-credit-sale-payment/` +
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
            fetchOtherSales();
            loadMeterSalesData();
        });

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
            // $('#meter_sale_table').hide();

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
                url: "/petro/settlement/payment/save-cash-payment",
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
                                <td><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="/petro/settlement/payment/delete-cash-payment/` +
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
                url: "/petro/settlement/payment/save-excess-payment",
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
                            <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petro/settlement/payment/delete-excess-payment/` +
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
                url: "/petro/settlement/payment/save-credit-sale-payment",
                data: {
                    settlement_no: settlement_no,
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
                                <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petro/settlement/payment/delete-credit-sale-payment/` +
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
            $('#note, #work_shift, #transaction_date, #pump_operator_id, #location_id').change(function() {
                $.ajax({
                    method: 'put',
                    url: "{{ action('\Modules\Petro\Http\Controllers\SettlementPDController@update', $active_settlement->id) }}",
                    data: {
                        note: $('#note').val(),
                        work_shift: $('#work_shift').val(),
                        transaction_date: $('#transaction_date').val(),
                        pump_operator_id: $('#pump_operator_id').val(),
                        location_id: $('#location_id').val()
                    },
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        @endif


        $('#card_customer_id').select2();
        $('#work_shift').select2();
        $('#customer_payment_customer_id').select2();
        $('#settlement_print').css('visibility', 'hidden');
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
