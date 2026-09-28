@extends('layouts.app')
@section('title', !empty($is_purchase_order_list) ? 'List Purchase Order' : __('purchase.purchases'))

@section('content')
<style>
    /*
     * Compact purchase rows while keeping every amount on one line. The table
     * remains fully accessible through horizontal scrolling on smaller screens.
     */
    .purchase-list-table-scroll {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
    }

    #purchase_table_wrapper {
        width: 100% !important;
        max-width: 100% !important;
    }

    #purchase_table {
        width: 100% !important;
        min-width: 1380px !important;
        table-layout: auto !important;
        margin-bottom: 0 !important;
    }

    #purchase_table thead th,
    #purchase_table tfoot td {
        vertical-align: middle !important;
        padding: 5px 4px !important;
        font-size: 10.5px;
        line-height: 1.15;
    }

    #purchase_table tbody tr {
        height: auto !important;
        min-height: 0 !important;
    }

    #purchase_table tbody td {
        vertical-align: middle !important;
        padding: 3px 4px !important;
        font-size: 10.5px;
        line-height: 1.1 !important;
        height: auto !important;
        min-height: 0 !important;
        overflow-wrap: normal;
        word-break: normal;
    }

    #purchase_table tbody .purchase-amount-cell,
    #purchase_table tbody .purchase-amount-cell *,
    #purchase_table thead .purchase-amount-heading,
    #purchase_table tfoot .display_currency {
        white-space: nowrap !important;
        word-break: keep-all !important;
        overflow-wrap: normal !important;
    }

    #purchase_table tbody .purchase-amount-cell {
        text-align: right !important;
    }

    #purchase_table tbody .purchase-due-cell br {
        display: block;
        content: "";
        margin-top: 2px;
    }

    #purchase_table tbody .purchase-action-cell,
    #purchase_table tbody .purchase-code-cell,
    #purchase_table tbody .purchase-status-cell {
        white-space: nowrap !important;
    }

    #purchase_table tbody .purchase-location-cell,
    #purchase_table tbody .purchase-supplier-cell,
    #purchase_table tbody .purchase-added-by-cell {
        white-space: normal !important;
        overflow-wrap: break-word;
    }

    #purchase_table tbody .btn,
    #purchase_table tbody .label,
    #purchase_table tbody .badge {
        white-space: nowrap !important;
        padding-top: 2px;
        padding-bottom: 2px;
        line-height: 1.05;
    }

    #purchase_table tbody .purchase-action-cell .btn {
        min-height: 24px;
        padding: 3px 6px !important;
        font-size: 10px;
    }

    #purchase_table tbody .purchase-date-cell .badge {
        display: inline-block;
        margin-top: 2px;
        font-size: 9px;
    }

    /* Purchase list uses its own filter toggle instead of Bootstrap collapse.
       Both Bootstrap 3 and 4 are loaded globally, so data-toggle="collapse" can
       fire twice and immediately hide the filters again. */
    .purchase-list-filter-panel {
        margin-bottom: 10px;
        background: #fff;
        border: 1px solid #e6ebf1;
        border-radius: 6px;
        overflow: visible;
    }

    .purchase-list-filter-toggle {
        width: 100%;
        min-height: 42px;
        padding: 8px 14px;
        border: 0;
        border-radius: 6px 6px 0 0;
        background: #f5f8fb;
        color: #263238;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
        font-weight: 600;
        cursor: pointer;
    }

    .purchase-list-filter-toggle:focus {
        outline: 2px solid rgba(37, 150, 190, 0.25);
        outline-offset: -2px;
    }

    .purchase-list-filter-body {
        display: block;
        padding: 14px 14px 4px;
    }

    .purchase-list-filter-body .row {
        margin-left: -15px;
        margin-right: -15px;
    }

    .purchase-list-filter-chevron {
        transition: transform 0.15s ease;
    }

    .purchase-list-filter-toggle[aria-expanded="false"] .purchase-list-filter-chevron {
        transform: rotate(180deg);
    }
</style>
@php 
$business_id = request()->session()->get('user.business_id');
$add_purchase = \App\Utils\ModuleUtil::hasThePermissionInSubscription($business_id, 'add_purchase');
@endphp


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">
                    @if (!empty($is_purchase_order_list))
                        List Purchase Order
                    @else
                        List @lang('purchase.purchases')
                    @endif
                </h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('purchase.purchases')</a></li>
                    <li>
                        <span>
                            @if (!empty($is_purchase_order_list))
                                List Purchase Order
                            @else
                                List @lang('purchase.purchases')
                            @endif
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner no-print">
    <div class="purchase-list-filter-panel" id="purchase_list_filter_panel">
        <button type="button"
                class="purchase-list-filter-toggle"
                id="purchase_list_filter_toggle"
                aria-expanded="true"
                aria-controls="purchase_list_filter_body">
            <span><i class="fa fa-filter" aria-hidden="true"></i> {{ __('report.filters') }}</span>
            <i class="fa fa-chevron-up purchase-list-filter-chevron" aria-hidden="true"></i>
        </button>
        <div class="purchase-list-filter-body" id="purchase_list_filter_body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('purchase_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_order_no',  __('purchase.purchase_order_no') . ':') !!}
                        {!! Form::select('purchase_list_order_no', array_combine($ordernos, $ordernos), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_supplier_id',  __('purchase.supplier') . ':') !!}
                        {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_status',  __('purchase.purchase_status') . ':') !!}
                        {!! Form::select('purchase_list_filter_status', $orderStatuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_payment_status',  __('purchase.payment_status') . ':') !!}
                        {!! Form::select('purchase_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('purchase_list_filter_date_range',  @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month')  , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.all_purchases')])
        @if($add_purchase == 1)
            @slot('tool')
                <div class="row">
                    <div class="box-tools pull-right">
                        @if (!empty($is_purchase_order_list))
                            <a class="btn btn-primary" href="{{ action('PurchaseController@addPurchaseOrder') }}">
                                <i class="fa fa-plus"></i> @lang('purchase.purchase_order')
                            </a>
                        @else
                            <a class="btn btn-primary" href="{{ action('PurchaseController@create') }}">
                                <i class="fa fa-plus"></i> @lang('messages.add')
                            </a>
                        @endif
                    </div>
                </div>
                <hr>
                
            @endslot
        @endif
        @can('purchase.view')
            @include('purchase.partials.purchase_table')
        @endcan
    @endcomponent

    <div class="modal fade product_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    @include('purchase.partials.update_purchase_status_modal')
<input type="hidden" id="just_saved_purchase_id" value="{{ request()->get('just_saved_purchase_id') }}">
<input type="hidden" id="purchase_list_url" value="{{ !empty($is_purchase_order_list) ? action('PurchaseController@listPurchaseOrders') : action('PurchaseController@index') }}">

</section>

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/purchase.js') }}?v={{ ($asset_v ?? 1) + 3 }}"></script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('payment.js?v=' . $asset_v) }}"></script>
<script>
    $(function () {
        var $filterToggle = $('#purchase_list_filter_toggle');
        var $filterBody = $('#purchase_list_filter_body');

        // Do not use Bootstrap's collapse data API here. The global layout loads
        // Bootstrap 4 and Bootstrap 3, which can process one click twice.
        $filterToggle.off('click.purchaseListFilters').on('click.purchaseListFilters', function (event) {
            event.preventDefault();

            var isOpen = $filterToggle.attr('aria-expanded') === 'true';
            $filterToggle.attr('aria-expanded', isOpen ? 'false' : 'true');

            $filterBody.stop(true, true)[isOpen ? 'slideUp' : 'slideDown'](150, function () {
                if (!isOpen && $.fn.dataTable && $.fn.dataTable.tables) {
                    $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
                }
            });
        });
    });

        // Show success or error message after redirect from add/edit purchase
        @if (session('status'))
            $(document).ready(function() {
                var status = {{ json_encode(session('status.success')) }};
                var msg = {!! json_encode(session('status.msg')) !!};
                if (status == 1 || status === true) {
                    toastr.success(msg);
                } else if (msg) {
                    toastr.error(msg);
                }
            });
        @endif

        //Date range as a button
    $('#purchase_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
           purchase_table.ajax.reload();
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
        if($('#target_custom_date_input').val() == "purchase_list_filter_date_range"){
            let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
            let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

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
        purchase_table.ajax.reload();
    });
    //D 81 Added the following two line of code
	$('#purchase_list_filter_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
	$('#purchase_list_filter_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));

    $(document).on('click', '.update_status', function(e){
        e.preventDefault();
        $('#update_purchase_status_form').find('#status').val($(this).data('status'));
        $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
        $('#update_purchase_status_modal').modal('show');
    });

    $(document).on('submit', '#update_purchase_status_form', function(e){
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
                    purchase_table.ajax.reload();
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
	
@endsection
