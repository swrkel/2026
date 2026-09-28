@php
    $is_no_change_payment_modal = !empty($no_change);
@endphp

@unless($is_no_change_payment_modal)
<div class="col-md-12">

    <div class="row">

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('excess_amount', __( 'petrodirect::lang.amount' ) ) !!}

                {!! Form::text('excess_amount', null, ['class' => 'form-control excess_fields input_number

                excess_amount', 'required',

                'placeholder' => __(

                'petrodirect::lang.amount' ) ]); !!}

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
@endunless

<br><br>



<div class="row">

    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="excess_table">

            <thead>

                <tr>

                    <th></th>

                    <th>@lang('petrodirect::lang.amount' )</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('petrodirect::lang.action' )</th>

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

                        <td>{{$excess_payment->note}}</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petrodirect/settlement/payment/delete-excess-payment/{{$excess_payment->id}}" {{ $is_no_change_payment_modal ? 'disabled' : '' }}><i

                                    class="fa fa-times"></i></button></td>

                    </tr>

                @endforeach

            </tbody>



            <tfoot>

                <tr>

                    <td style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="excess_total">

                       {{number_format($excess_total, $currency_precision)}}</td>

                </tr>

                <input type="hidden" value="{{$excess_total}}" name="excess_total" id="excess_total">

            </tfoot>

        </table>

    </div>

</div>









<script>

    window.isPetroPdNoChangePaymentModal = @json($is_no_change_payment_modal);

    $(document).ready(function(){

        

       

        $("#excess_customer_id").val($("#excess_customer_id option:eq(0)").val()).trigger('change');

        

    });

    

    

function calculateTotal(table_name, class_name_td, output_element) {

    let total = 0.0;

    $(table_name + " tbody")

        .find(class_name_td)

        .each(function () {

            total += parseFloat(__number_uf($(this).text()));

        });

    $(output_element).text(__number_f(total, false, false, __currency_precision));

}

function rebuildExcessTable() {
    const rows = [];
    $("#excess_table tbody tr").each(function () {
        const $cells = $(this).find('td');
        const amountText = $cells.eq(1).text();
        const noteText = $cells.eq(2).text();
        const deleteHref = $(this).find('.delete_excess_payment').data('href') || '';
        const autoBalance = ($(this).attr('data-auto-balance') || '') === '1';

        rows.push({
            amount: __number_uf(amountText),
            note: noteText,
            deleteHref: deleteHref,
            autoBalance: autoBalance
        });
    });

    const $tbody = $("#excess_table tbody");
    $tbody.empty();

    let total = 0.0;
    rows.forEach(function (row) {
        const amount = parseFloat(row.amount) || 0;
        total += amount;
        const safeNote = $('<div>').text(row.note || '').html();
        const deleteBtn = row.deleteHref
            ? `<button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="${row.deleteHref}" ${window.isPetroPdNoChangePaymentModal ? 'disabled' : ''}><i class="fa fa-times"></i></button>`
            : '';
        const autoAttr = row.autoBalance ? ' data-auto-balance="1"' : '';
        const deleteTd = `<td>${deleteBtn}</td>`;

        $tbody.append(
            `<tr${autoAttr}>
                <td></td>
                <td class="excess_amount">${__number_f(amount, false, false, __currency_precision)}</td>
                <td>${safeNote}</td>
                ${deleteTd}
            </tr>`
        );
    });

    $(".excess_total").text(__number_f(total, false, false, __currency_precision));
    $("#excess_total").val(total);
}

</script>





<script>

    

    // $(document).on("click", ".excess_add_btn", function () {
$(document).off("click", ".excess_add_btn").on("click", ".excess_add_btn", function () {

    var excess_amount_input = $("#excess_amount").val();

    var excess_note = $("#excess_note").val();

    if (excess_amount_input == "") {

        toastr.error("Please enter amount");

        return false;

    }

    var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));
    
    // Skip balance validation if triggered by Balance to Operator (balance was already updated to 0)
    if (!window.__petro_balance_to_operator_active && !isNaN(current_balance) && current_balance > 0) {

        toastr.error("Balance is positive. Please use Shortage");

        return false;

    }

    var excess_amount = __read_number($("#excess_amount")) ?? 0;

    if (isNaN(excess_amount) || excess_amount >= 0) {

        toastr.error("Please enter a negative amount for Excess");

        return false;

    }

    var $form = $(this).closest('form');
    if (!$form.length) {
        $form = $("#settlement_form");
    }

    var settlement_no = $form.find('input[name="settlement_no"]').val() || $("#settlement_no").val();
    var settlement_id = $form.find('input[name="payment_settlement_id"]').val();

    var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;

    

    $.ajax({

        method: "post",

        url: "/petrodirect/settlement/payment/save-excess-payment",

        data: {

            settlement_no: settlement_no,
            payment_settlement_id: settlement_id,

            amount: excess_amount,

            note: excess_note,

            is_edit: is_edit

        },

        success: function (result) {

            if (!result.success) {

                toastr.error(result.msg);

            } else {

                

                settlement_excess_payment_id = result.settlement_excess_payment_id;

                // CRITICAL: Only call add_payment if NOT triggered by Balance to Operator
                // When Balance to Operator is active, we want balance to stay at 0
                /*
                 * IS1999 / IS1982 #2: decide this from the pending auto-balance state, not from
                 * the __petro_skip_add_payment timer flag.
                 *
                 * "Balance to Operator" sets __petro_skip_add_payment = true, adjusts the totals
                 * itself, triggers this Add, then clears the flag on a fixed 1500 ms timeout.
                 * When the save round trip takes longer than that, the flag is already false by
                 * the time this success handler runs, so add_payment() applies the amount a
                 * SECOND time on top of the adjustment the button already made. The balance
                 * overshoots zero by exactly twice the original balance and settles back on the
                 * old figure instead of 0.00.
                 *
                 * __petro_auto_balance_pending is set before the Add is triggered and is cleared
                 * a few lines below in this same handler, so it is true for exactly the one row
                 * the button created - no timing involved.
                 */
                var is_auto_balance_row = !!(window.__petro_auto_balance_pending
                    && window.__petro_auto_balance_pending.type === 'excess');

                if (!is_auto_balance_row && !window.__petro_skip_add_payment) {
                    if (typeof window.add_payment === 'function') {
                        window.add_payment(excess_amount);
                    } else if (typeof window.petroSettlementAddPaymentCore === 'function') {
                        window.petroSettlementAddPaymentCore(excess_amount);
                    }
                }

                var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'excess')
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

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petrodirect/settlement/payment/delete-excess-payment/` +

                        settlement_excess_payment_id +

                        `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                );
                if (auto_balance_attr) {
                    window.__petro_auto_balance_state = window.__petro_auto_balance_pending;
                    window.__petro_auto_balance_pending = null;
                }

                $(".excess_fields").val("");

                $(".cash_fields").val("");

                

                $("#excess_number").val(result.excess_number);

                if (typeof rebuildExcessTable === 'function') {
                    rebuildExcessTable();
                } else {
                    calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                }

                // Note: add_payment was already called above if not Balance to Operator
                // This duplicate call has been removed to prevent double adjustment

                toastr.success("Added!");

            }

        },
        error: function (xhr) {
            var msg = "Please enter a negative amount for Excess";
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            toastr.error(msg);
        }

    });

});

</script>

<script>
$(document).off('shown.bs.tab', '.s271-direct-payment-tabs .excess_tab').on('shown.bs.tab', '.s271-direct-payment-tabs .excess_tab', function () {
    if (typeof rebuildExcessTable === 'function') {
        rebuildExcessTable();
    }
});
</script>
