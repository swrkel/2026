@php
    $is_no_change_payment_modal = !empty($no_change);
@endphp

@unless($is_no_change_payment_modal)
<div class="row">

    <div class="col-md-12">

        <div class="row">


            <div class="col-md-3">

                <div class="form-group" style="width: 100% !important">

                    {!! Form::label('credit_sale_customer_id', __('petropd::lang.customer').':') !!}

                    <div class="input-group">

                        {!! Form::select('credit_sale_customer_id', !empty($only_walkin) ? $walkin : $credit_customers, null, ['class' => 'form-control select2',

                            'style' => 'width: 100%;']); !!}

                        @if(empty($only_walkin))

                        <span class="input-group-btn">

                             <button type="button" class="btn btn-default bg-white btn-flat btn-modal"

                             data-href="{{action('ContactController@create',['type' => 'customer','is_credit' => true])}}" data-container=".contact_modal"

                               @if (!auth()->user()->can('customer.create')) disabled @endif><i

                               class="fa fa-plus-circle text-primary fa-lg"></i></button>

                        </span>

                        @endif

                    </div>

                </div>

                

            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __('petropd::lang.order_number')) !!}

                    @if($business->duplicate_orders_allowed === 1)
                        {{-- Duplicate orders allowed → default value 0 --}}
                        {!! Form::text('order_number', 0, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('petropd::lang.order_number')
                        ]) !!}
                    @else
                        {{-- Duplicate orders not allowed → default empty --}}
                        {!! Form::text('order_number', null, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('petropd::lang.order_number')
                        ]) !!}
                    @endif
                </div>
            </div>

            


            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('order_date', __( 'petropd::lang.order_date' ) ) !!}

                    {!! Form::text('order_date', null, ['class' => 'form-control

                    order_date',

                    'placeholder' => __(

                    'petropd::lang.order_date' ) ]); !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('customer_reference', __( 'petropd::lang.select_customer_vehicle_no' ) ) !!}

                   {!! Form::select('customer_reference', [], null, ['class' => 'form-control credit_sale_fields select2

                        customer_reference', 'required', 'id' => 'customer_reference', 'style' => 'width: 100%',

                        'placeholder' => __(

                        'petropd::lang.please_select' ) ]); !!}

                </div>

            </div>

            

            <div class="clearfix"></div>

            

            

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('credit_sale_product_id', __('petropd::lang.credit_sale_product').':') !!}

                    {!! Form::select('credit_sale_product_id', $products, null, ['class' => 'form-control select2',

                    'style' => 'width: 100%;',

                    'placeholder' => __(

                    'petropd::lang.please_select' )]); !!}

                </div>

                <input type="hidden" id="manual_discount" value="{{auth()->user()->can('manual_discount') ? 1 : 0}}">



            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('unit_price', __( 'petropd::lang.unit_price' ) ) !!}

                    {!! Form::text('unit_price', null, ['class' => 'form-control input_number

                    unit_price', 'readonly',

                    'placeholder' => __(

                    'petropd::lang.unit_price' ) ]); !!}

                </div>

            </div>

            

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('unit_discount', __( 'petropd::lang.unit_discount' ) ) !!}

                    {!! Form::text('unit_discount', null, ['class' => 'form-control input_number

                    unit_discount', 'disabled' => true,

                    'placeholder' => __(

                    'petropd::lang.unit_discount' ) ]); !!}

                </div>

            </div>

            

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('credit_sale_qty', __( 'petropd::lang.credit_sale_qty' ) ) !!}

                    {!! Form::text('credit_sale_qty', null, ['class' => 'form-control credit_sale_fields input_number

                    credit_sale_qty',

                    'placeholder' => __(

                    'petropd::lang.credit_sale_qty' ), 'disabled' => true ]); !!}

                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden" >

                </div>

            </div>

            

            <div class="clearfix"></div>

            <input type="hidden" name="total_amount_enable" id="total_amount_enable" value="0">

            

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('credit_total_amount', __( 'petropd::lang.amount' ).__( 'petropd::lang.before_discount_cr' ) ) !!} 

                    

                    {!! Form::text('credit_total_amount', null, ['id' => 'credit_total_amount', 'class' => 'form-control credit_sale_fields cust_input_number

                    credit_total_amount', 'required', 'disabled' => true,

                    'placeholder' => __(

                    'petropd::lang.credit_total_amount' ) ]); !!}

                </div>

            </div>

            

            

            <div class="col-md-3  hidden">

                <div class="form-group">

                    {!! Form::text('credit_sale_amount', null, ['id' => 'credit_sale_amount','class' => 'form-control credit_sale_fields cust_input_number

                    credit_sale_amount', 'required', 'disabled' => true,

                    'placeholder' => __(

                    'petropd::lang.amount' ) ]); !!}

                    <input type="hidden" name="credit_sale_amount_hidden" value="0" id="credit_sale_amount_hidden" >

                </div>

            </div>

            

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('credit_discount_amount', __( 'petropd::lang.credit_discount_amount' ) ) !!}

                    {!! Form::text('credit_discount_amount', null, ['class' => 'form-control credit_sale_fields cust_input_number

                    credit_discount_amount', 'required', 'disabled' => true,

                    'placeholder' => __(

                    'petropd::lang.credit_discount_amount' ) ]); !!}

                </div>

            </div>

            

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('customer_reference_one_time', __( 'petropd::lang.enter_customer_vehicle_no' ) ) !!}

                    {!! Form::text('customer_reference', null, ['class' => 'form-control

                    customer_reference_one_time', 'id' => 'customer_reference_one_time',

                    'placeholder' => __(

                    'petropd::lang.enter_customer_vehicle_no' ) ]); !!}

                </div>

            </div>

            

            

            <div class="col-md-4">

                <div class="form-group">

                  {!! Form::label("credit_note", __('lang_v1.payment_note') . ':') !!}

                  {!! Form::textarea("credit_note", null, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}

                </div>

                 {!! Form::hidden("pump_operator_id", $pump_operator->id ?? '', ['id' => 'pump_operator_id']) !!}

            </div>

            

            

            

            <div class="col-md-2 pull-right">

                <button type="button" class="btn btn-primary pull-right credit_sale_add"

                    style="margin-top: 23px;">@lang('messages.add')</button>

            </div>

            <div class="clearfix"></div>



            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">

                @lang('petropd::lang.current_outstanding'): <span class="current_outstanding"></span></div>

            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">

                @lang('petropd::lang.credit_limit'): <span class="credit_limit"></span></div>

        </div>

    </div>

</div>
@endunless



<div class="row" style="overflow-x: auto;">

    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="credit_sale_table">

            <thead>

                <tr>

                    <th>@lang('petropd::lang.cusotmer_name' )</th>

                    <th>@lang('petropd::lang.outstanding' )</th>

                    <th>@lang('petropd::lang.limit' )</th>

                    <th>@lang('petropd::lang.order_no' )</th>

                    <th>@lang('petropd::lang.order_date' )</th>

                    <th>@lang('petropd::lang.customer_reference' )</th>

                    <th>@lang('petropd::lang.product' )</th>

                    <th>@lang('petropd::lang.unit_price' )</th>

                    <th>@lang('petropd::lang.qty' )</th>

                    <th>@lang('petropd::lang.sub_total' )</th>

                    <th>@lang('petropd::lang.discount_total' )</th>

                    <th>@lang('petropd::lang.total' )</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('petropd::lang.action' )</th>

                </tr>

            </thead>

            <tbody id="credit_sale_table_body">

                @foreach ($settlement_credit_sale_payments as $credit_sale_payment)

                <tr>

                    <td>{{$credit_sale_payment->customer_name}}</td>

                    <td>{{@num_format($credit_sale_payment->outstanding)}}</td>

                    <td>{{@num_format($credit_sale_payment->credit_limit)}}</td>

                    <td>{{$credit_sale_payment->order_number}}</td>

                    <td>{{$credit_sale_payment->order_date}}</td>

                    <td>{{$credit_sale_payment->customer_reference}}</td>

                    <td>{{$credit_sale_payment->product_name}}</td>

                    <td>{{@num_format($credit_sale_payment->price)}}</td>

                    <td>{{ rtrim(rtrim(number_format((float) $credit_sale_payment->qty, 4, ".", ","), "0"), ".") }}</td>

                    <td class="credit_sale_amount">{{@num_format($credit_sale_payment->amount)}}

                    </td>

                    <td class="credit_tbl_discount_amount">{{@num_format($credit_sale_payment->total_discount)}}

                    </td>

                    <td class="credit_tbl_total_amount">{{@num_format(($credit_sale_payment->amount-$credit_sale_payment->total_discount))}}

                    </td>

                    <td>{{$credit_sale_payment->note}}</td>

                    <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment"

                            data-href="/petropd/settlement/payment/delete-credit-sale-payment/{{$credit_sale_payment->id}}"
                            {{ $is_no_change_payment_modal ? 'disabled' : '' }}><i

                                class="fa fa-times"></i></button></td>

                </tr>

                @endforeach

            </tbody>



            @php
                // PetroPD Pumper Dashboard credit totals are authoritative by one
                // unique pump_operator_payments.id. The detail table remains for
                // customer/product metadata and must never multiply Total Paid.
                $creditGrossTotal = isset($authoritative_credit_gross_total) && $authoritative_credit_gross_total !== null
                    ? (float) $authoritative_credit_gross_total
                    : (float) $settlement_credit_sale_payments->sum('amount');
                $creditDiscountTotal = isset($authoritative_credit_discount_total) && $authoritative_credit_discount_total !== null
                    ? (float) $authoritative_credit_discount_total
                    : (float) $settlement_credit_sale_payments->sum('total_discount');
                $creditNetTotal = isset($authoritative_credit_net_total) && $authoritative_credit_net_total !== null
                    ? (float) $authoritative_credit_net_total
                    : ($creditGrossTotal - $creditDiscountTotal);
            @endphp

            <tfoot>

                <tr>

                    <td colspan="9" style="text-align: right; font-weight: bold;">@lang('petropd::lang.total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="credit_sale_total">

                        {{ @num_format($creditGrossTotal) }}</td>

                    <td style="text-align: left; font-weight: bold;" class="credit_tb_discount_total">

                        {{ @num_format($creditDiscountTotal) }}</td>

                    <td style="text-align: left; font-weight: bold;" class="credit_tbl_amount_total">

                        {{ @num_format($creditNetTotal) }}</td>

                </tr>

                <input type="hidden" value="{{ $creditNetTotal }}" name="credit_sale_total" id="credit_sale_total">

            </tfoot>

        </table>

    </div>

</div>





<!-- <script>
$(document).ready(function() {
   
        $(document).on("change", "#order_number_input", function () {
       
        var orderNumber = $(this).val();
        if(orderNumber) {
            $.ajax({
                url: "/petropd/settlement/payment/check-order-number",
                type: 'GET',
                data: { order_number: orderNumber },
                success: function(response) {
                    if(response.exists) {
                        alert('Order number already exists! Please choose a different number.');
                        $('#order_number_input').val('');
                        $('#order_number_input').focus();
                    }
                },
                error: function(xhr) {
                    console.error('API call failed', xhr);
                }
            });
        }
    });
 
});
</script> -->



<script>

    $(document).ready(function(){

        $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');

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



    $(document).on('submit', '#customer_reference_add_form', function(e){

        e.preventDefault();

        let url = $('#customer_reference_add_form').attr('action');

        let data = $('#customer_reference_add_form').serialize();

        $.ajax({

            method: 'POST',

            url: url,

            dataType: 'json',

            data: data,

            success: function(result) {

                if(result.success){

                    let customer_reference = result.customer_reference;

                    $('#credit_sale_customer_id').trigger('change');

                }



                $('.view_modal').modal('hide');

            },

        });

    })

</script>



<script>
    window.isPetroPdNoChangePaymentModal = @json($is_no_change_payment_modal);

    /*
    |--------------------------------------------------------------------------
    | PetroPD Credit Sale Quantity Precision Fix
    |--------------------------------------------------------------------------
    | Fuel quantity must not be rounded to currency precision. Example:
    | Amount 3000 / Unit Price 295 = 10.169491525423728.
    | This helper recalculates Qty from Amount ÷ Unit Price and keeps the full
    | JavaScript numeric precision for saving, display, settlement and print.
    */
    function petroPdFormatFullQty(value) {
        var qty = Number(value);

        if (!isFinite(qty)) {
            return '0';
        }

        // Avoid scientific notation for normal fuel quantity ranges.
        var qtyText = qty.toString();

        if (qtyText.indexOf('e') !== -1 || qtyText.indexOf('E') !== -1) {
            qtyText = qty.toFixed(12).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
        }

        return qtyText;
    }

    function petroPdRecalculateCreditSaleQty() {
        var unitPrice = __read_number($("#unit_price")) || 0;
        var totalAmount = __read_number($("#credit_total_amount")) || 0;

        if (unitPrice > 0 && totalAmount > 0) {
            var qtyText = petroPdFormatFullQty(totalAmount / unitPrice);

            $("#credit_sale_qty").val(qtyText);
            $("#credit_sale_qty_hidden").val(qtyText);

            return parseFloat(qtyText);
        }

        $("#credit_sale_qty").val('');
        $("#credit_sale_qty_hidden").val(0);

        return 0;
    }

    $(document).on(
        'change keyup input',
        '#credit_total_amount, #unit_price',
        function () {
            petroPdRecalculateCreditSaleQty();
        }
    );

    // credit_sale payments

// $(document).on("click", ".credit_sale_add", function () {
$(document).off("click", ".credit_sale_add").on("click", ".credit_sale_add", function () {
    if (window.isPetroPdNoChangePaymentModal) {
        return false;
    }

     console.log('789');

    if ($("#credit_sale_amount").val() == "") {

        toastr.error("Please enter amount");

        return false;

    }

    var credit_sale_customer_id = $("#credit_sale_customer_id").val();

    var customer_name = $("#credit_sale_customer_id :selected").text();

    var credit_sale_product_id = $("#credit_sale_product_id").val();

    var credit_sale_product_name = $("#credit_sale_product_id :selected").text();

    if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !== null && $("#customer_reference_one_time").val() !== undefined) {

        var customer_reference = $("#customer_reference_one_time").val();

    } else {

        var customer_reference = $("#customer_reference").val();

    }

    var settlement_no = $("#settlement_no").val();

    var order_date = $("#order_date").val();

    var order_number = $("#order_number").val();

    var pump_operator_id = $('#pump_operator_id').val();



    console.log(pump_operator_id);

    

    var credit_sale_price = __read_number($("#unit_price"));

    var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;

    var credit_sale_qty = petroPdRecalculateCreditSaleQty();

    var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;

    var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;

    var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;

    

    var outstanding = $(".current_outstanding").text();

    var credit_limit = $(".credit_limit").text();

    var credit_note = $("#credit_note").val();

    var is_edit = $("#is_edit").val() ?? 0;

    

    $.ajax({

        method: "post",

        url: "/petropd/settlement/payment/save-credit-sale-payment",

        data: {

            settlement_no: settlement_no,

            customer_id: credit_sale_customer_id,

            product_id: credit_sale_product_id,

            order_number: order_number,

            order_date: order_date,

            pump_operator_id:pump_operator_id,

            // Financial ownership must remain on the exact selected Shift ID.
            shift_ids: ($('#shift_number').val() || $('#petropd_authoritative_shift_ids').val()),

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

        success: function (result) {

            if (!result.success) {

                toastr.error(result.msg);

            } else {

                settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;

                add_payment(credit_total_amount-credit_total_discount);

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

                        petroPdFormatFullQty(credit_sale_qty) +

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

                        <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petropd/settlement/payment/delete-credit-sale-payment/` +

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

                calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");

                calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");

                calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");

                // handlePaymentSuccess();

                 toastr.success("Added!");

                

            }

        },

    });

});



    

</script>
