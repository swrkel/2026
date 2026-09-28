@extends('airlineticketingnew::layouts.app')
@section('atn-title', $ticket->ticket_no)
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-panel"><div class="atn-panel-header atn-flex-between"><h4>{{ $ticket->ticket_no }}</h4><span class="label label-success">{{ $ticket->status }}</span></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.issue_date') }}</strong><br>{{ optional($ticket->issue_date)->format('Y-m-d') }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.currency') }}</strong><br>{{ $ticket->currency_code }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.grand_total') }}</strong><br>{{ number_format((float)$ticket->grand_total,4) }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.ticket_type') }}</strong><br>{{ $ticket->ticket_type }}</div>
</div></div></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>#</th><th>{{ __('airlineticketingnew::ticketing.flight_number') }}</th><th>{{ __('airlineticketingnew::ticketing.departure') }}</th><th>{{ __('airlineticketingnew::ticketing.arrival') }}</th><th>{{ __('airlineticketingnew::ticketing.coupon_status') }}</th></tr></thead><tbody>
@foreach($ticket->segments as $segment)<tr><td>{{ $segment->segment_no }}</td><td>{{ $segment->flight_number }}</td><td>{{ $segment->departure_at }}</td><td>{{ $segment->arrival_at }}</td><td>{{ $segment->coupon_status }}</td></tr>@endforeach
</tbody></table></div></div>
@can('airline_ticketing_new.invoices.create')
<a class="btn btn-primary" href="{{ route('airline-ticketing-new.invoices.create',$ticket) }}"><i class="fa fa-file-text"></i> {{ __('airlineticketingnew::ticketing.create_invoice') }}</a>
@endcan
@endsection
