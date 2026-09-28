@extends('layouts.app')
@section('title', 'Vehicle Maintenance')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Vehicle Maintenance'])
<section class="content distributionnew-page">
    <div class="disnew-pos-card">
        <div class="disnew-toolbar">
            <div><h4>Vehicle Maintenance</h4><small>Distribution New smart logistics</small></div>
            <a href="{{ route('distributionnew.maintenance.create') }}" class="btn btn-primary btn-sm">Add New</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped disnew-table">
                <thead><tr><th>ID</th><th>Reference</th><th>Status/Date</th><th>Amount/Value</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ $record->id }}</td>
                        <td>{{ $record->name ?? $record->vehicle_id ?? $record->document_type ?? $record->expense_category ?? $record->maintenance_type ?? $record->receipt_no ?? '-' }}</td>
                        <td>{{ $record->status ?? $record->fuel_date ?? $record->reading_date ?? $record->maintenance_date ?? $record->expense_date ?? '-' }}</td>
                        <td>{{ $record->total_amount ?? $record->amount ?? $record->cost_amount ?? $record->commission_amount ?? $record->litres ?? '-' }}</td>
                        <td><a href="{{ route('distributionnew.maintenance.edit', $record->id) }}" class="btn btn-xs btn-info">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No records found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</section>
@endsection
