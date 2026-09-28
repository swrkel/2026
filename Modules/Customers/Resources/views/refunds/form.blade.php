@extends('customers::layouts.action', ['title' => 'Refund Payment'])
@section('customer_action_body')
<form method="POST" action="{{ route('customers.refunds.payment.store', $customer->id) }}">
    {{ csrf_field() }}
    <input type="hidden" name="refund_mode" value="refund_payment">
    <div class="row">
        <div class="col-md-4"><div class="form-group"><label>Customer</label><input class="form-control" value="{{ $customer->name }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>Refund Amount</label><input name="amount" class="form-control input_number" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>Date</label><input name="date" class="form-control" value="{{ date('Y-m-d') }}"></div></div>
        <div class="col-md-12"><div class="form-group"><label>Note</label><textarea name="note" class="form-control"></textarea></div></div>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
@endsection
