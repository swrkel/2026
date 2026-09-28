@extends('airlineticketingnew::layouts.app')
@section('atn-title', $invoice->invoice_no)
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-panel"><div class="atn-panel-header atn-flex-between"><h4>{{ $invoice->invoice_no }}</h4><span class="label label-info">{{ $invoice->status }}</span></div>
<div class="atn-panel-body"><div class="row">
@foreach(['grand_total','paid_total','due_total'] as $field)<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.'.$field) }}</strong><br>{{ number_format((float)$invoice->{$field},4) }}</div>@endforeach
<div class="col-md-3"><strong>{{ __('airlineticketingnew::ticketing.currency') }}</strong><br>{{ $invoice->currency_code }}</div>
</div></div></div>
@if((float)$invoice->due_total > 0)
@can('airline_ticketing_new.payments.create')
<a class="btn btn-success" href="{{ route('airline-ticketing-new.payments.create',$invoice) }}"><i class="fa fa-money"></i> {{ __('airlineticketingnew::ticketing.receive_payment') }}</a>
@endcan
@endif
@endsection
