@php



    $cashdenoms = $settlement->cash_denomination;

    if(!empty($cashdenoms)){

        $cashdenoms = json_decode($cashdenoms,true);

    }else{

        $cashdenoms = [];

    }



@endphp





<div class="col-md-12">

    <div class="row">

        <div class="col-md-3 cash_to_disable">

            <div class="form-group">

                {!! Form::label('cash_customer_id', __('petrogeneral::lang.customer').':') !!}

                {!! Form::select('cash_customer_id', $customers, null, ['class' => 'form-control

                select2', 'style' => 'width: 100%;']); !!}

            </div>

        </div>

        <div class="col-md-3 cash_to_disable">

            <div class="form-group 123">

                {!! Form::label('cash_amount', __( 'petrogeneral::lang.amount' ) ) !!}

                {!! Form::text('cash_amount', null, ['class' => 'form-control cash_fields cust_input_number

                cash_amount', 'required',

                'placeholder' => __(

                'petrogeneral::lang.amount' ) ]); !!}

            </div>

        </div>

        <div class="col-md-5 cash_to_disable">

            <div class="form-group">

              {!! Form::label("cash_note", __('lang_v1.payment_note') . ':') !!}

              {!! Form::textarea("cash_note", null, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}

            </div>

        </div>

        

        <div class="col-md-1">

            <button type="button" class="btn btn-primary cash_to_disable cash_add_updated"

            style="margin-top: 23px;">@lang('messages.add')</button>

        </div>

        

        @if(!empty($cash_denoms))

        <div class="col-md-4">

            <div class="checkbox">

                <label>

                    {!! Form::checkbox('enable_cash_denoms', '1', !empty($cashdenoms) ? true : false,

                    [ 'class' => 'input-icheck','id' => 'enable_cash_denoms']); !!} {{ __( 'lang_v1.enable_cash_denoms' ) }}

                </label>

            </div>

        </div>

        <div class="col-md-4 denoms_row">

            <div class="checkbox">

                <label>

                    {!! Form::checkbox('calculate_cash', '1', false,

                    [ 'class' => 'input-icheck','id' => 'calculate_cash']); !!} {{ __( 'lang_v1.calculate' ) }}

                </label>

            </div>

        </div>

        @endif

        

        

        

        

    </div>

    <div class="row denoms_row" id="denoms_row" style="margin-top: 10px">

        <div class="col-md-12">

            @foreach($cash_denoms as $denom)

            @php  

                $index = array_search($denom, array_column($cashdenoms, 'value'));

                if ($index !== false) {

                    $qty = $cashdenoms[$index]['qty'];

                    $denomtotal = $qty*$denom;

                    

                } else {

                    $qty = null;

                    $denomtotal = null;

                }

            @endphp

                <div class="row">

                    <input type="hidden" value="{{$denom}}" class="denom_value">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>{{number_format($denom, $currency_precision)}}</label>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::number('qty[]', $qty, ['class' => 'form-control denom_qty', 'required',

                            'placeholder' => __(

                            'petrogeneral::lang.qty' ) ]); !!}

                        </div>

                    </div>

                    

                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::text('total_amount[]', $denomtotal, ['class' => 'form-control denom_amt', 'required','readonly',

                            'placeholder' => __(

                            'petrogeneral::lang.total_amount' ) ]); !!}

                        </div>

                    </div>

                </div>

            @endforeach

                <div class="row denoms_totals">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>{{ __( 'lang_v1.total' ) }}</label>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            {!! Form::text('grand_total', null, ['class' => 'form-control denom_total', 'required','readonly',

                            'placeholder' => __(

                            'petrogeneral::lang.total' ),'style' => 'color:green;font-weight:bold;' ]); !!}

                        </div>

                    </div>

                    

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>{{ __( 'lang_v1.balance' ) }}</label>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            {!! Form::text('grand_bal', null, ['class' => 'form-control denom_bal', 'required','readonly',

                            'placeholder' => __(

                            'petrogeneral::lang.balance' ),'style' => 'color:red;font-weight:bold;' ]); !!}

                        </div>

                    </div>

                    

                </div>

        </div>

        

    </div>

</div>

<br><br>



<div class="row" style="margin-top: 10px">

    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="cash_table">

            <thead>

                <tr>

                    <th>@lang('petrogeneral::lang.cusotmer_name' )</th>

                    <th>@lang('petrogeneral::lang.amount' )</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('petrogeneral::lang.action' )</th>

                </tr>

            </thead>

            <!-- <tbody id="cash_table_body">

                @php

                    $cash_total = $settlement_cash_payments->sum('amount');

                @endphp

                @foreach ($settlement_cash_payments as $cash_payment)

                    <tr class="paymt-{{ $cash_payment->customer_payment_id }}">

                        <td>{{$cash_payment->customer_name}}</td>

                        <td class="cash_amount">{{number_format($cash_payment->amount, $currency_precision)}}</td>

                        <td>{{$cash_payment->note}}</td>

                        @php
                            $cashDeleteUrl = '/petro-general/settlement/payment/delete-cash-payment/' . ($cash_payment->id ?? 0);
                        @endphp
                        <td><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="{{ $cashDeleteUrl }}" data-pump-payment-id="{{ $cash_payment->pump_payment_id ?? '' }}"><i

                                    class="fa fa-times"></i></button></td>

                    </tr>

                @endforeach

                @if (!empty($total_daily_collection))

                    <tr>

                    <td class="text-red">@lang('petrogeneral::lang.daily_collections')</td>

                    <td class="text-red cash_amount">{{number_format($total_daily_collection, $currency_precision)}}</td>

                    <td></td>

                </tr>

                @endif

            </tbody> -->

            <tbody id="cash_table_body">
    @foreach ($settlement_cash_payments as $cash_payment)
        @php
            $cashDeleteUrl = '/petro-general/settlement/payment/delete-cash-payment/' . ($cash_payment->id ?? 0);
        @endphp
        <tr class="paymt-{{ $cash_payment->customer_payment_id }}">
            <td>{{ $cash_payment->customer_name }}</td>
            <td class="cash_amount">{{ number_format($cash_payment->amount, $currency_precision) }}</td>
            <td>{{ $cash_payment->note }}</td>
            <td>
                @if(!empty($cash_payment->pump_payment_id))
                    <button type="button" class="btn btn-xs btn-primary btn-modal edit_payment_btn" 
                        data-href="/petro-general/pump-operators/payment/{{ $cash_payment->pump_payment_id }}/edit"
                        title="@lang('messages.edit')">
                        <i class="fa fa-edit"></i>
                    </button>
                @endif
                <button type="button" class="btn btn-xs btn-danger delete_cash_payment" 
                    data-href="{{ $cashDeleteUrl }}"
                    data-pump-payment-id="{{ $cash_payment->pump_payment_id ?? '' }}">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        </tr>
    @endforeach
</tbody>

<tfoot>
    <tr>
        <td style="text-align: right; font-weight: bold;">
            Total Amount :
        </td>
        <td style="text-align: left; font-weight: bold;" class="cash_total">
            {{ number_format($cash_total, $currency_precision) }}
        </td>
    </tr>
    <!-- <tr>

                    <td style="text-align: right; font-weight: bold;">@lang('lang_v1.settlement_cash_total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="cash_total">

                    {{number_format($cash_total+$total_daily_collection, $currency_precision)}}</td>

                </tr> -->

                <input type="hidden" value="{{$cash_total}}" name="cash_total" id="cash_total">
</tfoot>




            <!-- <tfoot>

                <tr>

                    <td style="text-align: right; font-weight: bold;">@lang('lang_v1.settlement_cash_total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="cash_total">

                    {{number_format($cash_total+$total_daily_collection, $currency_precision)}}</td>

                </tr>

                <input type="hidden" value="{{$cash_total}}" name="cash_total" id="cash_total">

            </tfoot> -->

        </table>

    </div>

</div>







@if(!empty($cashdenoms))

<script>

      $(".denoms_row").show();

</script>

@else

<script>

    $(".denoms_row").hide();

    

</script>

@endif



<script>

    $('#cash_customer_id').select2();

    $(document).ready(function(){

        

        $("#cash_customer_id").val($("#cash_customer_id option:eq(0)").val()).trigger('change');

        

        $('#enable_cash_denoms').change(function() {

            if ($(this).is(':checked')) {

                $(".denoms_row").show();

            } else {

              $(".denoms_row").hide();

              $('#calculate_cash').prop('checked', false).trigger('change');

            }

            calculateDenoms();

        });

        

        

        $('#calculate_cash').change(function() {

            var text = $('.cash_total').text();

            var val = parseFloat(text.replace(',', ''));

            

            if ($(this).is(':checked')) {

                $(".denoms_totals").hide();

                if(val > 0){

                    $(".cash_to_disable").hide();

                }else{

                    $(".cash_to_disable").show();

                }

            } else {

              $(".denoms_totals").show();

              $(".cash_to_disable").show();

            }

            calculateDenoms();

        });

          

          $(document).on('keyup', '.denom_qty', function() {

            var $row = $(this).closest('.row');

            var denom_value = parseFloat($row.find('.denom_value').val());

            var qty = parseFloat($(this).val());

            var ttotal = 0;

            

            if (!isNaN(denom_value) || !isNaN(qty)) {

                var total_amount = denom_value * qty;

                if(isNaN(total_amount)){

                    total_amount = 0;

                }

                

                ttotal = total_amount;

                

                total_amount = total_amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                

                $row.find('.denom_amt').val(total_amount);

                

                if ($('#calculate_cash').is(':checked')) {

                    $("#cash_amount").val(ttotal);

                }

                

              } else {

                $row.find('.denom_amt').val(''); // Reset denom_amt if input values are invalid

              }

              

              calculateDenoms();

              

          });

          

        

    });

</script>



<script>

    

function add_payment(add_amount) {

    if (typeof window.add_payment === "function") {
        return window.add_payment(add_amount);
    }

    if (typeof window.petroSettlementAddPaymentCore === "function") {
        return window.petroSettlementAddPaymentCore(add_amount);
    }

    add_amount = parseFloat(String(add_amount).replace(/,/g, ""));
    if (isNaN(add_amount)) {
        return;
    }

    let total_balance = parseFloat(String($("#total_balance").val() || "0").replace(/,/g, "")) || 0;
    let total_paid = parseFloat(String($("#total_paid").val() || "0").replace(/,/g, "")) || 0;

    total_balance = total_balance - add_amount;
    total_paid = total_paid + add_amount;

    if (typeof window.clearBalanceLock === "function" && !window.__petro_skip_add_payment) {
        window.clearBalanceLock();
    }

    $("#total_balance").val(__number_f(total_balance, false, false, __currency_precision));
    $("#total_paid").val(total_paid);
    $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
    $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));

    show_hide_excess_shortage_tab();
    calculateDenoms(add_amount);

}



</script>



<script>

    function show_hide_excess_shortage_tab() {

    let rawBalance = $("#total_balance").val() || "0"; // fallback to "0" if empty/null

    let total_balance = parseFloat(rawBalance.replace(/,/g, ""));



    // Handle NaN fallback

    if (isNaN(total_balance)) {

        total_balance = 0;

    }



    $(document).find('li.disabled a').off('click');

    $('#excess_amount').prop('disabled', false);



    // Show/disable tabs based on balance state (tabs remain visible but inactive when not applicable)
    if (total_balance > 0) {
        // Balance is positive (shortage) - disable Excess tab, enable Shortage tab
        $(".excess_tab").parents("li:first").show().addClass("disabled");
        $(".shortage_tab").parents("li:first").show().removeClass("disabled");
        $('#excess_add').prop('disabled', true);
        $('#shortage_add').prop('disabled', false);
        $('#shortage_amount').prop('disabled', false);

    } else if (total_balance < 0) {
        // Balance is negative (excess) - disable Shortage tab, enable Excess tab
        $(".shortage_tab").parents("li:first").show().addClass("disabled");
        $(".excess_tab").parents("li:first").show().removeClass("disabled");
        $('#shortage_add').prop('disabled', true);
        $('#shortage_amount').prop('disabled', true);
        $('#excess_add').prop('disabled', false);

    } else {
        // Balance is zero - enable both tabs
        $(".excess_tab").parents("li:first").show().removeClass("disabled");
        $(".shortage_tab").parents("li:first").show().removeClass("disabled");
        $('#excess_add').prop('disabled', false);
        $('#shortage_add').prop('disabled', false);
        $('#shortage_amount').prop('disabled', false);

    }



    $(document).find('li.disabled a').on('click', function(e) {

        e.preventDefault();

        return false;

    });
    $(".excess_tab, .shortage_tab").off('click');



    $(document).find('#settlement_form .settlement_tabs li a').on('click', function(e) {

        setTimeout(() => {

            $('#excess_amount').prop('disabled', false);

        }, 500);

        localStorage.setItem("settlement_tabs", $(this).attr('href'));

    });

}

</script>



<script>

    function calculateDenoms(amount = 0){

  var grand_total = 0;

  $('.denom_amt').each(function() {

    var amt = $(this).val();

    

    amt = parseFloat(amt.replace(/,/g, ""))

    

    

    if (!isNaN(amt)) {

      grand_total += amt;

    }

  });

  

  

  if ($('#calculate_cash').is(':checked')) {

        $("#cash_amount").val(grand_total);

        $("#cash_amount").prop('readonly', true);

    }else{

        $("#cash_amount").prop('readonly', false);

    }

  

  $('.denom_total').val(grand_total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

  

  var cashtotal = $(".cash_total").text();

  var bal = parseFloat(cashtotal.replace(/,/g, "")) - grand_total + amount;

  total_balance = parseFloat($("#total_balance").val().replace(/,/g, ""));

  total_balance = parseFloat(total_balance.toFixed(__currency_precision));

  

  if($('#enable_cash_denoms').is(':checked')){

      if(bal == 0 && total_balance == 0){

          $("#settlement_save_btn").removeClass("hide");

      }else{

          $("#settlement_save_btn").addClass("hide");

      }

  }else{

      if(total_balance == 0){

          $("#settlement_save_btn").removeClass("hide");

      }else{

          $("#settlement_save_btn").addClass("hide");

      }

  }

      

  

  $(".denom_bal").val(bal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

}

</script>





<script>
// $(document).on("click", ".cash_add_updated", function () {
$(document).off("click", ".cash_add_updated").on("click", ".cash_add_updated", function () {
    console.log('12333');

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
        url: "/petro-general/settlement/payment/save-cash-payment",
        data: {
            customer_id: cash_customer_id,
            amount: cash_amount,
            settlement_no: settlement_no,
            note: cash_note,
            is_edit: is_edit,
        },
        success: function (result) {
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
                // Cash payments should NOT affect balance - they are separate from outstanding balance
                // Removed: add_payment(cash_amount);

                // Build new row
                let newRow = `
                    <tr> 
                        <td>${customer_name}</td>
                        <td class="cash_amount">${__number_f(cash_amount, false, false, __currency_precision)}</td>
                        <td>${cash_note}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger delete_cash_payment" 
                                data-href="/petro-general/settlement/payment/delete-cash-payment/${settlement_cash_payment_id}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;

                // Insert before daily collection row if exists
                if ($("#cash_table tbody tr.text-red").length > 0) {
                    $("#cash_table tbody tr.text-red").first().before(newRow);
                } else {
                    $("#cash_table tbody").append(newRow);
                }

                $(".cash_fields").val("");
                calculateTotal("#cash_table", ".cash_amount", ".cash_total");

                toastr.success("Added!");
            }
        },
    });
});

// Function to update cash payment row when payment is edited
function updateCashPaymentRow(paymentId, newAmount, oldAmount) {
    // Find the row in cash table by matching the old amount
    // This is more reliable than using payment ID since settlement cash payments
    // might be linked differently than pump operator payments
    var $row = null;
    var oldAmountFormatted = parseFloat(oldAmount.toString().replace(/,/g, ''));
    
    $('#cash_table tbody tr').each(function() {
        var rowAmountText = $(this).find('.cash_amount').text().trim();
        var rowAmount = parseFloat(rowAmountText.replace(/,/g, ''));
        
        // Match by amount (with small tolerance for floating point)
        if (Math.abs(rowAmount - oldAmountFormatted) < 0.01) {
            $row = $(this);
            return false; // break
        }
    });
    
    // If still not found, try by payment ID (fallback)
    if (!$row || $row.length === 0) {
        $row = $('#cash_table tbody tr.paymt-' + paymentId);
        
        // If not found by class, try to find by checking delete button href
        if ($row.length === 0) {
            $('#cash_table tbody tr').each(function() {
                var deleteHref = $(this).find('.delete_cash_payment').attr('data-href') || '';
                if (deleteHref.includes('/' + paymentId) || deleteHref.includes('payment/' + paymentId)) {
                    $row = $(this);
                    return false; // break
                }
            });
        }
    }
    
    if ($row && $row.length > 0) {
        // Update the amount in the row
        var formattedAmount = __number_f(newAmount, false, false, __currency_precision);
        $row.find('.cash_amount').text(formattedAmount);
        
        // Recalculate the total
        calculateTotal("#cash_table", ".cash_amount", ".cash_total");
        
        // Update the difference in total balance if needed
        var amountDiff = parseFloat(newAmount) - parseFloat(oldAmount);
        if (amountDiff !== 0) {
            // Adjust total_paid and total_balance if they exist
            var currentTotalPaid = parseFloat($("#total_paid").val() || 0);
            var newTotalPaid = currentTotalPaid + amountDiff;
            $("#total_paid").val(newTotalPaid);
            $(".total_paid").text(__number_f(newTotalPaid, false, false, __currency_precision));
            
            var currentTotalBalance = parseFloat($("#total_balance").val().replace(/,/g, "") || 0);
            var newTotalBalance = currentTotalBalance - amountDiff;
            $("#total_balance").val(__number_f(newTotalBalance, false, false, __currency_precision));
            $(".total_balance").text(__number_f(newTotalBalance, false, false, __currency_precision));
            
            show_hide_excess_shortage_tab();
        }
    } else {
        // If row not found, log a warning but don't break
        console.warn('Cash payment row not found for payment ID: ' + paymentId + ', old amount: ' + oldAmount);
    }
}

// Listen for payment updated event
$(document).on('payment:updated', function(e, response) {
    if (response.payment_type === 'cash') {
        updateCashPaymentRow(response.payment_id, response.payment_amount, response.old_amount);
    }
});
</script>
