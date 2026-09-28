@extends('RiceMill::layout')
@section('rcm-title','Packaging Material Ledger - '.$material->name)
@section('rcm-subtitle','Every opening, adjustment and automatic packing consumption movement')
@section('rcm-actions')<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-materials.index') }}">Packaging Materials</a>@endsection
@section('rcm-content')
<div class="rcm-card">
    <div class="rcm-kpi-grid">
        <div class="rcm-kpi"><span>Material</span><strong>{{ $material->name }}</strong></div>
        <div class="rcm-kpi"><span>Unit</span><strong>{{ $material->unit }}</strong></div>
        <div class="rcm-kpi"><span>Current Stock</span><strong>{{ number_format($material->current_qty,$rcmQuantityPrecision) }}</strong></div>
    </div>
</div>
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-pack-material-ledger-table','exportName'=>'rice-mill-packaging-material-'.$material->id,'serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'movements'])
<div class="rcm-table-wrap">
<table id="rcm-pack-material-ledger-table" class="rcm-table rcm-managed-table">
<thead><tr><th>Date</th><th>Movement</th><th>Packing Operation No.</th><th class="rcm-num">In</th><th class="rcm-num">Out</th><th class="rcm-num">Signed Qty</th><th>Reference</th><th>Note</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr>
    <td>{{ optional($r->movement_date)->format('Y-m-d') }}</td>
    <td>{{ ucwords(str_replace('_',' ',$r->movement_type)) }}</td>
    <td>{{ $r->packingBatch?->packing_no ?: '-' }}</td>
    <td class="rcm-num">{{ $r->signed_quantity > 0 ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
    <td class="rcm-num">{{ $r->signed_quantity < 0 ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
    <td class="rcm-num">{{ number_format($r->signed_quantity,$rcmQuantityPrecision) }}</td>
    <td>{{ $r->reference_type ? $r->reference_type.' #'.$r->reference_id : '-' }}</td>
    <td>{{ $r->note }}</td>
</tr>
@empty
<tr data-rcm-empty-row><td colspan="8" class="rcm-muted">No material movements found.</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $rows->links() }}
</div>

<div class="rcm-card">
    <div class="rcm-panel-head"><h3>Stock Adjustment</h3><span class="rcm-panel-hint">Adjustment Out cannot exceed the current available quantity.</span></div>
    <form class="rcm-form-grid rcm-location-store-group" method="post" action="{{ route('rice-mill.packaging-materials.adjust',$material->id) }}">@csrf
        <div class="rcm-field"><label>Movement</label><select name="movement_type" required><option value="adjustment_in">Adjustment In</option><option value="adjustment_out">Adjustment Out</option></select></div>
        <div class="rcm-field"><label>Date</label><input type="date" name="movement_date" value="{{ old('movement_date',now()->toDateString()) }}"></div>
        <div class="rcm-field"><label>Quantity ({{ $material->unit }})</label><input type="number" step="{{ $rcmQuantityStep }}" min="{{ $rcmQuantityStep }}" name="quantity" required value="{{ old('quantity') }}"></div>
        <div class="rcm-field"><label>Location</label><select class="rcm-searchable rcm-location-select" name="location_id"><option value="">Select location</option>@foreach($locations as $x)<option value="{{ $x['id'] }}">{{ $x['name'] }}</option>@endforeach</select></div>
        <div class="rcm-field"><label>Store</label><select class="rcm-searchable rcm-store-select" name="store_id"><option value="">Select store</option>@foreach($stores as $x)<option value="{{ $x['id'] }}" data-location-id="{{ $x['location_id'] ?? '' }}">{{ $x['name'] }}</option>@endforeach</select></div>
        <div class="rcm-field"><label>Reason / Note</label><input name="note" required value="{{ old('note') }}"></div>
        <div><button class="rcm-btn">Post Adjustment</button></div>
    </form>
    @error('quantity')<div class="rcm-alert rcm-alert-danger" style="margin-top:10px">{{ $message }}</div>@enderror
</div>
@endsection
