<div class="restaurantnew-card restaurantnew-running-order">
    <div class="restaurantnew-card-title">@lang('restaurantnew::lang.running_order')</div>
    <div class="restaurantnew-order-meta">
        <span id="restaurantnew_order_no">@lang('restaurantnew::lang.new_order')</span>
        <span id="restaurantnew_order_type">@lang('restaurantnew::lang.dine_in')</span>
    </div>
    <div id="restaurantnew_order_lines" class="restaurantnew-order-lines">
        <div class="restaurantnew-muted">@lang('restaurantnew::lang.no_items_added')</div>
    </div>
    <div class="restaurantnew-totals">
        <div><span>@lang('restaurantnew::lang.subtotal')</span><strong id="restaurantnew_subtotal">0.0000</strong></div>
        <div><span>@lang('restaurantnew::lang.tax')</span><strong id="restaurantnew_tax">0.0000</strong></div>
        <div><span>@lang('restaurantnew::lang.service_charge')</span><strong id="restaurantnew_service_charge">0.0000</strong></div>
        <div class="restaurantnew-grand-total"><span>@lang('restaurantnew::lang.grand_total')</span><strong id="restaurantnew_grand_total">0.0000</strong></div>
    </div>
    <div class="restaurantnew-payment-actions">
        <button type="button" class="btn btn-warning btn-block" id="restaurantnew_send_kot">@lang('restaurantnew::lang.send_to_kitchen')</button>
        <button type="button" class="btn btn-success btn-block" id="restaurantnew_pay_close">@lang('restaurantnew::lang.pay_and_close')</button>
    </div>
</div>
