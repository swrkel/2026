<div class="rn-payment-box">
    <h5>@lang('restaurantnew::lang.payment')</h5>
    <div class="row rn-payment-row">
        <div class="col-md-4 form-group">
            <label>@lang('restaurantnew::lang.payment_method')</label>
            <select name="payments[0][payment_method]" class="form-control">
                <option value="cash">@lang('restaurantnew::lang.cash')</option>
                <option value="card">@lang('restaurantnew::lang.card')</option>
                <option value="bank_transfer">@lang('restaurantnew::lang.bank_transfer')</option>
                <option value="credit">@lang('restaurantnew::lang.credit')</option>
            </select>
        </div>
        <div class="col-md-4 form-group">
            <label>@lang('restaurantnew::lang.amount')</label>
            <input type="number" step="0.0001" name="payments[0][amount]" class="form-control" value="0">
        </div>
        <div class="col-md-4 form-group">
            <label>@lang('restaurantnew::lang.reference_no')</label>
            <input type="text" name="payments[0][reference_no]" class="form-control">
        </div>
    </div>
</div>
