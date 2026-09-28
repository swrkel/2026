@extends('airlineticketingnew::layouts.app')
@section('atn-title','Production Monitoring')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-kpi-grid">
@foreach($current as $key=>$value)
<div class="atn-kpi"><div class="atn-kpi-label">{{ str_replace('_',' ',$key) }}</div><div class="atn-kpi-value">{{ $value }}</div></div>
@endforeach
</div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Metric</th><th>Value</th><th>Recorded</th></tr></thead>
<tbody>@foreach($history as $row)<tr><td>{{ $row->metric_code }}</td><td>{{ $row->metric_value }}</td><td>{{ $row->recorded_at }}</td></tr>@endforeach</tbody>
</table></div></div>
@endsection
