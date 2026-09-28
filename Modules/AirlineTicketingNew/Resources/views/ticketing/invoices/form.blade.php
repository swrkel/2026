@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::ticketing.create_invoice'))
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.invoices.store',$ticket) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::ticketing.create_invoice_for') }} {{ $ticket->ticket_no }}</h4></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.invoice_date') }}</label><input type="date" class="form-control" name="invoice_date" value="{{ date('Y-m-d') }}"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.customer_type') }}</label><select class="form-control" name="customer_type"><option value="individual">Individual</option><option value="corporate">Corporate</option><option value="walk_in">Walk-in</option></select></div></div>
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.remarks') }}</label><textarea class="form-control" name="remarks"></textarea></div></div>
</div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-primary">{{ __('airlineticketingnew::ticketing.create_invoice') }}</button></div>
</form></div>
@endsection
