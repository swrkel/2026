<div class="col-md-12">

    <div class="row">

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('excess_amount', __( 'settlementsw::lang.amount' ) ) !!}

                {!! Form::text('excess_amount', null, ['class' => 'form-control excess_fields input_number

                excess_amount', 'required',

                'placeholder' => __(

                'settlementsw::lang.amount' ) ]); !!}

                <div class="text-center text-red excess_amount_err hidden">

                  

                  <span class="total_amount">Not Allowed. Already Shortage amount entered</span>

              </div>

            </div>

            

        </div>

        <div class="col-md-6">

                <div class="form-group">

                  {!! Form::label("excess_note", __('lang_v1.payment_note') . ':') !!}

                  {!! Form::textarea("excess_note", null, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}

                </div>

            </div>

        <div class="col-md-3">

            <button type="button" class="btn btn-primary excess_add_btn"

            style="margin-top: 23px;">@lang('messages.add')</button>

        </div>

    </div>

</div>

<br><br>



<div class="row">

    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="excess_table">

            <thead>

                <tr>

                    <th></th>

                    <th>@lang('settlementsw::lang.amount' )</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('settlementsw::lang.action' )</th>

                </tr>

            </thead>

            <tbody id="excess_table_body">

                @php

                    $excess_total = $settlement_excess_payments->sum('amount');

                @endphp

                @foreach ($settlement_excess_payments as $excess_payment)

                    <tr>

                        <td></td>

                        <td class="excess_amount">{{number_format($excess_payment->amount, $currency_precision)}}</td>

                        <td class="excess_amount">{{$excess_payment->note}}</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/settlement-sw/sw-add-payment/excess-payment/{{$excess_payment->id}}"><i

                                    class="fa fa-times"></i></button></td>

                    </tr>

                @endforeach

            </tbody>



            <tfoot>

                <tr>

                    <td style="text-align: right; font-weight: bold;">@lang('settlementsw::lang.total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="excess_total">

                       {{number_format($excess_total, $currency_precision)}}</td>

                </tr>

                <input type="hidden" value="{{$excess_total}}" name="excess_total" id="excess_total">

            </tfoot>

        </table>

    </div>

</div>









<script>

    $(document).ready(function(){

        

       

        $("#excess_customer_id").val($("#excess_customer_id option:eq(0)").val()).trigger('change');

        

    });

    

    

function add_payment(add_amount) {

    add_amount = parseFloat(add_amount);

    total_balance = parseFloat($("#total_balance").val().replace(",", ""));

    total_paid = parseFloat($("#total_paid").val());

    total_balance = total_balance - add_amount;

    total_paid = total_paid + add_amount;

    $("#total_balance").val(total_balance);

    $("#total_paid").val(total_paid);

    $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));

    $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));

    // Show/hide Save button based on balance (use tolerance for floating-point comparison)
    if (Math.abs(total_balance) < 0.01) {
        $("#settlement_save_btn").removeClass("hide");
        $("#balance_to_operator_btn").addClass("hide");
    } else {
        $("#settlement_save_btn").addClass("hide");
        $("#balance_to_operator_btn").removeClass("hide");
    }

    show_hide_excess_shortage_tab();

    calculateDenoms(add_amount);

}



function calculateTotal(table_name, class_name_td, output_element) {

    let total = 0.0;

    $(table_name + " tbody")

        .find(class_name_td)

        .each(function () {

            total += parseFloat(__number_uf($(this).text()));

        });

    $(output_element).text(__number_f(total, false, false, __currency_precision));

}

</script>





<script>

    

    // $(document).on("click", ".excess_add_btn", function () {
$(document).off("click", ".excess_add_btn").on("click", ".excess_add_btn", function () {   

    console.log('function called')

    var excess_amount_input = $("#excess_amount").val();

    var excess_note = $("#excess_note").val();

    if (excess_amount_input == "") {

        toastr.error("Please enter amount");

        return false;

    } else {

        if (excess_amount_input > 0) {

            toastr.error("Please enter the amount with a negative symbol");

            return false;

        }

    }

    var settlement_no = $("#settlement_no").val();

    var excess_amount = $("#excess_amount").val();

    var is_edit = $("#is_edit").val() ?? 0;

    

    $.ajax({

        method: "post",

        url: "/settlement-sw/sw-add-payment/excess-payment",

        data: {

            settlement_no: settlement_no,

            amount: excess_amount,

            note: excess_note,

            is_edit: is_edit

        },

        success: function (result) {

            console.log(result.success)

            if (!result.success) {

                toastr.error(result.msg);

            } else {

                

                settlement_excess_payment_id = result.settlement_excess_payment_id;

                var auto_balance_attr = (window.__settlementsw_auto_balance_pending && window.__settlementsw_auto_balance_pending.type === 'excess')
                    ? ' data-auto-balance="1"' : '';
                $("#excess_table tbody").append(

                    `

                    <tr` + auto_balance_attr + `> 

                        <td></td>

                        <td class="excess_amount">` +

                        __number_f(excess_amount, false, false, __currency_precision) +

                        `</td>

                        <td>` +

                        excess_note +

                        `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/settlement-sw/sw-add-payment/excess-payment/` +

                        settlement_excess_payment_id +

                        `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                );
                if (auto_balance_attr) {
                    window.__settlementsw_auto_balance_state = window.__settlementsw_auto_balance_pending;
                    window.__settlementsw_auto_balance_pending = null;
                }

                console.log('working');

                $(".excess_fields").val("");

                $(".cash_fields").val("");

                

                $("#excess_number").val(result.excess_number);

                calculateTotal("#excess_table", ".excess_amount", ".excess_total");

                add_payment(excess_amount);

                toastr.success("Added!");

            }

            console.log('result',result)

        },

    });

});

</script>
