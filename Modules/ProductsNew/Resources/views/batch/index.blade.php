@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-card">
    <div class="productsnew-card-header"><div><h3>Batch / Lot Centre</h3><p>Track batch no, lot no, expiry and FEFO stock allocation.</p></div></div>
    <form method="GET" class="productsnew-filter-row">
        <input type="text" name="batch_no" value="{{ request('batch_no') }}" placeholder="Batch / Lot No" class="form-control">
        <select name="expiry_status" class="form-control"><option value="">All Expiry</option><option value="expired">Expired</option><option value="expiring_soon">Expiring Soon</option><option value="valid">Valid</option></select>
        <button class="btn btn-primary">Search</button>
    </form>
</div>
<div class="productsnew-grid productsnew-grid-2">
    <div class="productsnew-card">
        <h4>Add Batch / Lot</h4>
        <form method="POST" action="{{ route('products-new.batch.store') }}">@csrf
            <div class="row"><div class="col-md-6"><label>Product</label><select name="product_id" class="form-control pn-searchable-select" required>
                    <option value="">Select Active Product</option>
                    @foreach($lookups['productsNew'] ?? [] as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                            {{ $product->name }}{{ !empty($product->sku) ? ' — ' . $product->sku : '' }}
                        </option>
                    @endforeach
                </select></div><div class="col-md-6"><label>Location ID</label><input name="location_id" class="form-control" required></div></div>
            <div class="row"><div class="col-md-6"><label>Batch No</label><input name="batch_no" class="form-control" required></div><div class="col-md-6"><label>Lot No</label><input name="lot_no" class="form-control"></div></div>
            <div class="row"><div class="col-md-6"><label>Manufactured Date</label><input type="date" name="manufactured_at" class="form-control"></div><div class="col-md-6"><label>Expiry Date</label><input type="date" name="expiry_at" class="form-control"></div></div>
            <div class="row"><div class="col-md-4"><label>Opening Qty</label><input name="opening_qty" class="form-control" value="0"></div><div class="col-md-4"><label>Cost Price</label><input name="cost_price" class="form-control"></div><div class="col-md-4"><label>Selling Price</label><input name="selling_price" class="form-control"></div></div>
            <label>Note</label><textarea name="note" class="form-control"></textarea>
            <button class="btn btn-success productsnew-mt">Save Batch</button>
        </form>
    </div>
    <div class="productsnew-card">
        <h4>FEFO / FIFO Rules</h4>
        <p class="text-muted">Default issue sequence is FEFO: earliest expiry first, then batch creation order. FIFO fallback is available when expiry date is empty.</p>
        <ul class="productsnew-checklist"><li>Expiry-aware stock issue</li><li>Batch balance after every movement</li><li>Recall-ready batch history</li><li>Multi-business and location isolated</li></ul>
    </div>
</div>
<div class="productsnew-card">
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Batch</th><th>Lot</th><th>Product</th><th>Location</th><th>Qty</th><th>Reserved</th><th>Available</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead><tbody>
    @forelse($batches as $row)<tr><td>{{ $row->batch_no }}</td><td>{{ $row->lot_no }}</td><td>{{ $row->product_id }}</td><td>{{ $row->location_id }}</td><td>{{ number_format($row->current_qty,4) }}</td><td>{{ number_format($row->reserved_qty,4) }}</td><td>{{ number_format($row->available_qty,4) }}</td><td>{{ optional($row->expiry_at)->format('Y-m-d') }}</td><td>{{ $row->current_qty <= 0 ? 'Empty' : ($row->expiry_at && $row->expiry_at->isPast() ? 'Expired' : 'Active') }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('products-new.batch.show',$row->id) }}">View</a></td></tr>@empty<tr><td colspan="10" class="text-center">No batches found.</td></tr>@endforelse
    </tbody></table></div>{{ $batches->links() }}
</div>

@endsection
