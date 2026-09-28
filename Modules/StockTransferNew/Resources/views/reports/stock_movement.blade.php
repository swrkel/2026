@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Stock Movement Report','subtitle'=>'Standalone movement ledger for Stock Transfer-New'])
@include('stocktransfernew::partials.list_toolbar')
<div class="stn-card">
    <div class="table-responsive">
        <table class="table table-bordered table-striped stn-table">
            <thead>
                <tr>
                    <th>Date</th><th>Reference</th><th>Type</th><th>Product</th><th>From Location</th><th>To Location</th><th>From Store</th><th>To Store</th><th class="text-right">Qty</th><th class="text-right">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->movement_date }}</td>
                        <td>{{ $movement->reference_no }}</td>
                        <td><span class="stn-status stn-status-{{ $movement->movement_type }}">{{ strtoupper($movement->movement_type) }}</span></td>
                        <td>{{ $movement->product_id }}</td>
                        <td>{{ $movement->from_location_id }}</td>
                        <td>{{ $movement->to_location_id }}</td>
                        <td>{{ $movement->from_store_id }}</td>
                        <td>{{ $movement->to_store_id }}</td>
                        <td class="text-right">{{ number_format((float)$movement->quantity, 4) }}</td>
                        <td class="text-right">{{ number_format((float)$movement->total_cost, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No stock movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $movements->links() }}
</div>
@endsection
