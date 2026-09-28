@extends('layouts.app')
@section('title', __('Purchase Discounts'))

<style>
    #purchase_discounts_table th,
    #purchase_discounts_table td {
        white-space: nowrap;
    }

    .main-footer {
        display: none !important;
    }

    /* Position footer at bottom of normal webpage */
    .reports-footer-content {
        margin-top: 50px;
        padding: 20px 0;
        border-top: 1px solid #ddd;
        text-align: center;
    }

    /* Ensure reports footer is visible during print */
    @media print {
        .reports-footer-content,
        #page_footer.reports-footer-content {
            display: block !important;
            visibility: visible !important;
            page-break-inside: avoid;
            margin-top: 20px;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
    }
</style>


@section('content')
    @php
        $business_id = request()->session()->get('user.business_id');
        $add_purchase = \App\Utils\ModuleUtil::hasThePermissionInSubscription($business_id, 'add_purchase');
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
        $reports_footer_html = !empty($reports_footer->value) ? $reports_footer->value : '';
        $reports_footer_text = trim(preg_replace('/\s+/', ' ', strip_tags($reports_footer_html)));
    @endphp


    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">Purchase Discounts</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('purchase.purchases')</a></li>
                        <li><span>Purchase Discounts</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content main-content-inner no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('purchase_list_filter_location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_order_no', __('purchase.purchase_order_no') . ':') !!}
                        {!! Form::select('purchase_list_order_no', array_combine($ordernos, $ordernos), null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}

                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_supplier_id', __('purchase.supplier') . ':') !!}
                        {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_status', __('purchase.product') . ':') !!}
                        {!! Form::select('purchase_list_filter_status', $products, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <!-- D 81 Added some code here-->
                        {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text(
                            'purchase_list_filter_date_range',
                            @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month'),
                            ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly'],
                        ) !!}
                    </div>
                </div>
            </div>
        @endcomponent

        {{-- Purchase Discount Summary Header --}}
        <div class="row mb-3 align-items-center">
            {{-- LEFT: Total Discount --}}
            <div class="col-md-3 text-left">
                <h5 class="mb-0" style="color: red;">
                    @lang('purchase.total_discount_for_period')
                </h5>
                <h4 style="color: red;">
                    <span id="total_discount_period">0.00</span>
                </h4>
            </div>
            <div class="col-md-6  text-center">
                <h3 class="mb-1">
                    {{ session('business.name') }}
                </h3>
            </div>
            {{-- RIGHT: Total Purchase After Tax --}}
            <div class="col-md-3 text-right">
                <h5 class="mb-0" style="color: red;">
                    @lang('purchase.total_purchase_after_tax')
                </h5>
                <h4 style="color: red;">
                    <span id="total_purchase_after_tax">0.00</span>
                </h4>
            </div>
        </div>
     

        <div class="row">
            <div class="col-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.list_purchase_discount')])
                    @can('purchase.view')
                        @include('purchase.partials.purchase_discounts_table')
                    @endcan
                @endcomponent
            </div>
        </div>


        <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        @include('purchase.partials.update_purchase_status_modal')

       @php
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
    @endphp
    @if (!empty($reports_footer) && !empty($reports_footer->value))
        <div class="row mt-4">
            <div class="col-12 text-center">
                <div id="page_footer" class="reports-footer-content">
                    {!! $reports_footer->value !!}
                </div>
            </div>
        </div>
    @endif

    </section>
    <input type="hidden" id="is_purchase_discounts_page" value="1">

    <section id="receipt_section" class="print_section"></section>

    @if (!empty($reports_footer_html))
        <div id="purchase_discounts_report_footer" class="text-center" style="margin-top: 20px;">
            {!! $reports_footer_html !!}
        </div>
    @endif

    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/purchase.js?v=' . $asset_v + 1) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        let purchase_discounts_table;
    </script>
    <script>
        //Date range as a button
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                if (purchase_discounts_table) {
                    purchase_discounts_table.ajax.reload();
                }
            }
        );
        $('#purchase_list_filter_date_range').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('purchase_list_filter_date_range');
                $('.custom_date_typing_modal').modal('show');
            }
        });


        $('#custom_date_apply_button').on('click', function() {
            debugger;
            if ($('#target_custom_date_input').val() == "purchase_list_filter_date_range") {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                    '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                    '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                    '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                    '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                    '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                    '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);

                    $('#purchase_list_filter_date_range').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#purchase_list_filter_date_range').data('daterangepicker').setStartDate(moment(startDate));
                    $('#purchase_list_filter_date_range').data('daterangepicker').setEndDate(moment(endDate));

                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });


        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            if (purchase_discounts_table) {
                purchase_discounts_table.ajax.reload();
            }
        });
        //D 81 Added the following two line of code
        $('#purchase_list_filter_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
        $('#purchase_list_filter_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));

        $(document).on('click', '.update_status', function(e) {
            e.preventDefault();
            $('#update_purchase_status_form').find('#status').val($(this).data('status'));
            $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
            $('#update_purchase_status_modal').modal('show');
        });

        $(document).on('submit', '#update_purchase_status_form', function(e) {
            e.preventDefault();
            $(this)
                .find('button[type="submit"]')
                .attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        $('#update_purchase_status_modal').modal('hide');
                        toastr.success(result.msg);
                        if (purchase_discounts_table) {
                            purchase_discounts_table.ajax.reload();
                        }
                        $('#update_purchase_status_form')
                            .find('button[type="submit"]')
                            .attr('disabled', false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    </script>

    <script>
        $(function() {
            if ($('#purchase_discounts_table').length) {
                const reportFooterText = @json($reports_footer_text);

                purchase_discounts_table = $('#purchase_discounts_table').DataTable({
                    processing: true,
                    serverSide: true,
                    dom: "<'row mb-3 align-items-center'\
                                            <'col-md-2'l>\
                                            <'col-md-8 d-flex justify-content-center dt-buttons-wrapper'B>\
                                            <'col-md-2 d-flex justify-content-end'f>\>" +
                        "rt" + "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",
                    buttons: [{
                            extend: 'colvis',
                            text: '<i class="fa fa-columns"></i> Column Visibility',
                            className: 'btn btn-xs btn-default',
                            columns: ':not(.notexport)',
                        },
                        {
                            extend: 'pdf',
                            footer: true,
                            text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                            className: 'btn btn-xs btn-default',
                            messageBottom: reportFooterText || null,
                            exportOptions: {
                                columns: ':visible:not(.notexport)',
                            },
                        },
                        {
                            extend: 'print',
                            footer: true,
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-xs btn-default',
                            messageBottom: reportFooterText || null,
                            exportOptions: {
                                columns: ':visible:not(.notexport)',
                            },
                        }
                    ],

                    ajax: {
                        url: "{{ route('purchases.discounts.data') }}",
                        data: function(d) {
                            d.location_id = $('#purchase_list_filter_location_id').val();
                            d.supplier_id = $('#purchase_list_filter_supplier_id').val();
                            d.status = $('#purchase_list_filter_status').val();
                            d.date_range = $('#purchase_list_filter_date_range').val();
                        },
                        dataSrc: function(json) {

                            $('#total_discount_period').html(
                                __currency_trans_from_en(json.total_discount, true)
                            );

                            $('#total_purchase_after_tax').html(
                                __currency_trans_from_en(json.total_purchase_after_tax, true)
                            );

                            let dates = $('#purchase_list_filter_date_range').val().split(' - ');
                            $('#report_from_date').text(dates[0]);
                            $('#report_to_date').text(dates[1]);

                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transaction_date'
                        },
                        {
                            data: 'invoice_no'
                        },
                        {
                            data: 'ref_no'
                        },
                        {
                            data: 'location_name'
                        },
                        {
                            data: 'supplier_name'
                        },
                        {
                            data: 'product_name'
                        },
                        {
                            data: 'discount_amount'
                        },
                        {
                            data: 'final_total'
                        }
                    ],
                    columnDefs: [{
                            width: "6%",
                            targets: 0
                        },
                        {
                            width: "8%",
                            targets: 2
                        },
                        {
                            width: "7%",
                            targets: 3
                        },
                        {
                            width: "8%",
                            targets: 7
                        },
                        {
                            width: "8%",
                            targets: 8
                        },
                        {
                            width: "18%",
                            targets: 5
                        },
                        {
                            width: "20%",
                            targets: 6
                        },
                    ],
                    drawCallback: function(settings) {
                        let api = this.api();

                        //Discount total
                        let discountTotal = api.column(7, {
                                page: 'current'
                            })
                            .data()
                            .reduce((a, b) => {
                                let val = parseFloat($(b).text().replace(/,/g, ''));
                                return a + (isNaN(val) ? 0 : val);
                            }, 0);

                        $('#footer_discount_total').html(
                            __currency_trans_from_en(discountTotal, true)
                        );

                        // Total after tax
                        let totalAfterTax = api.column(8, {
                                page: 'current'
                            })
                            .data()
                            .reduce((a, b) => {
                                let val = parseFloat($(b).text().replace(/,/g, ''));
                                return a + (isNaN(val) ? 0 : val);
                            }, 0);

                        $('#footer_total_after_tax').html(
                            __currency_trans_from_en(totalAfterTax, true)
                        );

                        __currency_convert_recursively($('#purchase_discounts_table'));
                    }

                });


                $('.filter-select').change(function() {
                    if (purchase_discounts_table) {
                        purchase_discounts_table.ajax.reload();
                    }
                });
            }
        });

    </script>

@endsection
