@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-page disnew-page">
    <div class="pos-card disnew-card">
        <div class="pos-card-header d-flex justify-content-between align-items-center">
            <h4>Warehouses</h4>
            <div class="pos-toolbar">Search | Date Range | CSV | Excel | PDF | Print | Column Visibility</div>
        </div>
        <div class="pos-card-body">
            <table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Manager</th><th>Status</th></tr></thead><tbody>@foreach($warehouses as $warehouse)<tr><td>{{ $warehouse->code }}</td><td>{{ $warehouse->name }}</td><td>{{ $warehouse->location_id }}</td><td>{{ $warehouse->manager_id }}</td><td>{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</tbody></table>{{ $warehouses->links() }}
        </div>
    </div>
</div>
@endsection
