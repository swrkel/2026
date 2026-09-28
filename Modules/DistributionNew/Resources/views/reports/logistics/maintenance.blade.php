@extends('layouts.app')
@section('title', 'Maintenance Report')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Maintenance Report'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
@include('distributionnew::partials.report_toolbar')
<div class="table-responsive"><table class="table table-bordered table-striped disnew-table"><thead><tr><th>ID</th><th>Vehicle/Reference</th><th>Date</th><th>Status</th><th>Value</th></tr></thead><tbody>
@foreach(($records ?? $documents ?? collect()) as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->vehicle_id ?? $record->document_type ?? '-' }}</td><td>{{ $record->fuel_date ?? $record->maintenance_date ?? $record->expiry_date ?? '-' }}</td><td>{{ $record->status ?? '-' }}</td><td>{{ $record->total_amount ?? $record->cost_amount ?? '-' }}</td></tr>@endforeach
@if(isset($drivers)) @foreach($drivers as $driver)<tr><td>{{ $driver->id }}</td><td>{{ $driver->name }} - License</td><td>{{ $driver->license_expiry_date }}</td><td>{{ $driver->status }}</td><td>-</td></tr>@endforeach @endif
</tbody></table></div>
@if(isset($records)) {{ $records->links() }} @endif
</div></section>
@endsection
