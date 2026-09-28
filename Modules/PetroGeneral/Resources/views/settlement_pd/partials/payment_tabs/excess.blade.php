<div class="col-md-12">

    <div class="row">

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('excess_amount', __( 'petrogeneral::lang.amount' ) ) !!}

                {!! Form::text('excess_amount', null, ['class' => 'form-control excess_fields input_number

                excess_amount', 'required',

                'placeholder' => __(

                'petrogeneral::lang.amount' ) ]); !!}

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

                    <th>@lang('petrogeneral::lang.amount' )</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('petrogeneral::lang.action' )</th>

                </tr>

            </thead>

            <tbody id="excess_table_body">

                @php
                    $excess_total = $settlement_excess_payments->sum(function ($payment) {
                        return abs($payment->amount);
                    });
                @endphp

                @foreach ($settlement_excess_payments as $excess_payment)

                    <tr>

                        <td></td>

                        <td class="excess_amount">{{number_format(abs($excess_payment->amount), $currency_precision)}}</td>

                        <td>{{$excess_payment->note}}</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petro-general/settlement/payment/delete-excess-payment/{{$excess_payment->id}}"><i

                                    class="fa fa-times"></i></button></td>

                    </tr>

                @endforeach

            </tbody>



            <tfoot>

                <tr>

                    <td style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.total') :</td>

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

    

    

function calculateTotal(table_name, class_name_td, output_element) {

    let total = 0.0;

    $(table_name + " tbody")

        .find(class_name_td)

        .each(function () {

            total += parseFloat(__number_uf($(this).text()));

        });

    $(output_element).text(__number_f(total, false, false, __currency_precision));

}

// Settlement PD: Prevent excess balance stacking on modal reopen.
// Cache/restore totals per settlement_no and reset modal-scoped state.
(function () {
    var NS = '.petroSettlementPdExcess';

    function getSettlementNo($ctx) {
        return ($ctx.find('#settlement_no').val() || $ctx.find('input[name="settlement_no"]').val() || '').toString();
    }

    function hasSettlementForm($ctx) {
        return $ctx.find('#settlement_form').length > 0;
    }

    function readTotals($ctx) {
        return {
            total_amount_val: ($ctx.find('#total_amount').val() || '').toString(),
            total_paid_val: ($ctx.find('#total_paid').val() || '').toString(),
            total_balance_val: ($ctx.find('#total_balance').val() || '').toString(),
            total_amount_text: ($ctx.find('.total_amount').text() || '').toString(),
            total_paid_text: ($ctx.find('.total_paid').text() || '').toString(),
            total_balance_text: ($ctx.find('.total_balance').text() || '').toString(),
        };
    }

    function writeTotals($ctx, totals) {
        if (!totals) {
            return;
        }

        $ctx.find('#total_amount').val(totals.total_amount_val);
        $ctx.find('#total_paid').val(totals.total_paid_val);
        $ctx.find('#total_balance').val(totals.total_balance_val);

        $ctx.find('.total_amount').text(totals.total_amount_text);
        $ctx.find('.total_paid').text(totals.total_paid_text);
        $ctx.find('.total_balance').text(totals.total_balance_text);
    }

    window.__petro_settlement_pd_totals_cache = window.__petro_settlement_pd_totals_cache || {};

    // Snapshot totals when the modal is closing (source-of-truth for next reopen).
    $(document)
        .off('hide.bs.modal' + NS, '.modal')
        .on('hide.bs.modal' + NS, '.modal', function () {
            var $modal = $(this);
            if (!hasSettlementForm($modal)) {
                return;
            }

            var settlementNo = getSettlementNo($modal);
            if (!settlementNo) {
                return;
            }

            window.__petro_settlement_pd_totals_cache[settlementNo] = readTotals($modal);

            // Reset any cached modal state used for calculations.
            window.__petro_auto_balance_pending = null;
            window.__petro_auto_balance_state = null;
        });

    // Restore totals on modal show to prevent recalculation stacking.
    $(document)
        .off('shown.bs.modal' + NS, '.modal')
        .on('shown.bs.modal' + NS, '.modal', function () {
            var $modal = $(this);
            if (!hasSettlementForm($modal)) {
                return;
            }

            var settlementNo = getSettlementNo($modal);
            if (!settlementNo) {
                return;
            }

            var cached = window.__petro_settlement_pd_totals_cache[settlementNo];
            if (!cached) {
                return;
            }

            // Defer to run after other modal shown handlers (avoid incremental stacking).
            setTimeout(function () {
                writeTotals($modal, cached);
                if (typeof show_hide_excess_shortage_tab === 'function') {
                    show_hide_excess_shortage_tab();
                }
                if (typeof rebuildExcessTable === 'function') {
                    rebuildExcessTable();
                }
            }, 0);
        });
})();

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
        const amount = Math.abs(parseFloat(row.amount) || 0);
        total += amount;
        const safeNote = $('<div>').text(row.note || '').html();
        const deleteBtn = row.deleteHref
            ? `<button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="${row.deleteHref}"><i class="fa fa-times"></i></button>`
            : '';
        const autoAttr = row.autoBalance ? ' data-auto-balance="1"' : '';

        $tbody.append(
            `<tr${autoAttr}>
                <td></td>
                <td class="excess_amount">${__number_f(amount, false, false, __currency_precision)}</td>
                <td>${safeNote}</td>
                <td>${deleteBtn}</td>
            </tr>`
        );
    });

    $(".excess_total").text(__number_f(total, false, false, __currency_precision));
    $("#excess_total").val(total);
}

</script>





<script>

    

    // $(document).on("click", ".excess_add_btn", function () {
$(document)
    .off("click.petroSettlementPdExcess", ".excess_add_btn")
    .on("click.petroSettlementPdExcess", ".excess_add_btn", function () {   

    var excess_amount_input = $("#excess_amount").val();

    var excess_note = $("#excess_note").val();

    if (excess_amount_input == "") {

        toastr.error("Please enter amount");

        return false;

    }

    var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));
    if (!isNaN(current_balance) && current_balance > 0) {

        toastr.error("Balance is positive. Please use Shortage");

        return false;

    }

    var excess_amount = __read_number($("#excess_amount")) ?? 0;


    excess_amount = Math.abs(excess_amount);

    if (isNaN(excess_amount) || excess_amount === 0) {

        toastr.error("Please enter a positive amount");

        return false;

    }

    var excess_amount_signed = 0 - Math.abs(excess_amount);

    var settlement_no = $("#settlement_no").val();

    var is_edit = $("#is_edit").val() ?? 0;

    

    $.ajax({

        method: "post",

        url: "/petro-general/settlement/payment/save-excess-payment",

        data: {

            settlement_no: settlement_no,

            amount: excess_amount_signed,

            note: excess_note,

            is_edit: is_edit

        },

        success: function (result) {

            if (!result.success) {

                toastr.error(result.msg);

            } else {

                

                settlement_excess_payment_id = result.settlement_excess_payment_id;

                var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'excess')
                    ? ' data-auto-balance="1"' : '';
                $("#excess_table tbody").append(

                    `

                    <tr` + auto_balance_attr + `> 

                        <td></td>

                        <td class="excess_amount">` +

                        __number_f(Math.abs(excess_amount), false, false, __currency_precision) +

                        `</td>

                        <td>` +

                        excess_note +

                        `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petro-general/settlement/payment/delete-excess-payment/` +

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

                // Update Total Paid / Balance only for auto-balance flow (avoid changing existing manual behavior)
                if (auto_balance_attr && typeof add_payment === 'function') {
                    add_payment(excess_amount_signed);
                }


                toastr.success("Added!");

            }

        },

    });

});

</script>

<script>
$(document)
    .off('shown.bs.tab.petroSettlementPdExcess shown.bs.tab', 'a.excess_tab')
    .on('shown.bs.tab.petroSettlementPdExcess', 'a.excess_tab', function () {
    if (typeof rebuildExcessTable === 'function') {
        rebuildExcessTable();
    }
});
</script>
