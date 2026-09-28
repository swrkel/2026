@extends('airlineticketingnew::layouts.app')
@section('atn-title','Enterprise Report Centre')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-report-grid">
@foreach($reports as $code=>$label)
<div class="atn-report-card"><strong>{{ $label }}</strong><div class="text-muted">{{ $code }}</div></div>
@endforeach
</div>
<div class="atn-panel"><div class="atn-panel-header"><h4>Saved Reports</h4></div>
<div class="atn-panel-body">
@forelse($savedReports as $report)<div>{{ $report->name }}</div>@empty<p>No saved reports.</p>@endforelse
</div></div>
@endsection
