@extends('layouts.'.$layout)
@section('title', __('pumperdashboard::lang.payment_summary'))


<style>
    #pump_operators_payment_summary_table td, 
#pump_operators_payment_summary_table th {
    padding: 5px 8px; /* reduce default padding */
    white-space: nowrap; /* avoid wrapping if needed */
}

    </style>
@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    
    <div class="col-md-12">
        <h1 class="pull-left">@lang('pumperdashboard::lang.payment_summary')</h1>
        <h2 style="color: red; text-align: center;">Shift_NO: {{$shift_number}}</h2>
    </div>
    
    <a href="{{action('Auth\PumpOperatorLoginController@logout')}}" class="btn btn-flat btn-lg pull-right"
    style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('pumperdashboard::lang.logout')</a>
    <a href="{{action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard')}}"
        class="btn btn-flat btn-lg pull-right"
        style="color: #fff; background-color:#810040;">@lang('pumperdashboard::lang.dashboard')
    </a>

</section>
<div class="clearfix"></div>
@include('pumperdashboard::partials.payment_summary')


@endsection
@section('javascript')
<script type="text/javascript">
    var body = document.getElementsByTagName("body")[0];
    body.className += " sidebar-collapse";
    
if ($('#payment_summary_date_range').length == 1) {
    $('#payment_summary_date_range').daterangepicker(dateRangeSettings, function(start, end) {
        $('#payment_summary_date_range').val(
            start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
        );
    });
    $('#payment_summary_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#payment_summary_date_range').val('');
    });
    $('#payment_summary_date_range')
        .data('daterangepicker')
        .setStartDate(moment().startOf('day'));
    $('#payment_summary_date_range')
        .data('daterangepicker')
        .setEndDate(moment().endOf('day'));
}
$(document).ready( function(){
    pump_operators_payment_summary_table = $('#pump_operators_payment_summary_table').DataTable({
           processing: true,
    serverSide: true,
    aaSorting: [[0, 'desc']],
    ajax: {
        url: "{{action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@index', ['only_pumper' => true])}}",
        data: function(d) {
            d.shift_id = $("#payment_summary_shift_id").val();
            d.location_id = $('#payment_summary_location_id').val();
            d.pump_operator_id = $('#payment_summary_pump_operators').val();
            d.payment_method = $('#payment_summary_payment_method').val();
            d.customer_id = $('#payment_summary_customer').val();
            d.slip_no = $('#payment_summary_slip_no').val();
            d.order_no = $('#payment_summary_order_no').val();
            d.date_range = $('#payment_summary_date_range').val();
        },
    },
    autoWidth: false, // <-- important
    scrollX: true,
    scrollCollapse: true,
    columnDefs: [{
        "targets": '_all',
        "defaultContent": ""
    }, {
        "targets": 0,
        "orderable": false,
        "searchable": false
    }],
    columns: [
        { data: 'action', name: 'action' },
        { data: 'date', name: 'date' },
        { data: 'location_name', name: 'business_locations.name'},
        { data: 'time', name: 'time' },
        { data: 'pump_operator_name', name: 'pump_operators.name' },
        { data: 'shift_number', name: 'pump_operator_payments.shift_id' },
        { data: 'collection_form_no', name: 'collection_form_no' },
        { data: 'payment_type', name: 'payment_type' },
        { data: 'customer_name', name: 'customer_name' },
        { data: 'slip_no', name: 'slip_no' },
        { data: 'order_number', name: 'order_number' },
        { data: 'amount', name: 'pump_operator_payments.payment_amount' },
        @if(empty($only_pumper))
        { data: 'note', name: 'note' },
        { data: 'edited_by', name: 'edited_by' },
        @endif
    ],
    fnDrawCallback: function(oSettings) {
        // S281-005: show page total and payment-type breakdown for the currently displayed page.
        // This avoids the old issue where the footer stayed Rs 0.00 even when rows were visible.
        var api = this.api();
        var pageTotal = 0;
        var breakdown = {};

        function parsePaymentAmount(value) {
            if (value === null || typeof value === 'undefined') {
                return 0;
            }

            var raw = $('<div>').html(value).text();
            raw = (raw || value.toString()).replace(/[^0-9.\-]/g, '');
            var parsed = parseFloat(raw);
            return isNaN(parsed) ? 0 : parsed;
        }

        function normalizePaymentType(value) {
            var raw = $('<div>').html(value || '').text().trim().toLowerCase();
            if (raw === 'card' || raw === 'cards' || raw === 'credit card' || raw === 'credit cards') {
                return 'Cards';
            }
            if (raw === 'cash') {
                return 'Cash';
            }
            if (raw === 'credit' || raw === 'credit sale' || raw === 'credit sales') {
                return 'Credit Sales';
            }
            if (raw === 'cheque' || raw === 'cheques' || raw === 'cheque sales') {
                return 'Cheques';
            }
            return raw ? raw.replace(/\b\w/g, function(c) { return c.toUpperCase(); }) : 'Other';
        }

        api.rows({ page: 'current' }).every(function() {
            var row = this.data() || {};
            var amount = parsePaymentAmount(row.amount);
            var type = normalizePaymentType(row.payment_type);

            pageTotal += amount;
            breakdown[type] = (breakdown[type] || 0) + amount;
        });

        var json = oSettings && oSettings.json ? oSettings.json : {};
        var serverTotal = parsePaymentAmount(json.payment_summary_total || 0);
        var serverBreakdown = json.payment_summary_breakdown || {};
        var footerTotal = serverTotal > 0 ? serverTotal : pageTotal;

        $('[id=footer_payment_summary_amount]')
            .attr('data-orig-value', footerTotal)
            .text(__currency_trans_from_en(footerTotal, true));

        if (serverTotal > 0 && serverBreakdown && Object.keys(serverBreakdown).length) {
            breakdown = {};
            Object.keys(serverBreakdown).forEach(function(key) {
                breakdown[normalizePaymentType(key)] = parsePaymentAmount(serverBreakdown[key]);
            });
        }

        var order = ['Cash', 'Cards', 'Credit Sales', 'Cheques', 'Other'];
        var html = '';
        order.forEach(function(type) {
            if (breakdown[type]) {
                html += '<div class="payment-summary-breakdown-line"><span>' + type + ':</span><strong>' + __currency_trans_from_en(breakdown[type], true) + '</strong></div>';
            }
        });
        Object.keys(breakdown).sort().forEach(function(type) {
            if (order.indexOf(type) === -1 && breakdown[type]) {
                html += '<div class="payment-summary-breakdown-line"><span>' + type + ':</span><strong>' + __currency_trans_from_en(breakdown[type], true) + '</strong></div>';
            }
        });

        $('[id=payment_summary_breakdown_container]').html(html || '<span class="text-muted">-</span>');
        __currency_convert_recursively($('#pump_operators_payment_summary_table'));
    },
    });

    $('#pump_operators_payment_summary_table')
    .on('preXhr.dt', function () {
        $('[id=footer_payment_summary_amount]').text(__currency_trans_from_en(0, true));
        $('[id=payment_summary_breakdown_container]').html('<span class="text-muted">Loading...</span>');
    });

    
    //  $(document).on('change', '#payment_summary_shift_id', function(){
    //     pump_operators_payment_summary_table.ajax.reload();
    // });
        $('.select2, #payment_summary_slip_no, #payment_summary_order_no, #payment_summary_date_range').on('change keyup', function () {
        pump_operators_payment_summary_table.ajax.reload();
    });
});

</script>
@endsection





