@php
    $add_payment_settlement_no = !empty($active_settlement) ? $active_settlement->settlement_no : $settlement_no;
@endphp
<div class="row">
    <div class="col-md-12" style="margin-top: 20px;">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('settlementsw::lang.meter_sale_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="payment_meter_sale_total">{{number_format( $payment_meter_sale_total, $currency_precision )}}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('settlementsw::lang.other_sale_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="payment_other_sale_total">{{number_format( $pump_other_sale_final_total, $currency_precision )}}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('settlementsw::lang.other_income_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="payment_other_income_total">{{number_format( $payment_other_income_total, $currency_precision )}}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('settlementsw::lang.customer_payment_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="payment_customer_payment_total">{{number_format( $payment_customer_payment_total, $currency_precision )}}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('settlementsw::lang.settlement_no') :
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
                    @lang('settlementsw::lang.shift_number') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="shift_number">{{ !empty($shift_number) ? $shift_number->shift_number : 'N/A' }}</span>
                </div>
            </div>
            <br>
        </div>
        <div class="col-md-4"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="pull-right" style="padding-right: 10px; font-size : 17px; color: brown;"><strong>@lang('purchase.payment_due'):</strong> <span
                id="payment_due">{{number_format( $payment_meter_sale_total+$pump_other_sale_final_total+$payment_other_income_total+$payment_customer_payment_total, $currency_precision )}}</span></div>
    </div>
    <br>
    <br>
    <div class="col-md-12">
<button type="button" id="add_payment"
    data-href="{{ route('sw-add-payment.create', ['settlement_no' => $add_payment_settlement_no]) }}"
    class="btn btn-primary pull-right">
    @lang('settlementsw::lang.payment_to_finalize')
</button>
    
</div>
</div>