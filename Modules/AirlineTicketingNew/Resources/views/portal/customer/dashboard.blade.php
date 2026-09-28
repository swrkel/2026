@extends('airlineticketingnew::layouts.app')
@section('atn-title','Customer Portal')
@section('atn-content')
<div class="atn-kpi-grid">
@foreach(['reservations'=>'Reservations','tickets'=>'Tickets','outstanding'=>'Outstanding'] as $key=>$label)
<div class="atn-kpi"><div class="atn-kpi-label">{{ $label }}</div><div class="atn-kpi-value">{{ number_format((float)$metrics[$key],4) }}</div></div>
@endforeach
</div>
@endsection
