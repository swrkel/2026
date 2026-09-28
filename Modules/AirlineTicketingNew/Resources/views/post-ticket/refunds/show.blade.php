@extends('airlineticketingnew::layouts.app')
@section('atn-title', $refund->refund_no)
@section('atn-content')
@include('airlineticketingnew::post-ticket.navigation')
<div class="atn-panel"><div class="atn-panel-header"><h4>{{ $refund->refund_no }}</h4></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><strong>{{ __('airlineticketingnew::postticket.gross_amount') }}</strong><br>{{ number_format((float)$refund->gross_amount,4) }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::postticket.cancellation_fee') }}</strong><br>{{ number_format((float)$refund->cancellation_fee,4) }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::postticket.other_deductions') }}</strong><br>{{ number_format((float)$refund->other_deductions,4) }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::postticket.refund_amount') }}</strong><br>{{ number_format((float)$refund->refund_amount,4) }}</div>
</div></div></div>
@endsection
