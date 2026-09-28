@extends('airlineticketingnew::layouts.app')
@section('atn-title', $creditNote->credit_note_no)
@section('atn-content')
@include('airlineticketingnew::post-ticket.navigation')
<div class="atn-panel"><div class="atn-panel-header"><h4>{{ $creditNote->credit_note_no }}</h4></div>
<div class="atn-panel-body"><p><strong>{{ __('airlineticketingnew::postticket.credit_note_date') }}:</strong> {{ optional($creditNote->credit_note_date)->format('Y-m-d') }}</p><p><strong>{{ __('airlineticketingnew::postticket.amount') }}:</strong> {{ $creditNote->currency_code }} {{ number_format((float)$creditNote->amount,4) }}</p><p><strong>{{ __('airlineticketingnew::postticket.reason') }}:</strong> {{ $creditNote->reason }}</p></div></div>
@endsection
