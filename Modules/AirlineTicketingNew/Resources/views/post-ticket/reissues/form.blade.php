@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::postticket.request_reissue'))
@section('atn-content')
@include('airlineticketingnew::post-ticket.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.reissues.store',$ticket) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::postticket.request_reissue') }} - {{ $ticket->ticket_no }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-3"><label>Request Date</label><input type="date" class="form-control" name="request_date" value="{{ date('Y-m-d') }}"></div>
<div class="col-md-3"><label>Fare Difference</label><input class="form-control" name="fare_difference" value="0"></div>
<div class="col-md-3"><label>Tax Difference</label><input class="form-control" name="tax_difference" value="0"></div>
<div class="col-md-3"><label>Service Fee</label><input class="form-control" name="service_fee" value="0"></div>
<div class="col-md-3"><label>Penalty</label><input class="form-control" name="penalty_amount" value="0"></div>
<div class="col-md-12"><label>Reason</label><textarea class="form-control" name="reason" required></textarea></div>
<div class="col-md-12"><label>Remarks</label><textarea class="form-control" name="remarks"></textarea></div></div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-primary">{{ __('airlineticketingnew::postticket.submit') }}</button></div>
</form></div>
@endsection
