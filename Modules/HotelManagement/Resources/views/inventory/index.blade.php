@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header">
    <h1>Hotel Inventory & Stores <small>Stock items, store movement and reorder monitoring</small></h1>
</section>
<section class="content">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger hm-alert"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-md-6">
        <div class="box hm-pos-box">
            <div class="box-header with-border"><h3 class="box-title">Add Store Item</h3></div>
            <div class="box-body">
                <form method="POST" action="{{ route('hotel-management.inventory.store') }}">@csrf
                    <div class="hm-form-grid">
                        <div class="form-group"><label>Item Code</label><input name="item_code" type="text" class="form-control" placeholder="Auto if blank"></div>
                        <div class="form-group"><label>Item Name</label><input name="name" type="text" class="form-control" required></div>
                        <div class="form-group"><label>Category</label><input name="category" type="text" class="form-control" placeholder="Linen / Minibar / Cleaning"></div>
                        <div class="form-group"><label>Unit</label><input name="unit" type="text" class="form-control" placeholder="pcs / bottle / kg"></div>
                        <div class="form-group"><label>Purchase Price</label><input name="purchase_price" type="number" step="0.0001" class="form-control" value="0"></div>
                        <div class="form-group"><label>Selling Price</label><input name="selling_price" type="number" step="0.0001" class="form-control" value="0"></div>
                        <div class="form-group"><label>Reorder Level</label><input name="reorder_level" type="number" step="0.0001" class="form-control" value="0"></div>
                        <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                    <br><button class="btn hm-btn-add">Save Item</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box hm-pos-box">
            <div class="box-header with-border"><h3 class="box-title">Post Stock Movement</h3></div>
            <div class="box-body">
                <form method="POST" action="{{ route('hotel-management.inventory.movement') }}">@csrf
                    <div class="hm-form-grid">
                        <div class="form-group"><label>Store Item</label><select name="store_item_id" class="form-control" required><option value="">Please Select</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->item_code ?? 'ITEM' }} - {{ $item->name ?? '' }} (Stock: {{ number_format((float)($item->current_stock ?? 0), 4) }})</option>@endforeach</select></div>
                        <div class="form-group"><label>Movement Type</label><select name="movement_type" class="form-control" required><option value="purchase">Purchase / Stock In</option><option value="issue">Issue to Department</option><option value="return">Return In</option><option value="damage">Damage / Write-off</option><option value="adjustment">Adjustment</option></select></div>
                        <div class="form-group"><label>Adjustment Direction</label><select name="adjustment_direction" class="form-control"><option value="increase">Increase</option><option value="decrease">Decrease</option></select></div>
                        <div class="form-group"><label>Movement Date</label><input name="movement_date" type="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                        <div class="form-group"><label>Quantity</label><input name="quantity" type="number" step="0.0001" class="form-control" required></div>
                        <div class="form-group"><label>Unit Cost</label><input name="unit_cost" type="number" step="0.0001" class="form-control" value="0"></div>
                        <div class="form-group"><label>Reference No</label><input name="reference_no" type="text" class="form-control"></div>
                        <div class="form-group"><label>Note</label><input name="note" type="text" class="form-control"></div>
                    </div>
                    <br><button class="btn hm-btn-add">Post Movement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="box hm-pos-box">
    <div class="box-header with-border"><h3 class="box-title">Store Item List</h3></div>
    <div class="box-body">
        @include('hotelmanagement::partials.toolbar')
        <div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>Item Code</th><th>Name</th><th>Category</th><th>Unit</th><th class="text-right">Current Stock</th><th class="text-right">Reorder Level</th><th class="text-right">Purchase</th><th class="text-right">Selling</th><th>Status</th></tr></thead><tbody>
        @forelse($items as $row)
            @php $lowStock = (float)($row->current_stock ?? 0) <= (float)($row->reorder_level ?? 0); @endphp
            <tr><td>{{ $row->item_code ?? '' }}</td><td>{{ $row->name ?? '' }}</td><td>{{ $row->category ?? '' }}</td><td>{{ $row->unit ?? '' }}</td><td class="text-right"><span class="hm-badge {{ $lowStock ? 'pending' : 'active' }}">{{ number_format((float)($row->current_stock ?? 0), 4) }}</span></td><td class="text-right">{{ number_format((float)($row->reorder_level ?? 0), 4) }}</td><td class="text-right">{{ number_format((float)($row->purchase_price ?? 0), 4) }}</td><td class="text-right">{{ number_format((float)($row->selling_price ?? 0), 4) }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ ucfirst($row->status ?? '-') }}</span></td></tr>
        @empty<tr><td colspan="9"><div class="hm-empty">No records found.</div></td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>

<div class="box hm-pos-box">
    <div class="box-header with-border"><h3 class="box-title">Latest Stock Movements</h3></div>
    <div class="box-body">
        <div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>Movement No</th><th>Date</th><th>Item</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Before</th><th class="text-right">After</th><th class="text-right">Total Cost</th><th>Reference</th><th>Note</th></tr></thead><tbody>
        @forelse($movements as $row)
            <tr><td>{{ $row->movement_no ?? '' }}</td><td>{{ $row->movement_date ?? '' }}</td><td>{{ $row->item_code ?? '' }} - {{ $row->item_name ?? '' }}</td><td><span class="hm-badge {{ ($row->direction ?? 1) > 0 ? 'active' : 'cancelled' }}">{{ ucfirst($row->movement_type ?? '-') }}</span></td><td class="text-right">{{ number_format((float)($row->quantity ?? 0), 4) }}</td><td class="text-right">{{ number_format((float)($row->stock_before ?? 0), 4) }}</td><td class="text-right">{{ number_format((float)($row->stock_after ?? 0), 4) }}</td><td class="text-right">{{ number_format((float)($row->total_cost ?? 0), 4) }}</td><td>{{ $row->reference_no ?? '' }}</td><td>{{ $row->note ?? '' }}</td></tr>
        @empty<tr><td colspan="10"><div class="hm-empty">No stock movements found.</div></td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
</section>
@endsection
