@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Batch / Lot Report')
@section('productsnew_page_subtitle', 'Batch balances, expiry dates and location-wise traceability.')

@section('productsnew_content')
<div class="productsnew-page">
    <div class="productsnew-header">
        <div>
            <h2>Batch / Lot Report</h2>
            <p>Review batch quantity, expiry, location and valuation details.</p>
        </div>
    </div>

    <div class="productsnew-table-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped productsnew-table">
                <thead>
                    <tr>
                        <th>Batch No</th>
                        <th>Lot No</th>
                        <th>Product ID</th>
                        <th>Location ID</th>
                        <th class="text-right">Current Qty</th>
                        <th class="text-right">Available Qty</th>
                        <th>Expiry</th>
                        <th class="text-right">Cost</th>
                        <th class="text-right">Selling</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->batch_no ?? '-' }}</td>
                            <td>{{ $row->lot_no ?? '-' }}</td>
                            <td>{{ $row->product_id ?? '-' }}</td>
                            <td>{{ $row->location_id ?? '-' }}</td>
                            <td class="text-right">{{ number_format((float) ($row->current_qty ?? 0), 4) }}</td>
                            <td class="text-right">{{ number_format((float) ($row->available_qty ?? 0), 4) }}</td>
                            <td>
                                @php
                                    $expiry = $row->expiry_at ?? null;
                                @endphp
                                {{ $expiry instanceof \Carbon\CarbonInterface ? $expiry->format('Y-m-d') : ($expiry ?: '-') }}
                            </td>
                            <td class="text-right">{{ number_format((float) ($row->cost_price ?? 0), 4) }}</td>
                            <td class="text-right">{{ number_format((float) ($row->selling_price ?? 0), 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No batch or lot records found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><th colspan="9">Record Count: {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }}</th></tr>
                </tfoot>
            </table>
        </div>
        @if(method_exists($rows, 'links'))
            {{ $rows->links() }}
        @endif
    </div>
</div>
@endsection
