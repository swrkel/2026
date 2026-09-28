@extends('layouts.'.$layout)
@section('title', __('petrogeneral::lang.payment_summary'))


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
        <h1 class="pull-left">@lang('petrogeneral::lang.payment_summary')</h1>
        <h2 style="color: red; text-align: center;">Shift_NO: {{$shift_number}}</h2>
    </div>
    
    <a href="{{action('Auth\PumpOperatorLoginController@logout')}}" class="btn btn-flat btn-lg pull-right"
    style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petrogeneral::lang.logout')</a>
    <a href="{{action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@dashboard')}}"
        class="btn btn-flat btn-lg pull-right"
        style="color: #fff; background-color:#810040;">@lang('petrogeneral::lang.dashboard')
    </a>

</section>
<div class="clearfix"></div>
@include('petrogeneral::pump_operators.partials.payment_summary')


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
        url: "{{action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController@index', ['only_pumper' => true])}}",
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
    columnDefs: [{
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
        { data: 'shift_number', name: 'shift_number' },
        { data: 'collection_form_no', name: 'collection_form_no' },
        { data: 'payment_type', name: 'payment_type' },
        { data: 'customer_name', name: 'customer_name' },
        { data: 'slip_no', name: 'slip_no' },
        { data: 'order_number', name: 'order_number' },
        { data: 'amount', name: 'amount' },
        @if(empty($only_pumper))
        { data: 'note', name: 'note' },
        { data: 'edited_by', name: 'edited_by' },
        @endif
    ],
    fnDrawCallback: function(oSettings) {
        var footer_payment_summary_amount = sum_table_col($('#pump_operators_payment_summary_table'), 'amount');
        $('#footer_payment_summary_amount').text(footer_payment_summary_amount);

        __currency_convert_recursively($('#pump_operators_payment_summary_table'));
    },
    });

    $('#pump_operators_payment_summary_table')
    .on('preXhr.dt', function () {
        $('#footer_payment_summary_amount').text(
            __currency_trans_from_en(0, true)
        );
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
