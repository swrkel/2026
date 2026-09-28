@extends('layouts.app')

@section('title', __('petrogeneral::lang.daily_status_report'))



@section('content')

<!-- Content Header (Page header) -->

@php
    $business_id = session()->get('user.business_id');
    $business_details = App\Business::find($business_id);
    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
@endphp

<style>

	.daily_report_div table {

		border: 1px solid #222;
		margin-top: 10px;
		margin-bottom: 0px;
	}

	.daily_report_div table.table-bordered>thead>tr>th {
		border: 1px solid #222;
		;
	}

	.daily_report_div table.table-bordered>tbody>tr>td {
		border: 1px solid #222;
		font-size: 13px;
	}

	.daily_reportt_div {
		max-width: 70%;
	}
    
    .daily_report_div .export {
        padding-top: 10px;
        display: flex;
        justify-content: flex-end;
    }

    /* IS2294: the document root is the only vertical scroll owner.
       The former rule made both html and body overflow:auto, while every
       DataTables wrapper also became a vertical scroll container because CSS
       converts overflow-y:visible to auto when overflow-x is auto. Those nested
       scroll owners and native anchoring caused the viewport to move whenever
       an asynchronous report table changed height. */
    html.petrogeneral-daily-status-html {
        height: auto !important;
        min-height: 100% !important;
        max-height: none !important;
        overflow-y: scroll !important;
        overflow-x: hidden !important;
        scroll-behavior: auto !important;
        scrollbar-gutter: stable;
        overflow-anchor: none !important;
    }

    body.petrogeneral-daily-status-page-open {
        height: auto !important;
        min-height: 100% !important;
        max-height: none !important;
        overflow: visible !important;
        scroll-behavior: auto !important;
        overflow-anchor: none !important;
    }

    body.petrogeneral-daily-status-page-open .wrapper,
    body.petrogeneral-daily-status-page-open .content-wrapper,
    body.petrogeneral-daily-status-page-open .right-side,
    body.petrogeneral-daily-status-page-open .main-content,
    body.petrogeneral-daily-status-page-open .content-area {
        height: auto !important;
        min-height: 100vh !important;
        max-height: none !important;
        overflow: visible !important;
        scroll-behavior: auto !important;
        overflow-anchor: none !important;
        transition: none !important;
    }

    .petrogeneral-daily-status-page {
        width: 100%;
        min-height: 100vh;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        padding-bottom: 40px;
        overflow-anchor: none !important;
    }

    .petrogeneral-daily-status-page .daily_report_div {
        width: 100% !important;
        max-width: none !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        overflow-anchor: none !important;
    }

    /* Wide tables may scroll horizontally, but never own vertical scrolling. */
    .petrogeneral-daily-status-page .dataTables_wrapper {
        width: 100%;
        max-width: 100%;
        height: auto !important;
        max-height: none !important;
        overflow-x: auto;
        overflow-y: hidden !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        overflow-anchor: none !important;
        position: relative;
    }

    .petrogeneral-daily-status-page .dataTables_wrapper table,
    .petrogeneral-daily-status-page .dataTables_wrapper thead,
    .petrogeneral-daily-status-page .dataTables_wrapper tbody,
    .petrogeneral-daily-status-page .dataTables_wrapper tfoot,
    .petrogeneral-daily-status-page .dataTables_wrapper tr {
        margin-bottom: 0 !important;
        overflow-anchor: none !important;
    }

    @media (max-width: 767px) {
        .petrogeneral-daily-status-page {
            padding-left: 8px;
            padding-right: 8px;
        }

        .petrogeneral-daily-status-page #product_transaction_report_filter_form {
            width: 100% !important;
        }

        .petrogeneral-daily-status-page .export {
            flex-wrap: wrap;
            gap: 6px;
        }
    }

</style>

<section class="content petrogeneral-daily-status-page">
<div class="container-fluid daily_report_div">
    <div class="export">
        <button class="btn btn-primary" id="print_report" style="margin-right: 10px;">
            <i class="fas fa-print"></i> Print
        </button>    
        <button class="btn btn-primary" id='download_pdf'>
            <i class="fas fa-file-pdf"></i> Download PDF
        </button>
    </div>
    
	<div class="col-xs-12 text-center text-danger">
	    <h2 class="text-center"><strong>@lang('petrogeneral::lang.daily_status_report')</strong></h2>
		<p style="font-size: 22px;" class="text-center"><strong>{{request()->session()->get('business.name')}}</strong>
		</p>
	</div>
	<div class="col-md-12">
	    {!! Form::open(['url' => route('petrogeneral.daily_status.index', [], false), 'method' => 'get', 'id' =>
            'product_transaction_report_filter_form', 'style'=>'margin:0 auto; width:50%' ]) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', @format_date('today') . ' ~ ' . @format_date('today') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'report_date_range', 'readonly']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
            </div>
                
                
          {!! Form::close() !!}
	</div>
    	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.dip_details_section')
	</h3>
	<div class="row">
		<div class="col-md-12">
			<table class="table table-striped" id="dip_details_section" style="width: 100%;">
				<thead>
					<tr class="row-border">
						<th>@lang('petrogeneral::lang.tank_no')</th>
						<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.dip_stick_reading')</th>
						<th>@lang('petrogeneral::lang.qty_in_liters')</th>
						<th>@lang('petrogeneral::lang.qty_in_system')</th>
						<th>@lang('petrogeneral::lang.difference')</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.pump_sales_details')
	</h3>
	<div class="row">
	    <div class="col-md-12">
    		<table class="table table-striped" id="pump_sales_details" style="width: 100%;">
    			<thead>
    				<tr class="row-border">
    					<th>@lang('petrogeneral::lang.pump_no' )</th>
    					<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.previous_day_meter' )</th>
    					<th>@lang('petrogeneral::lang.today_meter' )</th>
    					<th>@lang('petrogeneral::lang.sold_qty_liters')</th>
    					<th>@lang('petrogeneral::lang.amount')</th>
    					<th>@lang('petrogeneral::lang.banked_by_3pm')</th>
    					<th>@lang('petrogeneral::lang.locker')</th>
    					<th>@lang('petrogeneral::lang.card')</th>
    				</tr>
    			</thead>
    		</table>
	    </div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.fuel_sale_summary')
	</h3>
	<div class="row">
		<div class="col-md-6">
			<table class="table table-striped" id="fuel_sale" style="width: 100%;">
    			<thead>
    				<tr class="row-border">
    					<th>@lang('petrogeneral::lang.sub_category' )</th>
    					<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.qty' )</th>
    					<th>@lang('petrogeneral::lang.total_amount' )</th>
    				</tr>
    			</thead>
    		</table>
		</div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.lubricant_sale')
	</h3>
	<div class="row">
		<div class="col-md-12">
			<table class="table table-striped" id="lubricant_sale" style="width: 100%;">
				<thead>
					<tr class="row-border">
						<th>@lang('petrogeneral::lang.product' )</th>
						<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.starting_qty' )</th>
						<th>@lang('petrogeneral::lang.purchase_qty' )</th>
						<th>@lang('petrogeneral::lang.sold_qty' )</th>
						<th>@lang('petrogeneral::lang.amount')</th>
						<th>@lang('petrogeneral::lang.balance_qty')</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.other_sales')
	</h3>
	<div class="row">
		<div class="col-md-12">
			<table class="table table-striped" id="other_sale" style="width: 100%;">
				<thead>
					<tr class="row-border">
						<th>@lang('petrogeneral::lang.product' )</th>
						<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.sold_qty' )</th>
						<th>@lang('petrogeneral::lang.amount')</th>
						<th>@lang('petrogeneral::lang.balance_qty')</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.gas_sales')
	</h3>
	<div class="row">
		<div class="col-md-12">
			<table class="table table-striped" id="gas_sale" style="width: 100%;">
				<thead>
					<tr class="row-border">
						<th>@lang('petrogeneral::lang.product' )</th>
						<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.starting_qty' )</th>
						<th>@lang('petrogeneral::lang.purchase_qty' )</th>
						<th>@lang('petrogeneral::lang.sold_qty' )</th>
						<th>@lang('petrogeneral::lang.amount')</th>
						<th>@lang('petrogeneral::lang.balance_qty')</th>
						<th>@lang('petrogeneral::lang.empty_cylinders')</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	<h3 class="text-danger" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
		@lang('petrogeneral::lang.total_payment_summary')
	</h3>
	<div class="row" id="total_payments">
		<div class="col-md-3" >
		    <h4 class="text-center">@lang('petrogeneral::lang.total_sale')</h4>
		    <p class="text-center total_sale"></p>
		</div>
		<div class="col-md-2">
		    <h4 class="text-center">@lang('petrogeneral::lang.total_card')</h4>
		    <p class="text-center total_card"></p>
		</div>
		
		<div class="col-md-2">
		    <h4 class="text-center">@lang('petrogeneral::lang.total_credit')</h4>
		    <p class="text-center total_credit"></p>
		</div>
		
		<div class="col-md-2">
		    <h4 class="text-center">@lang('petrogeneral::lang.total_bank')</h4>
		    <p class="text-center total_bank"></p>
		</div>
		
		<div class="col-md-3">
		    <h4 class="text-center">@lang('petrogeneral::lang.cash')</h4>
		    <p class="text-center cash"></p>
		</div>
	</div>
	
	<div class="clearfix"></div>
	<br>
	
	<div class="row">
		<div class="col-md-6">
        	<h3 class="text-danger text-center" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
        		@lang('petrogeneral::lang.balance_credit_receipt')
        	</h3>
		</div>
		<div class="col-md-6">
    		 <h3 class="text-danger text-center" style="font-weight: bold; maring-bottom: 0px; font-size: 20px;">
        		@lang('petrogeneral::lang.credit_sales')
        	</h3>
        	<table class="table table-striped table-bordered" id="credit_sales" style="width: 100%;">
				<thead>
					<tr class="row-border">
						<th>@lang('petrogeneral::lang.customer' )</th>
						<th>@lang('petrogeneral::lang.location')</th>
						<th>@lang('petrogeneral::lang.amount' )</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	
    <div class="hide">
        <div id="report_print_div"></div>
    </div>
    
</div>
</section>

@endsection

@section('javascript')

<script type="text/javascript">

    $(document).ready(function(){
        // IS2281: use normal browser scrolling only.
        // Do not capture or restore scrollTop during DataTables AJAX draws.
        // A user can easily scroll between preDraw and draw; restoring an old
        // position at that point was the direct cause of the page moving by itself.
        document.documentElement.classList.add('petrogeneral-daily-status-html');
        $('body').addClass('petrogeneral-daily-status-page-open');

        // Keep DataTables errors inside the affected report section rather than
        // showing a blocking browser alert over the whole Daily Status page.
        // The server still logs the underlying exception for diagnosis.
        if ($.fn.dataTable && $.fn.dataTable.ext) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        $(document).on('error.dt.petrogeneralDailyStatus', '.petrogeneral-daily-status-page table', function(e, settings, techNote, message) {
            if (window.console && console.error) {
                console.error('Petro General Daily Status DataTable error:', message);
            }

            var $table = $(this);
            var columnCount = Math.max($table.find('thead th').length, 1);
            $table.find('tbody').html(
                '<tr class="petrogeneral-report-inline-error">' +
                    '<td colspan="' + columnCount + '" class="text-center text-danger" style="padding:12px;">' +
                        'Unable to load this section. Please refresh the report.' +
                    '</td>' +
                '</tr>'
            );
        });

        function dailyStatusNumber(value) {
            var parsed = parseFloat(String(value === null || value === undefined ? 0 : value).replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }
        
        if ($('#report_date_range').length == 1) {
            $('#report_date_range').daterangepicker(dateRangeSettings, function (start, end) {
                $('#report_date_range').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
            });
            $('#report_date_range').data('daterangepicker').setStartDate(moment().startOf('today'));
            $('#report_date_range').data('daterangepicker').setEndDate(moment().endOf('today'));
        }
        var dateRangeSelector = $('input#report_date_range').data('daterangepicker');
        var dip_details = $('#dip_details_section').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                // IS2288 / IS2291: use the collision-proof named route as a
                // relative URL. This keeps every AJAX request on the tenant
                // host even when APP_URL points at the central installation.
                url: '{{ route('petrogeneral.daily_status.index', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'tank_no', name: 'tank_no'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'dip_reading', name: 'dip_reading' },
                { data: 'qty_liters', name: 'fuel_balance_dip_reading' },
                { data: 'qty_system', name: 'current_qty'},
                { data: 'difference', name: 'difference'},
            ],
            fnDrawCallback: function() {
            }
        });
        var pump_sales = $('#pump_sales_details').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                url: '{{ route('petrogeneral.daily_status.get_pump_sales', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'pump_no', name: 'pump_no'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'previous_meter', name: 'starting_meter' },
                { data: 'today_meter', name: 'closing_meter' },
                { data: 'sold_qty', name: 'sold_qty'},
                { data: 'amount', name: 'amount'},
                { data: 'banked', name: 'banked'},
                { data: 'locker', name: 'locker'},
                { data: 'card', name: 'card'},
            ],
            fnDrawCallback: function() {
                var api = this.api();
                var totalAmount = 0;
                var totalBanked = 0;
                var totalLocker = 0;
                var totalCard = 0;
        
                // Iterate through the visible rows and calculate the total for specific conditions
                api.rows({page: 'current'}).every(function() {
                    var data = this.data();
                    totalAmount += dailyStatusNumber(data.amount);
                    totalBanked += dailyStatusNumber(data.banked);
                    totalLocker += dailyStatusNumber(data.locker);
                    totalCard += dailyStatusNumber(data.card);
                });
                totalAmount = totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                totalBanked = totalBanked.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                totalLocker = totalLocker.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                totalCard = totalCard.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#pump_sales_details .footer-total").remove();
                $("#pump_sales_details").append('<tr class="bg-gray font-17 footer-total text-center"><td>Total</td><td></td><td></td><td></td><td></td><td>'+totalAmount+'</td><td>'+totalBanked+'</td><td>'+totalLocker+'</td><td>'+totalCard+'</td></tr>');
            }
        });
        var lubricant_sale = $('#lubricant_sale').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                url: '{{ route('petrogeneral.daily_status.get_lubricant_sale', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'product', name: 'product'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'starting_qty', name: 'starting_qty' },
                { data: 'purchase_qty', name: 'purchase_qty' },
                { data: 'sold_qty', name: 'sold_qty' },
                { data: 'amount', name: 'amount'},
                { data: 'balance_qty', name: 'balance_qty'},
            ],
            fnDrawCallback: function() {
                var api = this.api();
                var totalAmount = 0;
                api.rows({page: 'current'}).every(function() {
                    var data = this.data();
                    totalAmount += dailyStatusNumber(data.amount);
                });
                totalAmount = totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#lubricant_sale .footer-total").remove();
                $("#lubricant_sale").append('<tr class="bg-gray font-17 footer-total text-center"><td>Total</td><td></td><td></td><td></td><td>'+totalAmount+'</td></tr>');
            }
        });
        var other_sale = $('#other_sale').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                url: '{{ route('petrogeneral.daily_status.get_other_sale', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'product', name: 'product'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'sold_qty', name: 'sold_qty' },
                { data: 'amount', name: 'amount'},
                { data: 'balance_qty', name: 'balance_qty'},
            ],
            fnDrawCallback: function() {
                var api = this.api();
                var totalAmount = 0;
                api.rows({page: 'current'}).every(function() {
                    var data = this.data();
                    totalAmount += dailyStatusNumber(data.amount);
                });
                totalAmount = totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#other_sale .footer-total").remove();
                $("#other_sale").append('<tr class="bg-gray font-17 footer-total text-center"><td>Total</td><td></td><td></td><td>'+totalAmount+'</td><td></td></tr>');
            }
        });
        var fuel_sale = $('#fuel_sale').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                url: '{{ route('petrogeneral.daily_status.get_fuel_sale', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'name', name: 'name'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'qty', name: 'qty' },
                { data: 'value', name: 'value' },
            ],
            fnDrawCallback: function() {
                var api = this.api();
                var totalAmount = 0;
                api.rows({page: 'current'}).every(function() {
                    var data = this.data();
                    totalAmount += dailyStatusNumber(data.value);
                });
                totalAmount = totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#fuel_sale .footer-total").remove();
                $("#fuel_sale").append('<tr class="bg-gray font-17 footer-total text-left"><td>Total</td><td></td><td></td><td>'+totalAmount+'</td></tr>');
            }
        });
       
        var gas_sale = $('#gas_sale').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                url: '{{ route('petrogeneral.daily_status.get_gas_sale', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'product', name: 'product'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'starting_qty', name: 'starting_qty' },
                { data: 'purchase_qty', name: 'purchase_qty' },
                { data: 'sold_qty', name: 'sold_qty' },
                { data: 'amount', name: 'amount'},
                { data: 'balance_qty', name: 'balance_qty'},
                { data: 'empty_cylinders', name: 'empty_cylinders'}
            ],
            fnDrawCallback: function() {
                var api = this.api();
                var totalAmount = 0;
                api.rows({page: 'current'}).every(function() {
                    var data = this.data();
                    totalAmount += dailyStatusNumber(data.amount);
                });
                totalAmount = totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#gas_sale .footer-total").remove();
                $("#gas_sale").append('<tr class="bg-gray font-17 footer-total text-center"><td>Total</td><td></td><td></td><td></td><td>'+totalAmount+'</td></tr>');
            }
        });
        
        var credit_sales = $('#credit_sales').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: 't',
            ajax: {
                url: '{{ route('petrogeneral.daily_status.get_credit_sale', [], false) }}',
                data: function(d) {
                    d.start_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                        
                    d.location_id = $("#location_id").val();
                },
            },
            columnDefs: [ {
                "targets": 0,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'customer', name: 'customer'},
                { data: 'location_name', name: 'business_locations.name'},
                { data: 'amount', name: 'amount' },
            ],
            fnDrawCallback: function() {
                var api = this.api();
                var totalAmount = 0;
                api.rows({page: 'current'}).every(function() {
                    var data = this.data();
                    totalAmount += dailyStatusNumber(data.amount);
                });
                totalAmount = totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#credit_sales .footer-total").remove();
                $("#credit_sales").append('<tr class="bg-gray font-17 footer-total text-center"><td>Total</td><td></td><td>'+totalAmount+'</td></tr>');
            }
        });
        var refreshData = function(init) {
            if (init === 1) {
                dip_details.ajax.reload();
                pump_sales.ajax.reload();
                other_sale.ajax.reload();
                lubricant_sale.ajax.reload();
                fuel_sale.ajax.reload();
                gas_sale.ajax.reload();
                credit_sales.ajax.reload();
            }
            $.ajax({
                method: 'get',
                url: '{{ route('petrogeneral.daily_status.get_total_payments', [], false) }}',
                data: {
                    start_date: $('input#report_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD'),
                    end_date: $('input#report_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD'),
                    location_id: $('#location_id').val(),
                },
                success: function(result) {
                    var cash = dailyStatusNumber(result.total_cash_payments);
                    var card = dailyStatusNumber(result.total_card_payments);
                    var bank = dailyStatusNumber(result.total_cash_deposits);
                    var credit = dailyStatusNumber(result.total_credit_sale_payments);
                    var total = dailyStatusNumber(result.total_sales);
                    cash = cash.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    card = card.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    bank = bank.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    credit = credit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    total = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    $('#total_payments .total_sale').text(total);
                    $('#total_payments .total_card').text(card);
                    $('#total_payments .total_bank').text(bank);
                    $('#total_payments .total_credit').text(credit);
                    $('#total_payments .cash').text(cash);
                },
            });
        };
        refreshData(0);
        // Handle date range change
        $('#report_date_range').change(function() {
            refreshData(1);
        });
        
        $('#location_id').change(function() {
            refreshData(1);
        });
        
        $(document).on('click', '#print_report', function(e){
            $.ajax({
                method: 'get',
                contentType: 'html',
                url: '{{ route('petrogeneral.daily_status.print_report', [], false) }}',
                data: { 
                    start_date: $('input#report_date_range')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD'),
                    end_date: $('input#report_date_range')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD'),
                    location_id: $('#location_id').val(),
                },
                success: function(result) {
                    $('#report_print_div').empty().append(result);
                    $('#report_print_div').printThis();

                },
            });
        });
        
        $(document).on('click', '#download_pdf', function(e){
            $.ajax({
                method: 'get',
                contentType: 'html',
                url: '{{ route('petrogeneral.daily_status.print_report', [], false) }}',
                data: { 
                    start_date: $('input#report_date_range')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD'),
                    end_date: $('input#report_date_range')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD'),
                    location_id: $('#location_id').val(),
                },
                success: function(result) {
                    generatePdf(result,'pdf');

                },
            });
        });
        
        function generatePdf(html,action) {
            console.log(html);
            $.ajax({
                url: '{{ route('petrogeneral.daily_status.download_pdf', [], false) }}',
                method: 'POST',
                data: {
                    html: html
                },
                success: function(data) {
                    // Handle the success response, for example:
                    var downloadUrl = data.path;
                    downloadPdf(downloadUrl);
                },
                error: function(xhr, status, error) {
                    // Handle the error response, for example:
                    alert('An error occurred while generating the PDF.');
                }
            });
            function downloadPdf(file){
                var link = document.createElement('a');
                link.href = file;
                link.download = 'report.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    
    });
</script>

@endsection
