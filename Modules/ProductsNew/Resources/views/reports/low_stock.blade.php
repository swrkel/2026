@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Low Stock Report')
@section('productsnew_page_subtitle', 'Products at or below the configured stock alert quantity.')

@section('productsnew_content')
<div class="productsnew-page">
    <div class="productsnew-header">
        <div>
            <h2>Low Stock Report</h2>
            <p>Location-wise stock that requires replenishment attention.</p>
        </div>
    </div>

    <div class="productsnew-table-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped productsnew-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Variation</th>
                        <th>Location</th>
                        <th class="text-right">Available Qty</th>
                        <th class="text-right">Alert Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->sku ?: '-' }}</td>
                            <td>{{ $row->name ?: '-' }}</td>
                            <td>{{ $row->variation_name ?: '-' }}</td>
                            <td>{{ $row->location_name ?: '-' }}</td>
                            <td class="text-right">{{ number_format((float) $row->qty_available, 3) }}</td>
                            <td class="text-right">{{ number_format((float) $row->alert_quantity, 3) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No low-stock records found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><th colspan="6">Record Count: {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }}</th></tr>
                </tfoot>
            </table>
        </div>
        @if(method_exists($rows, 'links'))
            {{ $rows->links() }}
        @endif
    </div>
</div>
@endsection
