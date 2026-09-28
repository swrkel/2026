@extends('distributionnew::layouts.app')
@section('title','Vehicle + Store Stock')
@section('subtitle','Track stock by business location, store, vehicle and product.')
@section('module_content')
<div class="disnew-card">
    <div class="disnew-toolbar"><input class="form-control" placeholder="Search vehicle/store stock"></div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover disnew-table">
            <thead><tr><th>Location</th><th>Store</th><th>Vehicle</th><th>Product</th><th class="text-right">Qty</th><th>Updated</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr><td>{{ $row->location_id }}</td><td>{{ $row->store_id }}</td><td>{{ $row->vehicle_id }}</td><td>{{ $row->product_id }}</td><td class="text-right">{{ number_format($row->qty,4) }}</td><td>{{ $row->updated_at }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No stock records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ method_exists($rows,'links') ? $rows->links() : '' }}
</div>
@endsection
