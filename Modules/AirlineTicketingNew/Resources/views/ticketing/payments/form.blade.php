@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::ticketing.receive_payment'))
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.payments.store',$invoice) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::ticketing.receive_payment_for') }} {{ $invoice->invoice_no }}</h4></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.payment_date') }}</label><input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.method') }}</label><select class="form-control" name="payment_method"><option value="cash">Cash</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="credit">Credit</option><option value="other">Other</option></select></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.amount') }}</label><input class="form-control input_number" name="amount" value="{{ $invoice->due_total }}"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.reference_no') }}</label><input class="form-control" name="reference_no"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.payment_account') }}</label><input class="form-control" name="payment_account"></div></div>
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.remarks') }}</label><textarea class="form-control" name="remarks"></textarea></div></div>
</div></div><div class="atn-panel-footer text-right"><button class="btn btn-success">{{ __('airlineticketingnew::ticketing.receive_payment') }}</button></div>
</form></div>
@endsection
