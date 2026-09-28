@extends('airlineticketingnew::layouts.app')
@section('atn-title', $payment->payment_no)
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-panel"><div class="atn-panel-header"><h4>{{ $payment->payment_no }}</h4></div>
<div class="atn-panel-body"><div class="row">
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.payment_date') }}</strong><br>{{ optional($payment->payment_date)->format('Y-m-d') }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.method') }}</strong><br>{{ $payment->payment_method }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.amount') }}</strong><br>{{ number_format((float)$payment->amount,4) }}</div>
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.reference_no') }}</strong><br>{{ $payment->reference_no }}</div>
</div></div></div>
@endsection
