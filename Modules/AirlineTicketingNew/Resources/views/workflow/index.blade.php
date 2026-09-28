@extends('airlineticketingnew::layouts.app')
@section('atn-title','Workflow Queue')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="atn-panel-header"><h4>Workflow Queue</h4></div><div class="atn-panel-body">
@isset($records)
<div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>ID</th><th>Status</th><th>Created</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->status ?? ($record->is_enabled ? 'Enabled' : 'Disabled') }}</td><td>{{ $record->created_at }}</td></tr>@empty<tr><td colspan="3" class="text-center">No records</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}
@endisset
</div></div>
@endsection
