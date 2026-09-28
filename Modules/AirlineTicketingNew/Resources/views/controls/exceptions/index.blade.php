@extends('airlineticketingnew::layouts.app')
@section('atn-title','Operational Exceptions')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Type</th><th>Severity</th><th>Reference</th><th>Description</th><th>Status</th><th>Detected</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->exception_type }}</td><td>{{ $record->severity }}</td><td>{{ $record->reference_no }}</td><td>{{ $record->description }}</td><td>{{ $record->status }}</td><td>{{ $record->detected_at }}</td></tr>
@empty
<tr><td colspan="6" class="text-center">No records</td></tr>
@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
