<div class="box box-solid pos-bill-summary">
    <div class="box-body">
        <div class="summary-row"><span>{{ __('pos::page_003.subtotal') }}</span><strong id="pos_summary_subtotal">{{ number_format($cart['subtotal'] ?? 0, 4) }}</strong></div>
        <div class="summary-row"><span>{{ __('pos::page_003.discount') }}</span><strong id="pos_summary_discount">{{ number_format($cart['discount_amount'] ?? 0, 4) }}</strong></div>
        <div class="summary-row"><span>{{ __('pos::page_003.tax') }}</span><strong id="pos_summary_tax">{{ number_format($cart['tax_amount'] ?? 0, 4) }}</strong></div>
        <div class="summary-row grand-total"><span>{{ __('pos::page_003.grand_total') }}</span><strong id="pos_summary_total">{{ number_format($cart['total_amount'] ?? 0, 4) }}</strong></div>
        <button type="button" class="btn btn-success btn-lg btn-block" id="pos_pay_btn"><i class="fa fa-credit-card"></i> {{ __('pos::page_003.pay') }}</button>
    </div>
</div>
