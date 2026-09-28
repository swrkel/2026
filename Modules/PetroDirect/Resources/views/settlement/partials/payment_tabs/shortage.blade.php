@php
    $is_no_change_payment_modal = !empty($no_change);
@endphp

@unless($is_no_change_payment_modal)
<div class="col-md-12">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('shortage_amount', __( 'petrodirect::lang.amount' ) ) !!}
                {!! Form::text('shortage_amount', null, ['class' => 'form-control shortage_fields input_number
                shortage_amount', 'required',
                'placeholder' => __(
                'petrodirect::lang.amount' ) ]); !!}
                <div class=" text-center text-red shortage_amount_err hidden">
                  
                  <span class="total_amount">Not Allowed. Already Excess amount entered</span>
              </div>
            </div>
        </div>
        <div class="col-md-6">
                <div class="form-group">
                  {!! Form::label("shortage_note", __('lang_v1.payment_note') . ':') !!}
                  {!! Form::textarea("shortage_note", null, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}
                </div>
            </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-primary shortage_add"
                style="margin-top: 23px;">@lang('messages.add')</button>
        </div>
    </div>
</div>
@endunless
<br><br>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="shortage_table">
            <thead>
                <tr>
                    <th></th>
                    <th>@lang('petrodirect::lang.amount' )</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petrodirect::lang.action' )</th>
                </tr>
            </thead>
            <tbody id="shortage_table_body">
                @php
                $shortage_total = $settlement_shortage_payments->sum('amount');
                @endphp
                @foreach ($settlement_shortage_payments as $shortage_payment)
                <tr>
                    <td></td>
                    <td class="shortage_amount">{{number_format($shortage_payment->amount, $currency_precision)}}</td>
                    <td>{{$shortage_payment->note}}</td>
                    <td><button type="button" class="btn btn-xs btn-danger delete_shortage_payment"
                            data-href="/petrodirect/settlement/payment/delete-shortage-payment/{{$shortage_payment->id}}"
                            {{ $is_no_change_payment_modal ? 'disabled' : '' }}><i
                                class="fa fa-times"></i></button></td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.total') :</td>
                    <td style="text-align: left; font-weight: bold;" class="shortage_total">
                        {{number_format($shortage_total, $currency_precision)}}</td>
                </tr>
                <input type="hidden" value="{{$shortage_total}}" name="shortage_total" id="shortage_total">
            </tfoot>
        </table>
    </div>
</div>




<script>
    $(document).ready(function(){
        $("#shortage_customer_id").val($("#shortage_customer_id option:eq(0)").val()).trigger('change');
        
    });

    //shortage payments
    $(document).off("click", ".shortage_add").on("click", ".shortage_add", function () {
        if ($("#shortage_amount").val() == "") {
            toastr.error("Please enter amount");
            return false;
        }

        var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));
        
        // Skip balance validation if triggered by Balance to Operator (balance was already updated to 0)
        if (!window.__petro_balance_to_operator_active && !isNaN(current_balance) && current_balance < 0) {
            toastr.error("Balance is negative. Please use Excess");
            return false;
        }

        var shortage_amount = __read_number($("#shortage_amount")) ?? 0;

        if (isNaN(shortage_amount) || shortage_amount <= 0) {
            toastr.error("Please enter a positive amount for Shortage");
            return false;
        }

        var $form = $(this).closest('form');
        if (!$form.length) {
            $form = $("#settlement_form");
        }

        var settlement_no = $form.find('input[name="settlement_no"]').val() || $("#settlement_no").val();
        var settlement_id = $form.find('input[name="payment_settlement_id"]').val();
        var shortage_note = $("#shortage_note").val();
        var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;

        $.ajax({
            method: "post",
            url: "/petrodirect/settlement/payment/save-shortage-payment",
            data: {
                settlement_no: settlement_no,
                payment_settlement_id: settlement_id,
                amount: shortage_amount,
                note: shortage_note,
                is_edit: is_edit
            },
            success: function (result) {
                if (!result.success) {
                    toastr.error(result.msg);
                } else {
                    settlement_shortage_payment_id = result.settlement_shortage_payment_id;

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
                        && window.__petro_auto_balance_pending.type === 'shortage');

                    if (!is_auto_balance_row && !window.__petro_skip_add_payment) {
                        if (typeof window.add_payment === 'function') {
                            window.add_payment(shortage_amount);
                        } else if (typeof window.petroSettlementAddPaymentCore === 'function') {
                            window.petroSettlementAddPaymentCore(shortage_amount);
                        }
                    }

                    var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'shortage')
                        ? ' data-auto-balance="1"' : '';
                    $("#shortage_table tbody").append(
                        `
                        <tr` + auto_balance_attr + `> 
                            <td></td>
                            <td class="shortage_amount">` +
                            __number_f(shortage_amount, false, false, __currency_precision) +
                            `</td>
                            <td>` +
                            shortage_note +
                            `</td>
                            <td><button type="button" class="btn btn-xs btn-danger delete_shortage_payment" data-href="/petrodirect/settlement/payment/delete-shortage-payment/` +
                            settlement_shortage_payment_id +
                            `"><i class="fa fa-times"></i></button>
                            </td>
                        </tr>
                    `
                    );
                    if (auto_balance_attr) {
                        window.__petro_auto_balance_state = window.__petro_auto_balance_pending;
                        window.__petro_auto_balance_pending = null;
                    }

                    $(".shortage_fields").val("");
                    $(".cash_fields").val("");

                    calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");

                    toastr.success("Added!");
                }
            },
            error: function(xhr) {
                var msg = "Please enter a positive amount for Shortage";
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            }
        });
    });
</script>
