@extends('airlineticketingnew::layouts.app')
@section('atn-title','Module Diagnostics')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel">
<h4>Environment</h4>
<p><strong>Database:</strong> {{ $diagnostics['database'] }}</p>
<p><strong>PHP:</strong> {{ $diagnostics['php_version'] }}</p>
<p><strong>Laravel:</strong> {{ $diagnostics['laravel_version'] }}</p>
</div>
<div class="atn-panel">
<h4>Tables</h4>
<div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Table</th><th>Status</th></tr></thead>
<tbody>@foreach($diagnostics['tables'] as $table=>$ok)<tr><td>{{ $table }}</td><td>{{ $ok ? 'Available' : 'Missing' }}</td></tr>@endforeach</tbody>
</table></div>
</div>
<div class="atn-panel">
<h4>Business Record Counts</h4>
<div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Area</th><th>Count</th></tr></thead>
<tbody>@foreach($diagnostics['business_record_counts'] as $area=>$count)<tr><td>{{ ucfirst($area) }}</td><td>{{ $count }}</td></tr>@endforeach</tbody>
</table></div>
</div>
@endsection
