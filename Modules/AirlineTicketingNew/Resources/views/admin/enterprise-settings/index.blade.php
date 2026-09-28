@extends('airlineticketingnew::layouts.app')
@section('atn-title','Enterprise Settings')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<x-airlineticketingnew::page-header title="Enterprise Settings" subtitle="Business-scoped module configuration" />
<div class="atn-panel">
<div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Group</th><th>Key</th><th>Value</th><th>Locked</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->setting_group }}</td><td>{{ $record->setting_key }}</td><td><pre>{{ json_encode($record->setting_value_json,JSON_PRETTY_PRINT) }}</pre></td><td>{{ $record->is_locked ? 'Yes' : 'No' }}</td></tr>
@empty
<tr><td colspan="4" class="text-center">No records</td></tr>
@endforelse
</tbody></table></div>
{{ $records->links() }}
</div>
@endsection
