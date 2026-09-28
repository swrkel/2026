@extends('airlineticketingnew::layouts.app')
@section('atn-title','Executive Dashboard')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-toolbar">
<form method="GET" class="atn-inline-filters">
<input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
<input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
<button class="btn btn-primary">Apply</button>
</form>
</div>
<div class="atn-kpi-grid">
@foreach($metrics as $key=>$value)
<div class="atn-kpi"><div class="atn-kpi-label">{{ str_replace('_',' ',$key) }}</div><div class="atn-kpi-value">{{ number_format((float)$value,4) }}</div></div>
@endforeach
</div>
@endsection
