<div class="card pos-standard-card rn-summary-card">
    <div class="card-header"><strong>@lang('restaurantnew::lang.summary')</strong></div>
    <div class="card-body">
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.subtotal')</span><strong>{{ number_format($bill->subtotal, 4) }}</strong></div>
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.discount')</span><strong>{{ number_format($bill->discount_amount, 4) }}</strong></div>
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.tax')</span><strong>{{ number_format($bill->tax_amount, 4) }}</strong></div>
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.service_charge')</span><strong>{{ number_format($bill->service_charge_amount, 4) }}</strong></div>
        <div class="rn-total-row grand"><span>@lang('restaurantnew::lang.grand_total')</span><strong>{{ number_format($bill->grand_total, 4) }}</strong></div>
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.paid')</span><strong>{{ number_format($bill->paid_total, 4) }}</strong></div>
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.balance')</span><strong>{{ number_format($bill->balance_due, 4) }}</strong></div>
        <div class="rn-total-row"><span>@lang('restaurantnew::lang.change')</span><strong>{{ number_format($bill->change_amount, 4) }}</strong></div>
    </div>
</div>
