@php
    $pos_total = $settlement_pos_sales->sum('amount') ?? 0;
@endphp

<div class="col-md-12">
    <div class="row">
        <div class="col-md-3 pos_to_disable">
            <div class="form-group">
                {!! Form::label('pos_customer_id', __('petro::lang.customer').':') !!}
                {!! Form::select('pos_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width: 100%;']); !!}
            </div>
        </div>
        <div class="col-md-3 pos_to_disable">
            <div class="form-group">
                {!! Form::label('pos_amount', __( 'petro::lang.amount' ) ) !!}
                {!! Form::text('pos_amount', null, ['class' => 'form-control pos_fields cust_input_number pos_amount', 'required', 'placeholder' => __( 'petro::lang.amount' ) ]); !!}
            </div>
        </div>
        <div class="col-md-5 pos_to_disable">
            <div class="form-group">
              {!! Form::label("pos_note", __('lang_v1.payment_note') . ':') !!}
              {!! Form::textarea("pos_note", null, ['class' => 'form-control pos_fields', 'rows' => 3]); !!}
            </div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-primary pos_to_disable pos_add_updated" style="margin-top: 23px;">@lang('messages.add')</button>
        </div>
    </div>
</div>

<br><br>

<div class="row" style="margin-top: 10px">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="pos_table">
            <thead>
                <tr>
                    <th>@lang('petro::lang.cusotmer_name' )</th>
                    <th>@lang('petro::lang.amount' )</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petro::lang.action' )</th>
                </tr>
            </thead>
            <tbody id="pos_table_body">
                @foreach ($settlement_pos_sales as $pos_sale)
                    <tr class="pos-paymt-{{ $pos_sale->customer_payment_id }}">
                        <td>{{ $pos_sale->customer_name }}</td>
                        <td class="pos_amount">{{ number_format($pos_sale->amount, $currency_precision) }}</td>
                        <td>{{ $pos_sale->note }}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger delete_pos_payment" 
                                data-href="/petro/settlement/payment/delete-pos-payment/{{ $pos_sale->id }}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: right; font-weight: bold;">
                        Total POS Amount Today :
                    </td>
                    <td style="text-align: left; font-weight: bold;" class="pos_total">
                        {{ number_format($pos_total, $currency_precision) }}
                    </td>
                </tr>
                <input type="hidden" value="{{ $pos_total }}" name="pos_total" id="pos_total">
            </tfoot>
        </table>
    </div>
</div>

<script>
    $('#pos_customer_id').select2();
    $(document).ready(function(){
        $("#pos_customer_id").val($("#pos_customer_id option:eq(0)").val()).trigger('change');
    });
</script>

<script>
$(document).off("click", ".pos_add_updated").on("click", ".pos_add_updated", function () {
    if ($("#pos_amount").val() == "") {
        toastr.error("Please enter amount");
        return false;
    }

    var pos_customer_id = $("#pos_customer_id").val();
    var pos_amount = $("#pos_amount").val();
    var settlement_no = $("#settlement_no").val();
    var customer_name = $("#pos_customer_id :selected").text();
    var pos_note = $("#pos_note").val();
    var is_edit = $("#is_edit").val() ?? 0;

    $.ajax({
        method: "post",
        url: "/petro/settlement/payment/save-pos-payment",
        data: {
            customer_id: pos_customer_id,
            amount: pos_amount,
            settlement_no: settlement_no,
            note: pos_note,
            is_edit: is_edit,
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
            } else {
                console.log("POS payment added successfully", result);
                settlement_pos_payment_id = result.settlement_pos_payment_id;
                add_payment(pos_amount);

                // Build new row
                let newRow = `
                    <tr> 
                        <td>${customer_name}</td>
                        <td class="pos_amount">${__number_f(pos_amount, false, false, __currency_precision)}</td>
                        <td>${pos_note}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger delete_pos_payment" 
                                data-href="/petro/settlement/payment/delete-pos-payment/${settlement_pos_payment_id}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;

                $("#pos_table tbody").append(newRow);
                $(".pos_fields").val("");
                calculateTotal("#pos_table", ".pos_amount", ".pos_total");

                toastr.success("POS Payment Successfully Added!");
            }
        },
    });
});

// Delete POS payment
$(document).on("click", ".delete_pos_payment", function () {
    var url = $(this).attr("data-href");
    var $row = $(this).closest("tr");
    var amount = parseFloat($row.find(".pos_amount").text().replace(/,/g, ""));

    swal({
        title: LANG.sure,
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            $.ajax({
                method: "DELETE",
                url: url,
                dataType: "json",
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $row.remove();
                        calculateTotal("#pos_table", ".pos_amount", ".pos_total");
                        
                        // Update balance
                        let total_balance = parseFloat($("#total_balance").val().replace(/,/g, ""));
                        let total_paid = parseFloat($("#total_paid").val());
                        total_balance = total_balance + amount;
                        total_paid = total_paid - amount;
                        $("#total_balance").val(__number_f(total_balance, false, false, __currency_precision));
                        $("#total_paid").val(total_paid);
                        $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
                        $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));
                        
                        show_hide_excess_shortage_tab();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});
</script>