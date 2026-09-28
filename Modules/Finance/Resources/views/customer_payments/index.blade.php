@extends('layouts.app')
@section('title', __('lang_v1.customer_payments'))
@section('content')


@php
    $business_id = request()
        ->session()
        ->get('user.business_id');

    $pacakge_details = [];

    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }

    $show_customer_payment_simple = !empty($pacakge_details['customer_payment_simple']);
    $show_customer_payment_bulk = !empty($pacakge_details['customer_payment_bulk']);
    $show_list_customer_payments = !empty($pacakge_details['list_customer_payments']);
    $show_customer_interest = !empty($pacakge_details['customer_interest']);
    $show_interest_settings = !empty($pacakge_details['interest_settings']);

    // Fallback: if package flags are missing or all disabled, still show the main customer payments list.
    $has_visible_customer_payment_tab =
        $show_customer_payment_simple ||
        $show_customer_payment_bulk ||
        $show_list_customer_payments ||
        $show_customer_interest ||
        $show_interest_settings;

    if (!$has_visible_customer_payment_tab) {
        $show_list_customer_payments = true;
    }

@endphp
   
    
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('lang_v1.customer_payments')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('lang_v1.customer_payments')</a></li>
                        <li><span></span>@lang( 'contact.manage_your_contact', ['contacts' => __('lang_v1.customer_payments') ])</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <section class="content main-content-inner">
        <div class="settlement_tabs">
            <ul class="nav nav-tabs">
                @if($show_customer_payment_simple)
                    <li class=" @if(empty(session('status.tab'))) active @endif">
                        <a href="#customer_payment_simple" data-toggle="tab">
                            <i class="fa fa-money"></i> <strong>@lang('lang_v1.customer_payment_simple')</strong>
                        </a>
                    </li>
                @endif
                
                @if($show_customer_payment_bulk)
                    <li class=" @if(session('status.tab') == 'bulk') active @endif @if(!$show_customer_payment_simple) active @endif">
                        <a href="#customer_payment_bulk" data-toggle="tab">
                            <i class="fa fa-money"></i> <strong>
                                @lang('lang_v1.customer_payment_bulk') </strong>
                        </a>
                    </li>
                @endif
                
                @if($show_list_customer_payments)
                    <li class=" @if(session('status.tab') == 'customer-payments' || !$has_visible_customer_payment_tab) active @endif">
                        <a href="#customer_payments" data-toggle="tab">
                            <i class="fa fa-list"></i> <strong>
                                @lang('lang_v1.customer_payments_list') </strong>
                        </a>
                    </li>
                @endif
                
                @if($show_customer_interest)
                    <li class=" @if(session('status.tab') == 'customer-interest') active @endif">
                        <a href="#customer_interest" data-toggle="tab">
                            <i class="fa fa-list"></i> <strong>
                                @lang('lang_v1.customer_interest_list') </strong>
                        </a>
                    </li>
                @endif
                
                @if($show_interest_settings)
                    <li class=" @if(session('status.tab') == 'interest-settings') active @endif">
                        <a href="#interest_settings" data-toggle="tab">
                            <i class="fa fa-list"></i> <strong>
                                @lang('lang_v1.interest_settings') </strong>
                        </a>
                    </li>
                @endif
            </ul>
            <div class="tab-content">
                @if($show_customer_payment_simple)
                    <div class="tab-pane @if(empty(session('status.tab'))) active @endif" id="customer_payment_simple">
                        @include('customer_payments.customer_payment_simple')
                    </div>
                @endif
                @if($show_customer_payment_bulk)
                    <div class="tab-pane @if(session('status.tab') == 'bulk') active @endif @if(!$show_customer_payment_simple) active @endif" id="customer_payment_bulk">
                        @include('customer_payments.customer_payment_bulk')
                    </div>
                @endif
                @if($show_list_customer_payments)
                    <div class="tab-pane @if(session('status.tab') == 'customer-payments' || !$has_visible_customer_payment_tab) active @endif"
                         id="customer_payments">
                        @include('customer_payments.customer_payments')
                    </div>
                @endif
                @if($show_customer_interest)
                    <div class="tab-pane @if(session('status.tab') == 'customer-interest') active @endif"
                         id="customer_interest">
                        @include('customer_payments.customer_interest')
                    </div>
                @endif
                @if($show_interest_settings)
                    <div class="tab-pane @if(session('status.tab') == 'interest-settings') active @endif"
                         id="interest_settings">
                        @include('customer_payments.interest_settings')
                    </div>
                @endif
            </div>
        </div>
    </section>
    <!-- /.content -->
<style>
  /* CUSTOMER PAYMENTS TAB BUTTON STANDARD - 2026-07-07
     Inactive tabs: white text. Active/clicked tab: white background + black text.
     Kept inside this page also because some deployed layouts load module CSS before the global UI CSS. */
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:visited,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a strong,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a i {
      color: #ffffff !important;
  }

  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:hover,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:focus,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:hover strong,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:focus strong,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:hover i,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li > a:focus i {
      color: #ffffff !important;
  }

  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a:hover,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a:focus {
      background: #ffffff !important;
      color: #000000 !important;
      border-color: #d9e2ef !important;
      box-shadow: 0 2px 8px rgba(0,0,0,.08) !important;
  }

  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a strong,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a i,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a:hover strong,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a:hover i,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a:focus strong,
  .content.main-content-inner .settlement_tabs > ul.nav.nav-tabs > li.active > a:focus i {
      color: #000000 !important;
  }
</style>
@endsection
@section('javascript')
    @if(session('status'))
        @if(!session('status')['success'])
            <script>
                toastr.error('{{ session("status")["msg"] }}');
            </script>
        @endif
    @endif
    <script>
        var body = document.getElementsByTagName("body")[0];
        body.className += " sidebar-collapse";
    </script>
    <script>

    $('#customer_payment_bulk_payment_method').change(function () {
        group_id = $(this).val();
        let row = $(this).closest('.payment_data_row');
        let selectedText = ($(this).find('option:selected').text() || '').toLowerCase();

        let isCash = selectedText.indexOf('cash') !== -1;
        let isCard = selectedText.indexOf('card') !== -1;
        let isCheque = selectedText.indexOf('cheque') !== -1;
        let isBank = selectedText.indexOf('bank') !== -1;

        row.find('.cash_divs').toggleClass('hide', !isCash);
        row.find('.card_divs').toggleClass('hide', !isCard);
        row.find('.cheque_divs').toggleClass('hide', !(isCheque || isBank));
        row.find('.bank_name_div').toggleClass('hide', !isCheque);
        row.find('.post_dated_cheque').toggleClass('hide', !isCheque);
        row.find('.update_post_dated_cheque').toggleClass('hide', !isCheque);

        $.ajax({
            method: 'get',
            url: '/finance/get-account-by-group-id/'+group_id,
            data: {  },
            contentType: 'html',
            success: function(result) {
                $('#customer_payment_bulk_accounting_module').empty().append(result);
            },
        });
    })


        $('#customer_payment_simple_customer_id').change(function () {
            $.ajax({
                method: 'get',
                url: '/petro/settlement/payment/get-customer-details/' + $(this).val(),
                data: {},
                success: function (result) {
                    __write_number($('#customer_simple_balance'), result.total_outstanding);
                },
            });
        });
        if($('#customer_payment_simple_customer_id').val()){
            $('#customer_payment_simple_customer_id').trigger('change');
        }

        //customer_payment simple tab script
        $('.cheque_date').datepicker('setDate', new Date());
        $('.transaction_date').datepicker('setDate', new Date());
       var customer_payment_simple_total_val = $('#customer_payment_simple_total').val();
       var customer_payment_simple_total = 0;

if (customer_payment_simple_total_val) {
    customer_payment_simple_total = parseFloat(customer_payment_simple_total_val.toString().replace(/,/g, '')) || 0;
}



        var sub_total = 0.0;
        $('.btn_customer_payment').click(function () {
            var amount = parseFloat($('#amount').val());
            var customer_name = $('#customer_payment_simple_customer_id :selected').text();
            var customer_id = $('#customer_payment_simple_customer_id').val();
            var payment_method = $('#customer_payment_simple_payment_method').val();
            var bank_name = $('#customer_payment_simple_bank_name').val();
            var payment_ref_no = 'CPS-' + $('#customer_payment_simple_payment_ref_no').val();
            if (payment_method == 'cheque') {
                var cheque_date = $('#customer_payment_simple_cheque_date').val();
            } else {
                var cheque_date = '';
            }
            var cheque_number = $('#customer_payment_simple_cheque_number').val();
            var customer_payment_simple_id = null;
            let row_index = parseInt($('#row_index').val());
            let customer_payment_simple_total_val = $('#customer_payment_simple_total').val();
            let customer_payment_simple_total = 0;
            if (customer_payment_simple_total_val) {
                customer_payment_simple_total = parseFloat(customer_payment_simple_total_val.toString().replace(',', '')) || 0;
            }
            customer_payment_simple_total = customer_payment_simple_total + amount;
            $('#customer_payment_simple_total').val(customer_payment_simple_total);
            $('.row_data').append(`
                <input type="hidden" name="payment[${row_index}][payment_ref_no]" value="${payment_ref_no}">
                <input type="hidden" name="payment[${row_index}][contact_id]" value="${customer_id}">
                <input type="hidden" name="payment[${row_index}][method]" value="${payment_method}">
                <input type="hidden" name="payment[${row_index}][bank_name]" value="${bank_name}">
                <input type="hidden" name="payment[${row_index}][cheque_date]" value="${cheque_date}">
                <input type="hidden" name="payment[${row_index}][cheque_number]" value="${cheque_number}">
                <input type="hidden" name="payment[${row_index}][amount]" value="${amount}">
            `);
            amount = __number_f(amount);
            $('#customer_payment_simple_table tbody').prepend(
                `<tr>
                    <td>` +
                        customer_name +
                        `</td>
                    <td>` +
                        payment_ref_no +
                        `</td>
                    <td>` +
                        payment_method +
                        `</td>
                    <td>` +
                        bank_name +
                        `</td>
                    <td>` +
                        cheque_date +
                        `</td>
                    <td>` +
                        cheque_number +
                        `</td>
                    <td>` +
                        amount +
                        `</td>
                    <td><button class="btn btn-xs btn-danger delete_customer_payment_simple"><i class="fa fa-times"></i></button>
                    </td>
                </tr>`
            );
            $('.customer_payment_simple_fields').val('').trigger('change');
            $('#row_index').val(row_index + 1);
        });
        $('#customer_payment_simple_payment_method').change(function () {

            let row = $(this).closest('.payment_data_row')
             if ($(this).val() == 'cheque') {
                row.find('.cheque_divs').removeClass('hide');
                row.find('.card_divs').addClass('hide');
            } else if ($(this).val() == 'card') {
                row.find('.card_divs').removeClass('hide');
                row.find('.cheque_divs').addClass('hide');
            } else {
                row.find('.card_divs').addClass('hide');
                row.find('.cheque_divs').addClass('hide');
            }
        });
        
        $(document).on('click', '.delete_customer_payment_simple', function () {
            tr = $(this).closest('tr');
            tr.remove();
        });
    </script>
   
    <script>
        $('#customer_interest_deduct_option').change(function () {
            if ($(this).val() === 'yes') {
                $('.interest_selection_checkbox').removeClass('hide');
            } else {
                $('.interest_selection_checkbox').addClass('hide');
            }
        });
        
        function calculate_interest_column_total() {
            let orderTotal = 0;
            $('.interest_column_total').each(function () {
                if (!isNaN(parseFloat($(this).val()))) {
                    orderTotal += parseFloat($(this).val());
                }
            });
            return orderTotal;
        }
        function calculate_amount_column_total() {
            let orderTotal = 0;
            $('.amount_column_total').each(function () {
                if (!isNaN(parseFloat($(this).val()))) {
                    orderTotal += parseFloat($(this).val());
                }
            });
            return orderTotal;
        }
   
    </script>
    
    <script>
        //customer payment bulk script
        $(document).ready(function () {
            $('#customer_payment_bulk_customer_id, #customer_payment_bulk_payment_method, #customer_payment_bulk_accounting_module, #customer_interest_deduct_option').each(function () {
                var $field = $(this);
                if ($field.data('select2')) { $field.select2('destroy'); }
                $field.select2({ width: '100%', dropdownParent: $field.closest('.tab-pane').length ? $field.closest('.tab-pane') : $(document.body) });
            });
            if ($('#customer_payment_bulk_customer_id').val()) {
                $('#customer_payment_bulk_customer_id').trigger('change');
            }
        })
        $('#customer_payment_bulk_customer_id').change(function () {
            if (!$(this).val()) {
                $('#customer_payment_bulk_balance').val('');
                $('#customer_payment_bulk_balance_amt').val('');
                $('#payment_bulk_invoice_table tbody').empty();
                return;
            }
            $.ajax({
                method: 'get',
                url: '/petro/settlement/payment/get-customer-details/' + $(this).val(),
                data: {},
                success: function (result) {
                    __write_number($('#customer_payment_bulk_balance'), result.total_outstanding);
                    $('#customer_payment_bulk_balance_amt').val(result.total_outstanding_amt);
                    // __write_number($('#customer_payment_bulk_balance_amt'), result.total_outstanding_amt);
                },
            });
            $.ajax({
                method: 'get',
                url: '/customer-payment-bulk/get-payment-table?customer_id=' + $(this).val(),
                data: {},
                contentType: 'html',
                success: function (result) {
                    $('#payment_bulk_invoice_table tbody').empty().append(result);
                    $('#payable_amount').val('');
                },
            });
        });
        $(document).on('change', '.interest_selection_checkbox, .amount_selection_box', function () {
             if($(this).closest('tr').find('.paying_checkbox').prop('checked') == true){
                $(this).closest('tr').find('.paying_checkbox').click();
             }
        });
        $(document).on('click', '.paying_checkbox', function () {
            let excess_amount = parseFloat($('#excess_amount').val());
            let transactionId = $(this).data('id');
            let outstandingAmount = parseFloat($('#outstanding_amount_'+transactionId).val());
            let interestAmount = parseFloat($('#interest_amount_'+transactionId).val());
            let totalAmount = parseFloat($('#total_amount_'+transactionId).val());
            if(!isNaN(totalAmount)) {
                if(!isNaN(interestAmount)) {
                    totalAmount -= interestAmount;
                }
            } else {
                totalAmount = outstandingAmount;
                if(!isNaN(interestAmount)) {
                    totalAmount += interestAmount;
                }
            }
            if($(this).is(":checked")) {
                if(excess_amount < totalAmount){

                $('#total_amount_'+transactionId).val(excess_amount.toFixed(2)).trigger('input');
                }else{

                $('#total_amount_'+transactionId).val(totalAmount.toFixed(2)).trigger('input');
                }
            } else {
                $('#total_amount_'+transactionId).val('').trigger('input');
            }
            let access_amount = parseFloat($('#payable_amount').val());
            $('.amount_column_total').each(function(i, input) {
                let inputVal = $(this).val();
                if(!isNaN(inputVal)) {
                    access_amount -= inputVal;
                }
            });


            calculate_excess_amount();
            // $('#excess_amount').val(access_amount);
        });
        
        $(document).on('input','.amount_column_total,#payable_amount',function(){
            var total_paid = calculate_amount_column_total();
            var total_amount = $("#payable_amount").val();
            
            $("#balance_amount_to_mark").text(__number_f(total_amount-total_paid));
            
            if(total_amount < total_paid){
                toastr.error('{{__("lang_v1.balance_is_insufficient")}}','Error');
                $("#submit_payment_bulk").prop('disabled',true);
            }else{
                $("#submit_payment_bulk").prop('disabled',false);
            }
        })
        
        $(document).on('change','#select_all_checkbox', function() {
            
            if ($(this).is(':checked')) {
                $('.paying_checkbox').trigger('click');
            } else {
                $('.paying_checkbox').trigger('click');
            }
        });
        
        
         var  Gtotal_paying = 0;
        function calculate_excess_amount() {
            let total_paying = 0;
            $('.paying_checkbox').each(function () {
                if ($(this).prop('checked') === true) {
                    let id = $(this).data('id');
                    let this_amount = parseFloat($('.amount_' + id).val());
                    total_paying += this_amount;

                }
            })

            // let payable_amount = Math.round($('#customer_payment_bulk_balance_amt').val());

            let payable_amount = parseFloat($('#payable_amount').val());
            // alert(total_paying);
            // if(total_paying > 0){

             let Balance = parseFloat($('#customer_payment_bulk_balance_amt').val());
             if(Balance < 0){
                payable_amount = payable_amount+Balance;
             }
            excess_amount = payable_amount - total_paying;
            Gtotal_paying = excess_amount;
            // $('#payable_amount').val(total_paying);
            $('#excess_amount').val(excess_amount.toFixed(2));
             // }

        }

        $('#payable_amount').change(function () {
            let amount = parseFloat($(this).val());

            calculate_excess_amount();
           
                tAmount = amount;
            let Balance = parseFloat($('#customer_payment_bulk_balance_amt').val());
            
            if(Balance < 0){
                tAmount = amount+Math.abs(Balance);
            }
            $('#excess_amount').val(tAmount);
            
        });
        $(document).on('click', '.pay_all', function () {
            if ($(this).prop('checked') === true) {
                $('.paying_checkbox').each(function () {
                    let excess_amount = parseFloat($('#excess_amount').val());
                    if (excess_amount == 0) {
                        return;
                    }
                    $(this).prop('checked', true);
                    let id = $(this).data('id');
                    let outstanding = parseFloat($('.outstanding_' + id).val());
                    if (excess_amount < outstanding) {
                        outstanding = excess_amount;
                    }
                    excess_amount -= outstanding;
                    $('#excess_amount').val(excess_amount.toFixed(2));
                    $('.amount_' + id).val(outstanding.toFixed(2));
                    calculate_excess_amount();
                })
            }
        });
        var customerPaymentDateRangeSettings = Object.assign({}, dateRangeSettings, {
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
        });
        
        if ($('#customer_payment_date_range').length == 1) {
            $('#customer_payment_date_range').daterangepicker(customerPaymentDateRangeSettings, function(start, end) {
                $('#customer_payment_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                if (typeof customer_payments !== 'undefined' && customer_payments) {
                    customer_payments.ajax.reload();
                }
            });
            
            $('#customer_payment_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#customer_payment_date_range').val('');
                if (typeof customer_payments !== 'undefined' && customer_payments) {
                    customer_payments.ajax.reload();
                }
            });
            
            $('#customer_payment_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#customer_payment_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
            
            // Set initial value
            $('#customer_payment_date_range').val(
                moment().startOf('month').format(moment_date_format) + ' - ' + moment().endOf('month').format(moment_date_format)
            );
        }

                $('#custom_date_apply_button').on('click', function () {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);
                    let fullRange = formattedStartDate + ' - ' + formattedEndDate;

                    // === Update customer_payment_date_range if it exists ===
                    if ($('#customer_payment_date_range').length) {
                        $('#customer_payment_date_range').val(fullRange);
                        $('#customer_payment_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#customer_payment_date_range').data('daterangepicker').setEndDate(moment(endDate));
                        $("#report_date_range").text("Date Range: " + fullRange);
                        if (typeof customer_payments !== 'undefined' && customer_payments) {
                            customer_payments.ajax.reload();
                        }
                    }
                    // Hide the modal
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            });
        $(document).ready(function () {
            
            $.ajax({
                method: 'get',
                url: '/customer-payment-information/all/amount',
                data: {},
                success: function (result) {
                    $('#customer_amount').populate(result.data, (item) => {
                        // Convert the string to a number and round it to 2 decimal places
                        const num = parseFloat(item).toFixed(2);
                        // Add a thousand separator to the number
                        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    });

                },
            });

            $.ajax({
                method: 'get',
                url: '/customer-payment-information/all/cheque_no',
                data: {},
                success: function (result) {
                    $('#customer_cheque_no').populate(result.data);
                },
            });
            
            
            customer_payments = $('#interest_settings_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/interest-settings",
                    "data": function (d) {
                    }
                },
                columns: [
                    {data: 'date', name: 'interest_settings.date', searchable: false},
                    {data: 'contact_group', name: 'contact_groups.name'},
                    {data: 'account', name: 'accounts.name'},
                    {data: 'username', name: 'users.username'}
                ]
            });
            customer_payments_table = $('#customer_payments_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/customer-payments",
                    "data": function (d) {
                        if ($('#customer_payment_date_range').val()) {
                            var start = $('#customer_payment_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#customer_payment_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.location_id = $('#customer_payment_location_id').val();
                        d.customer_id = $('#customer_payment_customer_id').val();
                        d.payment_method = $('#customer_payment_method').val();
                        d.paid_in_type = $('#paid_in_type option:selected').val();
                        d.payment_amount = $('#customer_amount').val();
                        d.daily_shift_no = $('#daily_shift_no').val();
                        d.cheque_number = $('#customer_cheque_no').val();
                    }
                },
                columns: [
                    {data: 'action', name: 'action', searchable: false},
                    {data: 'paid_on', name: 'tp.paid_on'},
                    {data: 'created_at', name: 'tp.created_at'},
                    {data: 'location_name', name: 'business_locations.name'},
                    {data: 'payment_ref_no', name: 'tp.payment_ref_no'},
                    {data: 'name', name: 'contacts.name'},
                    {data: 'interest', name: 'act.interest'},
                    {data: 'total_paid', name: 'tp.amount'},
                    {data: 'method', name: 'tp.method'},
                    {data: 'paid_in_type', name: 'tp.paid_in_type'},
                    {data: 'cheque_deposit_transfer_date', name: 'cheque_deposit_transfer_date', searchable: false},
                    {data: 'username', name: 'users.username'},
                    // Modified by Engr. Alex -- task 7889
                    {data: 'note', name: 'tp.note', searchable: false},
                ],
                "fnDrawCallback": function (oSettings) {
                    
                  var total_ = sum_table_col($('#customer_payments_table'), 'amount');
                  $('#footer_total').text(total_);
        
                  var tot_interest = sum_table_col($('#customer_payments_table'), 'interest');
                  $('#footer_interest').text(tot_interest);
                    
                    __currency_convert_recursively($('#customer_payments_table'));
                },
            });
        });
        $('#customer_interest_date_range').daterangepicker({
                    singleDatePicker: false, // For selecting a single date
                    showDropdowns: true, // To show the dropdown for predefined date ranges
                    locale: {
                        format: 'YYYY-MM-DD', // Adjust the date format according to your needs
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Custom Date Range': [moment().startOf('month'), moment().endOf(
                            'month')], // Default custom date range (this can be modified)
                    }
                }, function(start, end, label) {
                    if (label === 'Custom Date Range') {
                        // Show the modal for manual input
                        $('.custom_date_typing_modal').modal('show');
                        // $('.custom_date_typing_modal').modal('show'); // Uncomment if needed
                    }else{
                        // Set the selected date in the input
                        $('#customer_interest_date_range').val(start.format('YYYY-MM-DD'));

                        // Refresh DataTable with new date
                        customer_payments.ajax.reload();;
                    }
                });

                $('#custom_date_apply_button').on('click', function () {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);
                    let fullRange = formattedStartDate + ' ~ ' + formattedEndDate;

                    // === Update #9c_date_range if it exists ===
                    if ($('#customer_interest_date_range').length) {
                        $('#customer_interest_date_range').val(fullRange);
                        $('#customer_interest_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#customer_interest_date_range').data('daterangepicker').setEndDate(moment(endDate));
                        $("#report_date_range").text("Date Range: " + fullRange);
                        customer_payments.ajax.reload();
                    }
                    // Hide the modal
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            });
        $('#customer_interest_date_range').on('cancel.daterangepicker', function (ev, picker) {
            $('#customer_interest_date_range').val('');
            customer_payments.ajax.reload();
        });
        $(document).ready(function () {
            customer_payments = $('#customer_interest_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/customer-interest",
                    "data": function (d) {
                        if ($('#customer_interest_date_range').val()) {
                            var start = $('#customer_interest_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#customer_interest_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.location_id = $('#customer_payment_location_id').val();
                        d.customer_id = $('#customer_payment_customer_id').val();
                        d.daily_shift_no = $('#daily_shift_no').val();
                        d.payment_method = $('#customer_payment_method').val();
                        d.paid_in_type = $('#paid_in_type option:selected').val();
                        d.payment_amount = $('#customer_amount').val();
                        d.cheque_number = $('#customer_cheque_no').val();
                    }
                },
                columns: [
                    {data: 'action', name: 'action', searchable: false},
                    {data: 'paid_on', name: 'tp.paid_on'},
                    {data: 'location_name', name: 'business_locations.name'},
                    {data: 'name', name: 'contacts.name'},
                    {data: 'interest', name: 'act.interest'},
                    {data: 'total_paid', name: 'total_paid', searchable: false},
                    {data: 'method', name: 'tp.method'},
                    {data: 'paid_in_type', name: 'tp.paid_in_type'},
                    {data: 'username', name: 'users.username'},
                ],
                "fnDrawCallback": function (oSettings) {
                    __currency_convert_recursively($('#customer_payments_table'));
                },
            });
        });
        $('#customer_interest_date_range').change(function () {
            customer_payments.ajax.reload();
        });

        $('#customer_payment_date_range,#daily_shift_no, #customer_amount, #customer_cheque_no ,#customer_payment_location_id, #customer_payment_method, #paid_in_type').change(function () {
            if (typeof customer_payments_table !== 'undefined' && customer_payments_table) {
                customer_payments_table.ajax.reload();
            }
        });

        //select box

        $.fn.populate = function(data, callable = null) {
            $(this).empty()
            $(this).append(`<option value="">All</option>`)
            data.forEach(item=>{
                $(this).append(`<option value="${item}">${callable?callable(item):item}</option>`)
            })
        }

        $('#customer_payment_customer_id').change(function(){
            if($('#customer_payment_customer_id').val()){
                $.ajax({
                method: 'get',
                url: '/customer-payment-information/' + $(this).val() +'/amount',
                data: {},
                success: function (result) {
                    $('#customer_amount').populate(result.data, (item) => {
                        // Convert the string to a number and round it to 2 decimal places
                        const num = parseFloat(item).toFixed(2);
                        // Add a thousand separator to the number
                        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    });
                },
                });

                $.ajax({
                    method: 'get',
                    url: '/customer-payment-information/' + $(this).val() +'/cheque_no',
                    data: {},
                    success: function (result) {
                        $('#customer_cheque_no').populate(result.data);
                    },
                });
            }
            if (typeof customer_payments_table !== 'undefined' && customer_payments_table) {
                customer_payments_table.ajax.reload();
            }
        })
        
         $(document).on('click', '.delete_payment', function(e) {
            e.preventDefault();

            swal({
    
                title: LANG.sure,
    
                text: LANG.confirm_delete_payment,
    
                icon: 'warning',
    
                buttons: true,
    
                dangerMode: true,
    
            }).then((willDelete) => {
    
                if (willDelete) {
    
                    var href = $(this).data('href');
    
                    var data = $(this).serialize();
    
                    $.ajax({
    
                        method: 'DELETE',
    
                        url: href,
    
                        dataType: 'json',
    
                        data: data,
    
                        success: function(result) {
    
                            if (result.success == true) {
    
                                toastr.success(result.msg);
    
                                if (typeof customer_payments_table !== 'undefined' && customer_payments_table) {
                                    customer_payments_table.ajax.reload();
                                }
    
                            } else {
    
                                toastr.error(result.msg);
    
                            }
    
                        },
    
                    });
    
                }
    
            });
    
        });

        $(document).on('click', '.btn-modal-view', function (e) {
            e.preventDefault();
            var container = $(this).data('container');
            $.ajax({
                url: $(this).data('href'),
                dataType: 'html',
                success: function (result) {
                    $(container).html(result).modal('show');
                },
            });
        });

        // Modified by Engr. Alex -- task 7889: Edit payment handler
        $(document).on('click', '.edit_payment', function (e) {
            e.preventDefault();
            var editHref   = $(this).data('href');
            var updateHref = $(this).data('update-href');

            $.ajax({
                method: 'GET',
                url: editHref,
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        var p = result.payment;
                        $('#edit_payment_id').val(p.id);
                        $('#edit_payment_update_url').val(updateHref);
                        $('#edit_paid_on').val(p.paid_on ? p.paid_on.substring(0, 10) : '');
                        $('#edit_amount').val(p.amount);
                        $('#edit_method').val(p.method);
                        $('#edit_note').val(p.note);
                        $('#edit_account_id').val(p.account_id || '');
                        $('#edit_location_id').val(p.location_id || '');
                        $('#editPaymentModal').modal('show');
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error loading payment details.');
                },
            });
        });

        // Modified by Engr. Alex -- task 7889: use event delegation so the handler works even though
        // the modal form is rendered after this script block in the DOM
        $(document).on('submit', '#editPaymentForm', function (e) {
            e.preventDefault();
            var url = $('#edit_payment_update_url').val();
            $.ajax({
                method: 'PUT',
                url: url,
                data: $(this).serialize(),
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('#editPaymentModal').modal('hide');
                        if (typeof customer_payments_table !== 'undefined' && customer_payments_table) {
                            customer_payments_table.ajax.reload();
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving payment.');
                },
            });
        });

    </script>

    {{-- Modified by Engr. Alex -- task 7889: Edit Payment Modal --}}
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">@lang('messages.edit') @lang('lang_v1.payment')</h4>
                </div>
                <form id="editPaymentForm">
                    @csrf
                    <input type="hidden" id="edit_payment_id" name="payment_id">
                    <input type="hidden" id="edit_payment_update_url">
                    <input type="hidden" id="edit_account_id" name="account_id">
                    <input type="hidden" id="edit_location_id" name="location_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('lang_v1.date')</label>
                            <input type="date" class="form-control" id="edit_paid_on" name="paid_on" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('lang_v1.amount')</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('lang_v1.payment_method')</label>
                            <select class="form-control" id="edit_method" name="method">
                                <option value="cash">@lang('lang_v1.cash')</option>
                                <option value="card">@lang('lang_v1.card')</option>
                                <option value="cheque">@lang('lang_v1.cheque')</option>
                                <option value="bank_transfer">@lang('lang_v1.bank_transfer')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>@lang('lang_v1.payment_note')</label>
                            <textarea class="form-control" id="edit_note" name="note" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
