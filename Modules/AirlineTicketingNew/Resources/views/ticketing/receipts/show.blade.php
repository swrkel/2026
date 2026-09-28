@extends('airlineticketingnew::layouts.app')
@section('atn-title', $receipt->receipt_no)
@section('atn-content')
<div class="atn-panel"><div class="atn-panel-header atn-flex-between"><h4>{{ $receipt->receipt_no }}</h4><a class="btn btn-primary" href="{{ route('airline-ticketing-new.receipts.print',$receipt) }}" target="_blank"><i class="fa fa-print"></i> {{ __('airlineticketingnew::ticketing.print') }}</a></div>
<div class="atn-panel-body"><p><strong>{{ __('airlineticketingnew::ticketing.receipt_date') }}:</strong> {{ optional($receipt->receipt_date)->format('Y-m-d') }}</p><p><strong>{{ __('airlineticketingnew::ticketing.amount') }}:</strong> {{ $receipt->currency_code }} {{ number_format((float)$receipt->amount,4) }}</p></div></div>
@endsection
