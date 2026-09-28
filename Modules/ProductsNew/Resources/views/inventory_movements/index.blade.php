@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-card">
  <div class="pn-card-header"><strong>Inventory Movements</strong><span class="pn-muted">Standalone movement register for Products New</span></div>
  <div class="pn-card-body">
    <form class="pn-toolbar" method="get">
      <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search product, SKU or reference">
      <select class="form-control" name="movement_type"><option value="">All Movement Types</option>@foreach(['opening_stock','stock_in','stock_out','adjustment_in','adjustment_out','return_in','return_out'] as $type)<option value="{{ $type }}" @selected(request('movement_type')===$type)>{{ ucwords(str_replace('_',' ',$type)) }}</option>@endforeach</select>
      <select class="form-control" name="location_id"><option value="">All Locations</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(request('location_id')==$location->id)>{{ $location->name }}</option>@endforeach</select>
      <input class="form-control" type="date" name="from_date" value="{{ request('from_date') }}"><input class="form-control" type="date" name="to_date" value="{{ request('to_date') }}">
      <button class="pn-btn pn-btn-primary">Search</button>
    </form>
    <div class="pn-grid-2">
      <div>
        <table class="table pn-table"><thead><tr><th>Date</th><th>Product</th><th>SKU</th><th>Location</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Unit Cost</th><th>Reference</th></tr></thead><tbody>
        @foreach($rows as $row)<tr><td>{{ optional($row->movement_date ? \Carbon\Carbon::parse($row->movement_date) : null)->format('d M Y') }}</td><td>{{ $row->product_name }}</td><td>{{ $row->sku }}</td><td>{{ $row->location_name }}</td><td><span class="pn-badge">{{ ucwords(str_replace('_',' ',$row->movement_type)) }}</span></td><td class="text-right">{{ number_format((float)$row->qty,3) }}</td><td class="text-right">{{ number_format((float)$row->unit_cost,4) }}</td><td>{{ $row->reference_no }}</td></tr>@endforeach
        </tbody></table>{{ $rows->links() }}
      </div>
      <div class="pn-side-panel"><h4>Add Movement</h4><form method="post" action="{{ route('products-new.inventory-movements.store') }}">@csrf
        <label>Product</label><select name="product_id" class="form-control" required>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} - {{ $product->sku }}</option>@endforeach</select>
        <label>Variation ID</label><input name="variation_id" class="form-control" placeholder="Optional; use Product 360 lookup if required">
        <label>Location</label><select name="location_id" class="form-control"><option value="">None</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select>
        <label>Movement Type</label><select name="movement_type" class="form-control" required>@foreach(['stock_in','stock_out','adjustment_in','adjustment_out','return_in','return_out'] as $type)<option value="{{ $type }}">{{ ucwords(str_replace('_',' ',$type)) }}</option>@endforeach</select>
        <label>Qty</label><input type="number" step="0.001" min="0.001" name="qty" class="form-control" required>
        <label>Unit Cost</label><input type="number" step="0.0001" min="0" name="unit_cost" class="form-control">
        <label>Reference No</label><input name="reference_no" class="form-control"><label>Notes</label><textarea name="notes" class="form-control"></textarea>
        <button class="pn-btn pn-btn-success pn-mt">Save Movement</button></form></div>
    </div>
  </div>
</div>
@endsection
