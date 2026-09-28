@extends('airlineticketingnew::layouts.app')
@section('atn-title', $quotation->quotation_no)
@section('atn-content')
@include('airlineticketingnew::transactions.navigation')
<div class="atn-panel"><div class="atn-panel-header atn-flex-between"><h4>{{ $quotation->quotation_no }}</h4><span class="label label-info">{{ $quotation->status }}</span></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><strong>{{ __('airlineticketingnew::transactions.quotation_date') }}</strong><br>{{ optional($quotation->quotation_date)->format('Y-m-d') }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::transactions.valid_until') }}</strong><br>{{ optional($quotation->valid_until)->format('Y-m-d') }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::transactions.currency') }}</strong><br>{{ $quotation->currency_code }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::transactions.grand_total') }}</strong><br>{{ number_format((float)$quotation->grand_total,4) }}</div>
</div></div></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>#</th><th>{{ __('airlineticketingnew::transactions.flight_number') }}</th><th>{{ __('airlineticketingnew::transactions.departure') }}</th><th>{{ __('airlineticketingnew::transactions.arrival') }}</th><th>{{ __('airlineticketingnew::transactions.total') }}</th></tr></thead><tbody>
@foreach($quotation->segments as $segment)<tr><td>{{ $segment->segment_no }}</td><td>{{ $segment->flight_number }}</td><td>{{ $segment->departure_at }}</td><td>{{ $segment->arrival_at }}</td><td class="text-right">{{ number_format((float)$segment->segment_total,4) }}</td></tr>@endforeach
</tbody></table></div></div>
@if($quotation->status !== 'converted')
@can('airline_ticketing_new.reservations.create')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.quotations.convert',$quotation) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::transactions.convert_to_reservation') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.pnr_code') }}</label><input class="form-control" name="pnr_code"></div></div><div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.reservation_date') }}</label><input type="date" class="form-control" name="reservation_date" value="{{ date('Y-m-d') }}"></div></div><div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.ticketing_deadline') }}</label><input type="datetime-local" class="form-control" name="ticketing_deadline"></div></div><div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::transactions.remarks') }}</label><textarea class="form-control" name="remarks"></textarea></div></div></div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-success"><i class="fa fa-check"></i> {{ __('airlineticketingnew::transactions.create_reservation') }}</button></div>
</form></div>
@endcan
@endif
@endsection
