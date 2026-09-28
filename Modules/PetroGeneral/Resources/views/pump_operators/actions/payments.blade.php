@extends('layouts.'.$layout)
@section('content')
<style>
    @media print {
        .no-print,
        .no-print * {
            display: none !important;
        }
    }
    .print_section {
        display: none;
    }

    @media print {
        .print_section {
            display: inline !important;
        }
    }
</style>
<div class="container no-print">
  @include('petrogeneral::pump_operators.partials.payment_section', ['pop_up' => false])
</div>

<div class="modal fade" id="direct_cr" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang( 'petrogeneral::lang.credit_sale' )</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            @include('petrogeneral::pump_operators.credit_sale')
        </div>

      </div>
    </div>
  </div>
  
 <div class="modal fade" id="cheque_payments" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang( 'petrogeneral::lang.cheque' )</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            @include('petrogeneral::pump_operators.cheque_payment')
        </div>

      </div>
    </div>
  </div>
  
  <div class="modal fade" id="cash_payments" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang( 'petrogeneral::lang.cash' )</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController@saveCashDenom'), 'method' =>'post']) !!}
            <div class="row">
                <div class="col-md-6">
                    @foreach($cash_denoms as $denom)
                    
                        <div class="row">
                            <input type="hidden" value="{{$denom}}" class="denom_value">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{@num_format($denom)}}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::number('qty[]', 0, ['class' => 'form-control cash_payment_input denom_qty', 'required',
                                    'placeholder' => __(
                                    'petrogeneral::lang.qty' ) ]); !!}
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::text('total_amount[]', 0, ['class' => 'form-control denom_amt', 'required','readonly',
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
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::text('grand_total', 0, ['class' => 'form-control denom_total', 'required','readonly',
                                'placeholder' => __(
                                'petrogeneral::lang.total' ),'style' => 'color:green;font-weight:bold;' ]); !!}
                            </div>
                        </div>
                        
                        <div class="col-md-2 pull-right">
                            <button type="submit" class="btn btn-success pull-right other_sale_finalize"
                                style="margin-top: 23px;">@lang('petrogeneral::lang.correct')</button>
                        </div> 
                        
                        
                        
                    </div>
                </div>
 
                </div>
            </div>
            {!! Form::close() !!}
        </div>

      </div>
    </div>
  </div>
  
<div class="modal fade" id="card_payment" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang('petrogeneral::lang.card')</h4>
          <button type="button" class="btn btn-primary pull-right" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            @include('petrogeneral::pump_operators.card_payment')
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

      </div>
    </div>
  </div>
  
  <div class="modal fade" id="other_sales" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang('petrogeneral::lang.enter_meters')</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            @include('petrogeneral::pump_operators.other_sales',compact('pending_pumps'))
        </div>

      </div>
    </div>
  </div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>

@endsection

@section('javascript')
<script src="{{ route('petrogeneral.assets.js', ['file' => 'po_payment.js']) }}?v={{ time() }}"></script>

<script>
$(document).on('submit', 'form', function () {
    const btn = $('.other_sale_finalize');

    btn.prop('disabled', true)
       .text('Processing...');
});
</script>

<script>
    $(document).ready(function(){
        $(".select2").select2();
        
        $(document).on('click', '.add_other_sales', function(e) {
            
            $("#other_sales").modal({
                backdrop: 'static',
                keyboard: false 
            });
            
            $(".other_sale_finalize").attr('disabled',true);

            // Re-evaluate after modal opens in case inputs already have values
            setTimeout(function() {
                if (typeof calculate_other_sales_totals === 'function') {
                    calculate_other_sales_totals();
                }
            }, 300);
        });
        
        
         $(document).on('click', '.add_cheque_payment', function(e) {
            $("#cheque_payments").modal({
                backdrop: 'static',
                keyboard: false 
            });
        });
        
        $(document).on('click', '.cash_payment_btn', function(e) {
            if ($("#meter_sales_compulsory").val() == "yes") {
                return;
            }

            $("#payment_type").val("cash");
            $(".payment_type_btn").addClass("active");
            $(".payment_type_checkbox").prop("checked", false);
            $(this).removeClass("active");
            $(this).find(".payment_type_checkbox").prop("checked", true);

            if ($("#cash_payments").length) {
                $("#cash_payments").modal({
                    backdrop: 'static',
                    keyboard: false
                });
            }
        });
        
        
        $(document).on('click', '.card_payment_btn', function(e) {
            resetCardPaymentForm($('#card_payment_form'));
            $("#card_payment").modal({
                backdrop: 'static',
                keyboard: false 
            });
            
            $(".card-save-btn").prop('disabled',true);
            
            $(".card_payment_add").attr('disabled',true);
        });

        $(document).on('shown.bs.modal hidden.bs.modal', '#card_payment', function () {
            resetCardPaymentForm($('#card_payment_form'));
        });
        
        $(document).on('change input', '#card_payment_form [name="card_type"], #card_payment_form [name="slip_no"], #card_payment_form [name="card_no"], #card_payment_form [name="card_amount"]', function(){
            var $form = cardPaymentForm($(this));

            if ($form.find('[name="card_type"]').val() && $form.find('[name="card_amount"]').val()) {
                $form.find(".card_payment_add").attr('disabled', false);
            } else {
                $form.find(".card_payment_add").attr('disabled', true);
            }
        })
        
        function toggerCardSaveBtn($form){
            $form = $form && $form.length ? $form : $('#card_payment_form');

            if($form.find(".card-data").length > 0){
                $form.find(".card-save-btn").prop('disabled',false);
            }else{
                $form.find(".card-save-btn").prop('disabled',true);
            }
        }

        function refreshPaymentTables() {
            [
                '#pump_operators_payment_summary_table',
                '#pump_operators_meters_with_payments_table',
                '#list_daily_collection_table',
                '#daily_collection_table'
            ].forEach(function(selector) {
                if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                    $(selector).DataTable().ajax.reload(null, false);
                }
            });
        }

        function cardPaymentForm($source) {
            var $form = $source.closest('form');
            if (!$form.length) {
                $form = $('#card_payment_form');
            }
            if (!$form.length) {
                $form = $('#card_payment form').first();
            }
            return $form;
        }

        function resetCardPaymentEntry($form) {
            var $cardType = $form.find('[name="card_type"]');

            $form.find('[name="slip_no"]').val('').css('border', '').trigger('input').trigger('change');
            $form.find('[name="card_no"]').val('').css('border', '').trigger('input').trigger('change');
            $form.find('[name="card_amount"]').val('').trigger('input').trigger('change');
            $cardType.val(null).trigger('change');
            $form.find('.card_payment_add').attr('disabled', true);
        }

        function resetCardPaymentAmountEntry($form) {
            $form.find('[name="card_amount"]').val('').trigger('input').trigger('change');
            $form.find('.card_payment_add').attr('disabled', true);
        }

        function resetCardPaymentForm($form) {
            $form.find('#card_payment_table tbody').empty();
            resetCardPaymentEntry($form);
            $form.find('.card-save-btn').prop('disabled', true);
        }
        
        $(document).on('click', '.card_payment_add', function () {
            var $form = cardPaymentForm($(this));
            const dropdown = $form.find('[name="card_pmt_type"]').get(0);
            const enterCardNumbers = $form.find('[name="enter_card_numbers"]').val();
            var $slipNo = $form.find('[name="slip_no"]');
            var $cardNo = $form.find('[name="card_no"]');
            var $cardType = $form.find('[name="card_type"]');
            var $cardAmount = $form.find('[name="card_amount"]');

            if (dropdown && dropdown.value !== 'bulk') {
                let hasError = false;
                if (!$slipNo.val()) {
                    $slipNo.css("border", "1px solid red");
                    hasError = true;
                    $slipNo.on("input", function () {
                        $(this).css("border", "");
                    });
                } else {
                    $slipNo.css("border", "");
                }
                if (enterCardNumbers == 'yes') {
                    if (!$cardNo.val()) {
                        $cardNo.css("border", "1px solid red");
                        hasError = true;
                        $cardNo.on("input", function () {
                            $(this).css("border", "");
                        });
                    } else {
                        $cardNo.css("border", "");
                    }
                }
                if (!($cardNo.val() && String($cardNo.val()).trim())) {
                    $cardNo.css("border", "1px solid red");
                    hasError = true;
                    $cardNo.on("input", function () {
                        $(this).css("border", "");
                    });
                } else {
                    $cardNo.css("border", "");
                }
                // if (!$("#card_no").val()) {
                //     $("#card_no").css("border", "1px solid red");
                //     hasError = true;
                //     $("#card_no").on("input", function () {
                //         $(this).css("border", "");
                //     });
                // } else {
                //     $("#card_no").css("border", "");
                // }

                if (hasError) {
                    toastr.error("Enter Slip Number");
                    return;
                }
            }
            const cardNumberRequired = true;
           
            var data = {
                card_type: $cardType.val(),
                slip_no: $slipNo.val(),
                amount: $cardAmount.val(),
                card_number: cardNumberRequired ? $cardNo.val() : null, // null if not required
                collection_form_no: $form.find('[name="collection_form_no"]').val()
            };
            var cardNumberTd = cardNumberRequired ? `<td>` + $cardNo.val() + `</td>` : '';
            var amount = parseFloat($cardAmount.val()) || 0;
var formattedAmount = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

              var html = `
                <tr>
                    <td>`+ $cardType.find('option:selected').text() + `</td>
                    <td>`+ $slipNo.val() + `</td>
                    `+ cardNumberTd +`
                  <td>`+ formattedAmount +`
                      <input type="hidden" name="card_data[]" class="card-data" required value='`+JSON.stringify(data)+`'>
                </td>

                    <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                </tr>
            `;
        
            $form.find("#card_payment_table tbody").append(html);
            resetCardPaymentEntry($form);
            toggerCardSaveBtn($form);
        });
        
        // Remove row when the remove button is clicked
        $(document).on('click', '.remove-row', function () {
            $(this).closest('tr').remove();
            toggerCardSaveBtn(cardPaymentForm($(this)))
        });

        // Card payment form AJAX submission
        $(document).on('submit', '#card_payment_form', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Card payment form submit triggered via AJAX');
            
            var $form = $(this);
            var $saveBtn = $form.find('.card-save-btn');
            
            // Prevent double submission
            if ($saveBtn.prop('disabled')) {
                console.log('Button already disabled, preventing duplicate submission');
                return false;
            }
            
            $saveBtn.prop('disabled', true).html('Saving...');
            
            var formData = $form.serialize();
            console.log('Form data:', formData);
            resetCardPaymentForm($form);
            $("#card_payment").modal("hide");
            
            $.ajax({
                method: 'POST',
                url: $form.attr('action'),
                data: formData,
                dataType: 'json',
                success: function(result) {
                    $saveBtn.prop('disabled', false).html('@lang("petrogeneral::lang.save")');
                    
                    console.log('Card payment result:', result);
                    console.log('Collection form no:', result.collection_form_no);
                    
                    if (result.success) {
                        toastr.success(result.msg);
                        $saveBtn.prop('disabled', true).html('@lang("petrogeneral::lang.save")');
                        
                        // Update collection form number
                        $(".collection_form_no").each(function() {
                            $(this).val(result.collection_form_no);
                        });
                        
                        refreshPaymentTables();
                        
                        // Show confirmation modal with form number
                        if (result.collection_form_no) {
                            $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + result.collection_form_no);
                        } else {
                            console.error('No collection_form_no in response!');
                            $("#reloadConfirmationModalLabel").html("Confirm Another Payment");
                        }
                        $("#reloadConfirmationModal").modal("show");
                    } else {
                        toastr.error(result.msg || 'Something went wrong');
                    }
                },
                error: function(xhr, status, error) {
                    $saveBtn.prop('disabled', false).html('@lang("petrogeneral::lang.save")');
                    
                    console.error('Card payment save error:', {
                        status: xhr.status,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    var errorMsg = 'Something went wrong while saving card payment';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.msg) {
                            errorMsg = response.msg;
                        }
                    } catch (e) {
                        // Use default message
                    }
                    
                    toastr.error(errorMsg);
                }
            });
            
            return false;
        });


        
        
        $(".credit_sale_finalize").hide();
        $(".credit_sale_finalize_print").hide();
        
        // Clear credit sale table when modal is opened to prevent duplicate entries
        $(document).on('click', '.po_credit_payment', function(e){
          // Clear any existing rows in the credit sale table
          $("#credit_sale_table tbody").empty();
          // Reset totals
          $(".credit_sale_total").text("0.00");
          $(".credit_tb_discount_total").text("0.00");
          $(".credit_tbl_amount_total").text("0.00");
          // Hide finalize buttons
          $(".credit_sale_finalize").hide();
          $(".credit_sale_finalize_print").hide();
          // Clear form fields
          $(".credit_sale_fields").val("");
          $("#customer_reference_one_time").val("").trigger("change");
          $("#direct_cr").modal('show');
        });
        
        // Also clear table when modal is closed to prevent stale data
        $("#direct_cr").on('hidden.bs.modal', function () {
          $("#credit_sale_table tbody").empty();
          $(".credit_sale_total").text("0.00");
          $(".credit_tb_discount_total").text("0.00");
          $(".credit_tbl_amount_total").text("0.00");
          $(".credit_sale_finalize").hide();
          $(".credit_sale_finalize_print").hide();
          $(".credit_sale_fields").val("");
          $("#customer_reference_one_time").val("").trigger("change");
        });
        
        
        // Don't auto-select first customer - let placeholder show by default
        // Removed: $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');
        $('#order_date').datepicker("setDate", new Date());
        $('#credit_sale_product_id').select2();
        $('#credit_sale_customer_id').select2();
        $('#customer_reference').select2();
    });

    $(document).on('change', '#customer_reference_one_time', function(){
        if($(this).val() !== '' && $(this).val() !== null && $(this).val() !== undefined){
            $('#customer_reference').attr('disabled', 'disabled');
            $('.quick_add_customer_reference').attr('disabled', 'disabled');
        }else{
            $('#customer_reference').removeAttr('disabled');
            $('.quick_add_customer_reference').removeAttr('disabled');
        }
    })
    
    $(document).on('change', '.cash_payment_input', function() {
        var amount = $(this).val() ?? 0;  // Default to 0 if no value
        var row = $(this).closest('.row'); // Find the closest row element
        var denom_value = row.find(".denom_value").val(); // Get the denomination value
        var total = denom_value * amount; // Calculate the total
    
        console.log(total); // Log the calculated total
    
        row.find('.denom_amt').val(total); // Set the calculated total in the denom_amt field
    
        calculateDenomTotals(); // Call the function to update the totals
    });

    
   function calculateDenomTotals() {
        var total = 0;
    
        $('.denom_amt').each(function() {
            var denom_total = parseFloat($(this).val()) || 0; 
            total += denom_total; 
        });
    
        $('.denom_total').val(total.toFixed(2)); 
    }


    
    $(document).on("click", ".credit_sale_add", function () {
        if ($("#credit_sale_amount").val() == "") {
            toastr.error("Please enter amount");
            return false;
        }
        var credit_sale_customer_id = $("#credit_sale_customer_id").val();
        var customer_name = $("#credit_sale_customer_id :selected").text();
        
        // Validate customer is selected
        if (!credit_sale_customer_id || credit_sale_customer_id === '' || credit_sale_customer_id === null) {
            toastr.error("Please select a customer");
            return false;
        }
        
        // Validate customer name is not "None" or empty
        if (!customer_name || customer_name === '' || customer_name === 'None') {
            toastr.error("Please select a valid customer");
            return false;
        }
        
        var credit_sale_product_id = $("#credit_sale_product_id").val();
        var credit_sale_product_name = $("#credit_sale_product_id :selected").text();
        
        // Validate product is selected
        if (!credit_sale_product_id || credit_sale_product_id === '' || credit_sale_product_id === null) {
            toastr.error("Please select a product");
            return false;
        }
        
        // Validate product name is not "None" or empty
        if (!credit_sale_product_name || credit_sale_product_name === '' || credit_sale_product_name === 'None') {
            toastr.error("Please select a valid product");
            return false;
        }
        
        if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !== null && $("#customer_reference_one_time").val() !== undefined) {
            var customer_reference = $("#customer_reference_one_time").val();
        } else {
            var customer_reference = $("#customer_reference").val();
        }
        var settlement_no = $("#settlement_no").val();
        var order_date = $("#order_date").val();
        var order_number = $("#order_number").val();
        
        // Validate: Same order number cannot be used with different customers
        if (order_number && order_number.trim() !== '' && order_number.trim() !== '0') {
            var duplicateOrderWithDifferentCustomer = false;
            $("#credit_sale_table tbody tr").each(function() {
                var existingData = JSON.parse($(this).find('.credit_data').val());
                var existingOrderNumber = existingData.order_number;
                var existingCustomerId = existingData.customer_id;
                
                // Check if same order number exists with a different customer
                if (existingOrderNumber && existingOrderNumber.trim() !== '' && 
                    existingOrderNumber.trim() !== '0' && 
                    existingOrderNumber.trim() === order_number.trim() &&
                    existingCustomerId !== credit_sale_customer_id) {
                    duplicateOrderWithDifferentCustomer = true;
                    return false; // Break the loop
                }
            });
            
            if (duplicateOrderWithDifferentCustomer) {
                toastr.error("Order Number " + order_number + " is already used with a different customer. Please use a different order number or select the same customer.");
                return false;
            }
        }
        
        // Order number is optional - no validation required
        
  var credit_sale_price = __read_number($("#unit_price"));
var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;

// calculate full precision quantity
var credit_sale_qty_full =
    (__read_number($("#credit_total_amount")) ?? 0) /
    credit_sale_price;

// display quantity
var credit_sale_qty =
    __read_number($("#credit_sale_qty")) ?? 0;
    
        
        // Validate quantity is required and greater than 0
        if (!credit_sale_qty || credit_sale_qty <= 0 || isNaN(credit_sale_qty)) {
            toastr.error("Please enter a valid quantity (must be greater than 0)");
            $("#credit_sale_qty").focus();
            return false;
        }
        var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
        var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
        var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;
        
        var outstanding = $(".current_outstanding").text();
        var credit_limit = $(".credit_limit").text();
        var credit_note = $("#credit_note").val();
        
        
        var credit_data = {
            settlement_no: '',
            customer_id: credit_sale_customer_id, // Ensure this is not empty
            customer_name: customer_name, // Also include customer_name for fallback
            product_id: credit_sale_product_id,
            order_number: order_number,
            order_date: order_date,
            price: credit_sale_price,
            unit_discount: credit_unit_discount,
            qty: credit_sale_qty_full,
            qty_display: credit_sale_qty,
            amount: credit_total_amount,
            sub_total: credit_sub_total,
            total_discount: credit_total_discount,
            outstanding: outstanding,
            credit_limit: credit_limit,
            customer_reference: customer_reference,
            note: credit_note
        };
        
    
    
                $("#credit_sale_table tbody").prepend(
                    `
                    <tr> 
                        <td>` +
                        customer_name +
                        `<input type="hidden" class="credit_data" value='` + JSON.stringify(credit_data) + `'></td>
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
                        credit_sale_qty.toFixed(2) +
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
                        <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment"><i class="fa fa-times"></i></button>
                        </td>
                    </tr>
                `
                );
                toastr.success("Successfully Added");
                
                // Clear all form fields for new entry
                // Clear select2 fields first
                $("#credit_sale_customer_id").val(null).trigger("change");
                $("#credit_sale_product_id").val(null).trigger("change");
                $("#customer_reference").val(null).trigger("change");
                $("#customer_reference_one_time").val("").trigger("change");
                
                // Clear text inputs
                $("#order_number").val("");
                $("#order_date").val("");
                $("#unit_price").val("");
                $("#unit_discount").val("");
                $("#credit_sale_qty").val("");
                $("#credit_total_amount").val("");
                $("#credit_discount_amount").val("");
                $("#credit_sale_amount").val("");
                $("#credit_note").val("");
                
                // Also clear fields with credit_sale_fields class (in case we missed any)
                $(".credit_sale_fields").not("select").val("");
                $(".cash_fields").val("");
                
                // Reset order date to today
                $('#order_date').datepicker("setDate", new Date());
                
                // Recalculate totals
                calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
                calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
                calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
});

function calculateTotal(table_name, class_name_td, output_element) {
    let total = 0.0;
    $(table_name + " tbody")
        .find(class_name_td)
        .each(function () {
            total += parseFloat(__number_uf($(this).text()));
        });
        
        if(total <=0){
            $(".credit_sale_finalize").hide();
            $(".credit_sale_finalize_print").hide();
        }else{
            $(".credit_sale_finalize").show();
            $(".credit_sale_finalize_print").show();
        }
    $(output_element).text(__number_f(total, false, false, __currency_precision));
}

$(document).on('input','#credit_total_amount', function() {
    $("#credit_sale_qty").attr('disabled',true);
    
    let price = __read_number($("#unit_price")) ?? 0;
    let total_amount = __read_number($("#credit_total_amount")) ?? 0;
    let qty = total_amount / price; 
    
    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;
    let unit_discount = total_discount / qty;
    let amount = total_amount - total_discount
    
    __write_number($("#credit_sale_amount"), amount);
    __write_number($("#unit_discount"), unit_discount);
    // Keep full calculated fuel quantity precision to avoid settlement/print rounding differences.
    // Example: 3000 / 295 = 10.169491525423728, not 10.17.
    $("#credit_sale_qty").val(qty);
    
    
});

$(document).on('input','#credit_sale_qty', function() {
    $("#credit_total_amount").attr('disabled',true);
    
    let price = __read_number($("#unit_price")) ?? 0;
    let qty = __read_number($("#credit_sale_qty")) ?? 0; 
    let total_amount = price * qty;
    
    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;
    let unit_discount = total_discount / qty;
    let amount = total_amount - total_discount
    
    __write_number($("#credit_sale_amount"), amount);
    __write_number($("#unit_discount"), unit_discount);
    __write_number($("#credit_total_amount"),total_amount);
    
});

$(document).on("change", "#credit_discount_amount, #unit_price", function () {
    let price = __read_number($("#unit_price")) ?? 0;
    let qty_check = __read_number($("#credit_sale_qty")) ?? 0;
    
    var qty = 0;
    var total_amount = 0;
    
    if(qty_check > 0){
        qty = __read_number($("#credit_sale_qty")) ?? 0;
        total_amount = price * qty; 
        __write_number($("#credit_total_amount"), total_amount);
    }else{
        total_amount = __read_number($("#credit_total_amount")) ?? 0;
        qty = total_amount / price; 
        // Keep full calculated fuel quantity precision to avoid settlement/print rounding differences.
    // Example: 3000 / 295 = 10.169491525423728, not 10.17.
    $("#credit_sale_qty").val(qty);
    }
    
    
    
    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;
    let unit_discount = total_discount / qty;
    let amount = total_amount - total_discount
    
    __write_number($("#unit_discount"), unit_discount);
    __write_number($("#credit_sale_amount"),amount);
    
});
    
$(document).on("change", "#credit_sale_product_id", function () {
    if ($(this).val()) {
        $.ajax({
            method: "get",
            url: "/petro-general/settlement/payment/get-product-price",
            data: { product_id: $(this).val() },
            success: function (result) {
                $("#unit_price").val(result.price);
                $("#unit_price").trigger('change');
                
$("#credit_total_amount").prop("disabled", false).removeAttr("disabled");
$("#credit_sale_qty").prop("disabled", false).removeAttr("disabled");

if ($("#manual_discount").val() == 1) {
    $("#credit_discount_amount").prop("disabled", false).removeAttr("disabled");
}
                
            },
        });
    } else {
$("#credit_total_amount").prop("disabled", true);
$("#credit_sale_qty").prop("disabled", true);
$("#credit_discount_amount").prop("disabled", true);
    }
});
$(document).on("change", "#credit_sale_customer_id", function () {
    $.ajax({
        method: "get",
        url: "/petro-general/settlement/payment/get-customer-details/" + $(this).val(),
        data: {},
        success: function (result) {
            $(".current_outstanding").text(result.total_outstanding);
            $(".credit_limit").text(result.credit_limit);
            $("#customer_reference").empty();
            $("#customer_reference").append(`<option selected="selected" value="">Please Select</option>`);
            result.customer_references.forEach(function (ref, i) {
                $("#customer_reference").append(`<option value="` + ref.reference + `">` + ref.reference + `</option>`);
                // $("#customer_reference").val($("#customer_reference option:eq(1)").val()).trigger("change");
            });
        },
    });
});
$(document).on("click", ".delete_credit_sale_payment", function () {
    tr = $(this).closest("tr");
    tr.remove();
    
    calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
    calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
    calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
});

$(document).on("click", ".credit_sale_finalize", function (e) {
    e.preventDefault();
    
    // Prevent double-clicking
    var $btn = $(this);
    if ($btn.prop('disabled')) {
        return false;
    }
    $btn.prop('disabled', true);
    
    var dataArray = [];
    $(".credit_data").each(function() {
        var jsonData = JSON.parse($(this).val());
        var collection_form_no = $("#collection_form_no").val() ?? "";
        jsonData.collection_form_no = collection_form_no;
        dataArray.push(jsonData);
    });
    $.ajax({
        method: "post",
        url: "/petro-general/pump-operator-pmts/save-credit",
        data: {
            credit_data : dataArray,
            collection_form_no : $("#collection_form_no").val() ?? "",
        },
        dataType: 'json',
        success: function (result) {
            $btn.prop('disabled', false); // Re-enable button
            if (result.success) {
            toastr.success(result.msg);
            $(".collection_form_no").each(function() {
                $(this).val(result.collection_form_no);
            });
                // Clear the table after successful save
                $("#credit_sale_table tbody").empty();
                $(".credit_sale_total").text("0.00");
                $(".credit_tb_discount_total").text("0.00");
                $(".credit_tbl_amount_total").text("0.00");
                $(".credit_sale_finalize").hide();
                $(".credit_sale_finalize_print").hide();
                $(".credit_sale_fields").val("");
                $("#customer_reference_one_time").val("").trigger("change");
            $("#direct_cr").modal("hide");
            $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + result.collection_form_no);
            $("#reloadConfirmationModal").modal("show");
            } else {
                toastr.error(result.msg || 'Something went wrong');
            }
        },
        error: function(xhr, status, error) {
            $btn.prop('disabled', false); // Re-enable button on error
            console.error('Credit sale save error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            var errorMsg = 'Something went wrong while saving credit sale';
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.msg) {
                    errorMsg = response.msg;
                }
            } catch (e) {
                // If response is not JSON, use default message
            }
            
            toastr.error(errorMsg);
        }
    });
});

$(document).on('click', '.credit_sale_finalize_print', function(e) {
    e.preventDefault();
    
    // Show the print copy selection modal
    $('#print_copy_selection_modal').modal('show');
    
    // Store button reference for later use
    window.creditSaleFinalizePrintBtn = $(this);
});

// Handle the print confirmation button
$(document).on('click', '#confirm_print_btn', function(e) {
    e.preventDefault();
    
    var selectedCopy = $('input[name="print_copy_option"]:checked').val();
    
    // Close the modal
    $('#print_copy_selection_modal').modal('hide');
    
    // Open the print window early (user gesture) to avoid popup blocking.
    var printWindow = null;
    try {
        printWindow = window.open('', '_blank');
    } catch (err) {
        printWindow = null;
    }

    var saveBtn = window.creditSaleFinalizePrintBtn;
    saveBtn.html('Saving & Print...');
    saveBtn.prop('disabled', true);
    
    var dataArray = [];
    $(".credit_data").each(function() {
        var jsonData = JSON.parse($(this).val());
        var collection_form_no = $("#collection_form_no").val() ?? "";
        jsonData.collection_form_no = collection_form_no;
        dataArray.push(jsonData);
    });
    $.ajax({
        method: "post",
        url: "/petro-general/pump-operator-pmts/save-credit",
        data: {
            credit_data : dataArray,
            collection_form_no : $("#collection_form_no").val() ?? "",
            print: "print",
            print_copy_option: selectedCopy,
        },
        dataType: 'json',
        success: function (result) {
            saveBtn.html('Print & Save');
            saveBtn.prop('disabled', false);
            
            if (result.success) {
            toastr.success(result.msg);
            $(".collection_form_no").each(function() {
                $(this).val(result.collection_form_no);
            });
                // Clear the table after successful save
                $("#credit_sale_table tbody").empty();
                $(".credit_sale_total").text("0.00");
                $(".credit_tb_discount_total").text("0.00");
                $(".credit_tbl_amount_total").text("0.00");
                $(".credit_sale_finalize").hide();
                $(".credit_sale_finalize_print").hide();
                $(".credit_sale_fields").val("");
                $("#customer_reference_one_time").val("").trigger("change");
            $("#direct_cr").modal("hide");

            if(result.print){
                if (result.html_content) {
                    // Prefer printing in the dedicated window to handle full HTML print templates safely.
                    if (printWindow && !printWindow.closed) {
                        printWindow.document.open();
                        printWindow.document.write(result.html_content);
                        printWindow.document.close();
                        printWindow.focus();
                    } else {
                        $('#receipt_section').html(result.html_content);
                        setTimeout(function () {
                            window.print();
                        }, 1000);
                    }
                } else if (result.print_credit_sale_id) {
                    // Fallback: open server-side print route if HTML wasn't returned.
                    var url = '/petro-general/pump-operator-pmts/print-credit-sale/' + result.print_credit_sale_id;
                    if (printWindow && !printWindow.closed) {
                        printWindow.location.href = url;
                        printWindow.focus();
                    } else {
                        window.location.href = url;
                    }
                } else {
                    toastr.error('Print content is missing. Please try reprint.');
                    if (printWindow && !printWindow.closed) {
                        printWindow.close();
                    }
                }
            }

            $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + result.collection_form_no);
            $("#reloadConfirmationModal").modal("show");
            } else {
                toastr.error(result.msg || 'Something went wrong');
                saveBtn.html('Print & Save');
                saveBtn.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Credit sale save error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            var errorMsg = 'Something went wrong while saving credit sale';
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.msg) {
                    errorMsg = response.msg;
                }
            } catch (e) {
                // If response is not JSON, use default message
            }
            
            toastr.error(errorMsg);
            saveBtn.html('Print & Save');
            saveBtn.prop('disabled', false);
            if (printWindow && !printWindow.closed) {
                printWindow.close();
            }
        }
    });
});



    let cash_payment_currentInput = null;

    $(".cash_payment_input").on('focus', function() {
        cash_payment_currentInput = $(this); 
    });
    
    function cashPaymentEnterVal(val) {
        if (!cash_payment_currentInput) return;
    
        let str = cash_payment_currentInput.val(); 
    
        if (val === "precision") {
            if (!str.includes(".")) {
                str += ".";
                cash_payment_currentInput.val(str);
            }
            return;
        }
    
        if (val === "backspace") {
            str = str.substring(0, str.length - 1);
            cash_payment_currentInput.val(str);
            return;
        }
    
        str += val; 
        cash_payment_currentInput.val(str);
        cash_payment_currentInput.focus();
        cash_payment_currentInput.trigger('change');
    }
    
    
    let card_payment_currentInput = null;

    $(".card_payment_input").on('focus', function() {
        card_payment_currentInput = $(this); 
    });
    
    function cardPaymentEnterVal(val) {
        if (!card_payment_currentInput) return;
    
        let str = card_payment_currentInput.val(); 
    
        if (val === "precision") {
            if (!str.includes(".")) {
                str += ".";
                card_payment_currentInput.val(str);
            }
            return;
        }
    
        if (val === "backspace") {
            str = str.substring(0, str.length - 1);
            card_payment_currentInput.val(str);
            return;
        }
    
        str += val; 
        card_payment_currentInput.val(str);
        card_payment_currentInput.focus();
        card_payment_currentInput.trigger('change');
    }

</script>
@endsection
