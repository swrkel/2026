@php
    $add_payment_settlement_no = !empty($active_settlement) ? $active_settlement->settlement_no : $settlement_no;
    $payment_due_total = ($payment_meter_sale_total ?? 0)
        + ($pump_other_sale_final_total ?? 0)
        + ($payment_other_income_total ?? 0);
    $is_petropd_settlement = \Illuminate\Support\Str::startsWith((string) $add_payment_settlement_no, 'PDST');
@endphp
<div class="row">
    <div class="col-md-12" style="margin-top: 20px;">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petrogeneral::lang.meter_sale_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_meter_sale_total">{{ number_format($payment_meter_sale_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petrogeneral::lang.other_sale_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_other_sale_total">{{ number_format($pump_other_sale_final_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petrogeneral::lang.other_income_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_other_income_total">{{ number_format($payment_other_income_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petrogeneral::lang.customer_payment_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_customer_payment_total">{{ number_format($payment_customer_payment_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petrogeneral::lang.settlement_no') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="settlement_no">
                        {{ !empty($active_settlement) ? $active_settlement->settlement_no : $settlement_no }}
                    </span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petrogeneral::lang.shift_number') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="shift_number">
                        {{ !empty($shift_id) ? $shift_id : $show_shift_no  ?? '' }}</span>
                </div>
            </div>
            <br>
        </div>
        <div class="col-md-4"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="pull-right" style="padding-right: 10px; font-size : 17px; color: brown;">
            <strong>@lang('purchase.payment_due'):</strong> <span
                id="payment_due">{{ number_format($payment_due_total, $currency_precision) }}</span>
        </div>
    </div>
    <br>
    <br>
    <div class="col-md-12">
        <button type="button" id="add_payment" data-container=".add_payment"
            data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\AddPaymentController@create', array_merge(['settlement_no' => $add_payment_settlement_no, 'type' => 'settlement_pd', 'shift_ids' => $shift_id ?? null, 'pump_operator_id' => $pump_operator_id ?? null], $is_petropd_settlement ? ['source' => 'petropd'] : [])) }}"
            data-base-href="{{ action('\Modules\PetroGeneral\Http\Controllers\AddPaymentController@create', array_merge(['settlement_no' => $add_payment_settlement_no, 'type' => 'settlement_pd', 'pump_operator_id' => $pump_operator_id ?? null], $is_petropd_settlement ? ['source' => 'petropd'] : [])) }}"
            class="btn btn-primary btn-modal pull-right">@lang('petrogeneral::lang.payment_to_finalize')</button>
    </div>
</div>
<script>
// Listen for payment updated event and recalculate totals
$(document).on('payment:updated', function(e, response) {
    // When a payment is updated, we need to refresh the payment totals
    // Get the settlement number from the page
    var settlement_no = $('.settlement_no').text().trim();
    
    if (settlement_no) {
        // Fetch updated payment totals from server
        $.ajax({
            url: "{{ action('\Modules\PetroGeneral\Http\Controllers\SettlementPDController@getPaymentTabTotals') }}",
            method: 'GET',
            data: {
                settlement_no: settlement_no
            },
            success: function(result) {
                if (result.success) {
                    // Update the displayed totals
                    var precision = {{ $currency_precision ?? 2 }};
                    
                    $('.payment_meter_sale_total').text(
                        __number_f(result.meter_sale_total, false, false, precision)
                    );
                    $('.payment_other_sale_total').text(
                        __number_f(result.other_sale_total, false, false, precision)
                    );
                    $('.payment_other_income_total').text(
                        __number_f(result.other_income_total, false, false, precision)
                    );
                    $('.payment_customer_payment_total').text(
                        __number_f(result.customer_payment_total, false, false, precision)
                    );
                    
                    // Update the payment due total
                    var total_due = result.meter_sale_total + result.other_sale_total + 
                                   result.other_income_total + result.customer_payment_total;
                    $('#payment_due').text(
                        __number_f(total_due, false, false, precision)
                    );
                }
            },
            error: function(xhr) {
                console.error('Error updating payment totals:', xhr);
            }
        });
    }
});
</script>
