@extends('airlineticketingnew::layouts.app')
@section('atn-title','B2B Agents')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Agent Code</th><th>Name</th><th>Email</th><th>Phone</th><th>Credit Limit</th><th>Available Credit</th><th>Status</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->agent_code }}</td><td>{{ $record->name }}</td><td>{{ $record->email }}</td><td>{{ $record->phone }}</td><td>{{ $record->credit_limit }}</td><td>{{ $record->available_credit }}</td><td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
