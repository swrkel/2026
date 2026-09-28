@extends('layouts.'.$layout)
@section('title', __('petropd::lang.payment_summary'))


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
        <h1 class="pull-left">@lang('petropd::lang.payment_summary')</h1>
        <h2 style="color: red; text-align: center;">Shift_NO: {{$shift_number}}</h2>
    </div>
    
    <a href="{{action('Auth\PumpOperatorLoginController@logout')}}" class="btn btn-flat btn-lg pull-right"
    style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petropd::lang.logout')</a>
    <a href="{{action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard')}}"
        class="btn btn-flat btn-lg pull-right"
        style="color: #fff; background-color:#810040;">@lang('petropd::lang.dashboard')
    </a>

</section>
<div class="clearfix"></div>
@include('petropd::pd_operators.partials.payment_summary')


@endsection
@section('javascript')
<script type="text/javascript">
var petropdPaymentSummaryTotalsUrl = "{{ route('petropd.pump-operator-payments.totals') }}";

/* S282-PD-PAYMENT-SUMMARY-TOTALS-021
 * Exact live Payment Summary total updater.
 * The PD Operators page has its own DataTable initializer in index.blade.php.
 * This helper updates the footer from the current draw immediately, then asks
 * the server totals endpoint for the full filtered total. It never leaves the
 * footer at Rs 0.00 when visible rows contain amounts.
 */
function petropdPaymentSummaryParseAmount(value) {
    if (value === null || typeof value === 'undefined') {
        return 0;
    }
    var text = String(value).replace(/<[^>]*>/g, ' ');
    text = text.replace(/Rs\.?/gi, '').replace(/,/g, '').replace(/[^0-9.\-]/g, '');
    var number = parseFloat(text);
    return isNaN(number) ? 0 : number;
}

function petropdPaymentSummaryFormatAmount(amount) {
    amount = parseFloat(amount || 0) || 0;
    if (typeof __currency_trans_from_en === 'function') {
        return __currency_trans_from_en(amount, true);
    }
    return 'Rs ' + amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function petropdPaymentSummarySetFooter(total, breakdown) {
    total = parseFloat(total || 0) || 0;
    breakdown = breakdown || {};

    $('#footer_payment_summary_amount')
        .attr('data-orig-value', total)
        .removeClass('display_currency')
        .text(petropdPaymentSummaryFormatAmount(total));

    var rows = [
        ['Cash', breakdown.Cash || breakdown.cash || 0],
        ['Cards', breakdown.Card || breakdown.Cards || breakdown.card || breakdown.cards || 0],
        ['Credit', breakdown.Credit || breakdown.credit || 0],
        ['Cheques', breakdown.Cheque || breakdown.Cheques || breakdown.cheque || breakdown.cheques || 0],
            ['Shortage', breakdown.Shortage || breakdown.shortage || 0],
            ['Excess', breakdown.Excess || breakdown.excess || 0]
    ];

    var html = [];
    $.each(rows, function (index, item) {
        html.push('<div class="pd-summary-breakdown-row"><strong>' + item[0] + ':</strong> ' + petropdPaymentSummaryFormatAmount(item[1]) + '</div>');
    });
    $('#footer_payment_summary_breakdown').html(html.join(''));
}

function petropdPaymentSummaryVisibleTotals(tableSelector) {
    var total = 0;
    var breakdown = {Cash: 0, Card: 0, Credit: 0, Cheque: 0};
    var $table = $(tableSelector || '#pump_operators_payment_summary_table');

    $table.find('tbody tr').each(function () {
        var $row = $(this);
        if ($row.find('td').length < 2 || $row.find('td.dataTables_empty').length) {
            return;
        }

        var amount = 0;
        var $amount = $row.find('span.amount, span.display_currency.amount').first();
        if ($amount.length) {
            amount = petropdPaymentSummaryParseAmount($amount.attr('data-orig-value') || $amount.text());
        } else {
            $row.find('td').each(function () {
                var possible = petropdPaymentSummaryParseAmount($(this).text());
                if (possible > amount) {
                    amount = possible;
                }
            });
        }

        if (amount <= 0) {
            return;
        }

        var rowText = $row.text().toLowerCase();
        total += amount;
        if (rowText.indexOf('credit') !== -1) {
            breakdown.Credit += amount;
        } else if (rowText.indexOf('cheque') !== -1 || rowText.indexOf('check') !== -1) {
            breakdown.Cheque += amount;
        } else if (rowText.indexOf('card') !== -1) {
            breakdown.Card += amount;
        } else if (rowText.indexOf('cash') !== -1) {
            breakdown.Cash += amount;
        }
    });

    return {total: total, breakdown: breakdown};
}

function petropdPaymentSummaryBuildFilterPayload() {
    var payload = {
        shift_id: $('#payment_summary_shift_id').val(),
        location_id: $('#payment_summary_location_id').val(),
        pump_operator_id: $('#payment_summary_pump_operators').val(),
        payment_method: $('#payment_summary_payment_method').val(),
        customer_id: $('#payment_summary_customer').val(),
        slip_no: $('#payment_summary_slip_no').val(),
        order_no: $('#payment_summary_order_no').val(),
        date_range: $('#payment_summary_date_range').val()
    };

    var drp = $('#payment_summary_date_range').data('daterangepicker');
    if (drp && drp.startDate && drp.endDate) {
        payload.start_date = drp.startDate.format('YYYY-MM-DD');
        payload.end_date = drp.endDate.format('YYYY-MM-DD');
    }

    return payload;
}

function petropdPaymentSummaryRefreshFooter(settings) {
    var json = settings && settings.json ? settings.json : {};
    var visible = petropdPaymentSummaryVisibleTotals('#pump_operators_payment_summary_table');
    var serverTotal = petropdPaymentSummaryParseAmount(json.payment_summary_total || 0);
    var serverBreakdown = json.payment_summary_breakdown || {};

    if (serverTotal > 0) {
        petropdPaymentSummarySetFooter(serverTotal, serverBreakdown);
    } else {
        petropdPaymentSummarySetFooter(visible.total, visible.breakdown);
    }

    var totalsUrl = $('#pump_operators_payment_summary_table').data('totals-url') || (typeof petropdPaymentSummaryTotalsUrl !== 'undefined' ? petropdPaymentSummaryTotalsUrl : '');
    if (totalsUrl) {
        $.ajax({
            url: totalsUrl,
            data: petropdPaymentSummaryBuildFilterPayload(),
            dataType: 'json',
            success: function (response) {
                var ajaxTotal = petropdPaymentSummaryParseAmount(response.payment_summary_total || 0);
                if (ajaxTotal > 0 || visible.total === 0) {
                    petropdPaymentSummarySetFooter(ajaxTotal, response.payment_summary_breakdown || {});
                }
            }
        });
    }
}

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
    aaSorting: [[{{ empty($only_pumper) ? 5 : 4 }}, 'desc'], [{{ empty($only_pumper) ? 1 : 1 }}, 'desc']],
    ajax: {
        url: "{{ url('petropd/pump-operator-payments') }}",
        data: function(d) {
            d.shift_id = $("#payment_summary_shift_id").val();
            d.location_id = $('#payment_summary_location_id').val();
            d.pump_operator_id = $('#payment_summary_pump_operators').val();
            d.payment_method = $('#payment_summary_payment_method').val();
            d.customer_id = $('#payment_summary_customer').val();
            d.slip_no = $('#payment_summary_slip_no').val();
            d.order_no = $('#payment_summary_order_no').val();
            d.date_range = $('#payment_summary_date_range').val();
            d.only_pumper = true;
        },
    },
    autoWidth: true, // PETROPD_PAY_SUM_TABLE_001: allow selected columns to expand
    columnDefs: [{
        "targets": '_all',
        "defaultContent": ""
    }, {
        "targets": 0,
        "orderable": false,
        "searchable": false
    }, {
        "targets": [1],
        "width": "115px",
        "className": "pd-col-date"
    }, {
        "targets": [8],
        "width": "248px",
        "className": "pd-col-customer"
    }, {
        "targets": [9],
        "width": "34px",
        "className": "pd-col-slip text-center"
    }, {
        "targets": [10],
        "width": "65px",
        "className": "pd-col-order text-center"
    }, {
        "targets": [1,2,3,11],
        "className": "pd-col-auto"
    }, {
        "targets": 5,
        "width": "28px",
        "className": "text-center pd-col-shift"
    }, {
        "targets": 7,
        "width": "54px",
        "className": "pd-col-payment-type"
    }, {
        "targets": 13,
        "width": "150px",
        "className": "pd-col-edited-by"
    }],
    columns: [
        { data: 'action', name: 'action' },
        { data: 'date', name: 'date', className: 'pd-col-date' },
        @if(empty($only_pumper))
        { data: 'location_name', name: 'business_locations.name'},
        @endif
        { data: 'time', name: 'time' },
        { data: 'pump_operator_name', name: 'pump_operators.name' },
        { data: 'shift_number', name: 'shift_number' },
        { data: 'collection_form_no', name: 'collection_form_no' },
        { data: 'payment_type', name: 'payment_type' },
        { data: 'customer_name', name: 'customer_name', className: 'pd-col-customer' },
        { data: 'slip_no', name: 'slip_no', className: 'pd-col-slip text-center' },
        { data: 'order_number', name: 'order_number', className: 'pd-col-order text-center' },
        { data: 'amount', name: 'amount', className: 'text-right pd-col-amount' },
        @if(empty($only_pumper))
        { data: 'note', name: 'note', className: 'pd-col-note text-center' },
        { data: 'edited_by', name: 'edited_by' },
        @endif
    ],
    fnDrawCallback: function(oSettings) {
                    petropdPaymentSummaryRefreshFooter(oSettings);
                    __currency_convert_recursively($('#pump_operators_payment_summary_table'));
                },

    });

    $('#pump_operators_payment_summary_table').on('preXhr.dt', function () {
        $('#footer_payment_summary_amount').text('Loading...');
        $('#footer_payment_summary_breakdown').html('');
    });

    
    //  $(document).on('change', '#payment_summary_shift_id', function(){
    //     pump_operators_payment_summary_table.ajax.reload();
    // });
        $('#payment_summary_location_id, #payment_summary_pump_operators, #payment_summary_shift_id, #payment_summary_payment_method, #payment_summary_customer, #payment_summary_slip_no, #payment_summary_order_no, #payment_summary_date_range').on('change keyup', function () {
        pump_operators_payment_summary_table.ajax.reload();
    });
});

</script>

<script type="text/javascript">
/* PETROPD_PAYMENT_SUMMARY_DATE_FAST_018
 * Payment Summary tab may be rendered after the main page script has already run.
 * This initializer makes the Date Range value visible immediately and then binds
 * daterangepicker as soon as the plugin is available. It is safe to run multiple times.
 */
(function ($) {
    'use strict';

    window.petropdInitPaymentSummaryDateRangeFast = function () {
        var $input = $('#payment_summary_date_range');
        if (!$input.length) {
            return false;
        }

        var todayText = (typeof moment !== 'undefined' && typeof moment_date_format !== 'undefined')
            ? moment().format(moment_date_format) + ' ~ ' + moment().format(moment_date_format)
            : $input.val();

        if (!$input.val() || $.trim($input.val()) === '~' || $.trim($input.val()) === '-' || $.trim($input.val()) === '') {
            $input.val(todayText);
        }

        if (!$input.data('petropd-fast-date-visible')) {
            $input.data('petropd-fast-date-visible', true);
            $input.removeClass('date-range-loading').prop('readonly', true);
        }

        if ($.fn.daterangepicker && typeof dateRangeSettings !== 'undefined' && !$input.data('daterangepicker')) {
            $input.daterangepicker(dateRangeSettings, function (start, end) {
                $input.val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                $input.trigger('change');
            });

            $input.on('cancel.daterangepicker.petropdFastDate', function () {
                $input.val('');
                $input.trigger('change');
            });

            $input.data('daterangepicker').setStartDate(moment().startOf('day'));
            $input.data('daterangepicker').setEndDate(moment().endOf('day'));
            $input.val(todayText);
        }

        return true;
    };

    $(document).ready(function () {
        window.petropdInitPaymentSummaryDateRangeFast();

        var attempts = 0;
        var timer = setInterval(function () {
            attempts++;
            if (window.petropdInitPaymentSummaryDateRangeFast() || attempts >= 60) {
                clearInterval(timer);
            }
        }, 100);
    });

    $(document).on('click shown.bs.tab shown.bs.modal', function () {
        setTimeout(window.petropdInitPaymentSummaryDateRangeFast, 10);
        setTimeout(window.petropdInitPaymentSummaryDateRangeFast, 150);
    });
})(jQuery);
</script>

@include('petropd::pd_operators.partials.payment_summary_edit_modal_script')
@endsection


