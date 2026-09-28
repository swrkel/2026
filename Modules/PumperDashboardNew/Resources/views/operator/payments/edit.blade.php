@extends('pumperdashboardnew::layouts.operator')
@section('title','Edit Payment')
@section('pone_content')
<div class="pone-page-head"><div><div class="pone-page-kicker">{{ $payment->payment_number }}</div><h1>Edit Payment</h1><p>The original and revised values are retained in the edit history.</p></div><div class="pone-actions"><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.payments.show',$payment) }}">View Record</a></div></div>
<div class="pone-panel"><div class="pone-panel-body">@include('pumperdashboardnew::operator.payments._form',['action'=>route('pumper-dashboard-new.operator.payments.update',$payment)])</div></div>
@endsection
