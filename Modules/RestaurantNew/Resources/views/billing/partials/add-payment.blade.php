@if($bill->bill_status !== 'void')
<form method="POST" action="{{ route('restaurantnew.billing.payment', $bill->id) }}" class="card pos-standard-card mt-3">
    @csrf
    <div class="card-header"><strong>@lang('restaurantnew::lang.add_payment')</strong></div>
    <div class="card-body">
        <div class="form-group">
            <label>@lang('restaurantnew::lang.payment_method')</label>
            <select name="payment_method" class="form-control">
                <option value="cash">@lang('restaurantnew::lang.cash')</option>
                <option value="card">@lang('restaurantnew::lang.card')</option>
                <option value="bank_transfer">@lang('restaurantnew::lang.bank_transfer')</option>
                <option value="credit">@lang('restaurantnew::lang.credit')</option>
            </select>
        </div>
        <div class="form-group">
            <label>@lang('restaurantnew::lang.amount')</label>
            <input type="number" step="0.0001" name="amount" class="form-control" value="{{ $bill->balance_due }}">
        </div>
        <div class="form-group">
            <label>@lang('restaurantnew::lang.reference_no')</label>
            <input type="text" name="reference_no" class="form-control">
        </div>
    </div>
    <div class="card-footer text-right"><button class="btn btn-primary">@lang('restaurantnew::lang.save_payment')</button></div>
</form>
@endif
