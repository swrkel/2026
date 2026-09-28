@if($bill->bill_status !== 'void')
<div class="card pos-standard-card mt-3">
    <div class="card-header"><strong>@lang('restaurantnew::lang.refund_void')</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('restaurantnew.billing.refund', $bill->id) }}" class="mb-3">
            @csrf
            <input type="number" step="0.0001" name="amount" class="form-control mb-2" placeholder="@lang('restaurantnew::lang.refund_amount')">
            <input type="text" name="reason" class="form-control mb-2" placeholder="@lang('restaurantnew::lang.reason')">
            <input type="hidden" name="refund_method" value="cash">
            <button class="btn btn-warning btn-sm">@lang('restaurantnew::lang.create_refund')</button>
        </form>
        <form method="POST" action="{{ route('restaurantnew.billing.void', $bill->id) }}">
            @csrf
            <input type="text" name="reason" class="form-control mb-2" required placeholder="@lang('restaurantnew::lang.void_reason')">
            <button class="btn btn-danger btn-sm">@lang('restaurantnew::lang.void_bill')</button>
        </form>
    </div>
</div>
@endif
