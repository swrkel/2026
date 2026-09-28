@extends('layouts.app')
@section('title', 'Purchase Return Details')

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>
<section class="content purchase-workspace">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-undo"></i> Purchase Return Details</h1>
            <div class="purchase-breadcrumb">Purchase (New) / Purchase Returns / {{ $return->ref_no ?: ('PR-'.$return->id) }}</div>
        </div>
        <div class="purchase-workspace-actions">
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-default"><i class="fa fa-list"></i> List Returns</a>
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>

    <div class="purchase-panel">
        <div class="purchase-panel-heading"><span><i class="fa fa-info-circle"></i> Return Information</span></div>
        <div class="purchase-panel-body">
            <div class="purchase-form-grid">
                <div><label>Return No.</label><div>{{ $return->ref_no ?: $return->invoice_no ?: ('PR-'.$return->id) }}</div></div>
                <div><label>Return Date</label><div>{{ $return->transaction_date ? \Carbon\Carbon::parse($return->transaction_date)->format('d/m/Y H:i') : '' }}</div></div>
                <div><label>Original Purchase</label><div>{{ $return->parent_purchase_no ?: '-' }}</div></div>
                <div><label>Supplier</label><div>{{ $return->supplier_name ?: '-' }}</div></div>
                <div><label>Location</label><div>{{ $return->location_name ?: '-' }}</div></div>
                <div><label>Store</label><div>{{ $return->store_name ?: '-' }}</div></div>
                <div><label>Status</label><div>{{ ucfirst($return->status ?: 'final') }}</div></div>
                <div><label>Return Total</label><div><strong>{{ number_format((float)$return->final_total, 2) }}</strong></div></div>
                @if(!empty($return->additional_notes))<div class="full-width"><label>Notes</label><div>{{ $return->additional_notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="purchase-panel">
        <div class="purchase-panel-heading"><span><i class="fa fa-cubes"></i> Returned Products</span></div>
        <div class="purchase-panel-body">
            <div class="purchase-table-wrap">
                <table class="table table-bordered purchase-data-table">
                    <thead><tr><th>#</th><th>Product</th><th>SKU</th><th>Unit</th><th>Return Qty</th><th>Unit Cost</th><th>Line Total</th></tr></thead>
                    <tbody>
                        @forelse($lines as $index => $line)
                            @php($qty = (float)$line->return_quantity)
                            @php($price = (float)$line->purchase_price_inc_tax)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $line->product_name ?: 'Product' }}{{ $line->variation_group || $line->variation_name ? ' - '.trim(($line->variation_group ?? '').' '.($line->variation_name ?? '')) : '' }}</td>
                                <td>{{ $line->sub_sku ?: '-' }}</td>
                                <td class="text-center">{{ $line->unit_name ?: '-' }}</td>
                                <td class="amount">{{ number_format($qty, 3) }}</td>
                                <td class="amount">{{ number_format($price, 2) }}</td>
                                <td class="amount">{{ number_format($qty * $price, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="purchase-empty-state">No returned product lines were found.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th colspan="6" class="text-right">Return Total</th><th class="amount">{{ number_format((float)$return->final_total, 2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
