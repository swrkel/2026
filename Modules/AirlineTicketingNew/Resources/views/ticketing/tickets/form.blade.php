@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::ticketing.issue_ticket'))
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.tickets.store',$reservation) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::ticketing.issue_ticket_for') }} {{ $reservation->reservation_no }}</h4></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.ticket_no') }}</label><input class="form-control" name="ticket_no" placeholder="{{ __('airlineticketingnew::ticketing.auto_number') }}"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.issue_date') }}</label><input type="date" class="form-control" name="issue_date" value="{{ date('Y-m-d') }}" required></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.ticket_type') }}</label><select class="form-control" name="ticket_type"><option value="normal">Normal</option><option value="reissue">Reissue</option><option value="exchange">Exchange</option></select></div></div>
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::ticketing.remarks') }}</label><textarea class="form-control" name="remarks"></textarea></div></div>
</div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-success"><i class="fa fa-ticket"></i> {{ __('airlineticketingnew::ticketing.issue_ticket') }}</button></div>
</form></div>
@endsection
