@extends('RiceMill::layout')
@section('rcm-title','Packaging Materials Stock')
@section('rcm-subtitle','Products mapped in Settings → Product Category Mapping → Packaging Material')
@section('rcm-actions')
<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-material-mappings.index') }}"><i class="fa fa-random"></i> Material Usage Mapping</a>
<a class="rcm-btn secondary" href="{{ route('rice-mill.packing.index') }}"><i class="fa fa-cubes"></i> Packing</a>
@endsection
@section('rcm-content')
<div class="rcm-card">
    <div class="rcm-panel-head"><h3>Add Packaging Material</h3><span class="rcm-panel-hint">Examples: Empty Bags, Thread, Labels, Twine, Tape.</span></div>
    <form class="rcm-form-grid" method="post" action="{{ route('rice-mill.packaging-materials.store') }}">@csrf
        <div class="rcm-field"><label>Code</label><input name="code" value="{{ old('code') }}" placeholder="Optional"></div>
        <div class="rcm-field"><label>Material Name</label><input name="name" required value="{{ old('name') }}"></div>
        <div class="rcm-field"><label>Unit</label><input name="unit" required value="{{ old('unit','pcs') }}" placeholder="pcs / kg / roll / cone / m"></div>
        <div class="rcm-field"><label>Opening Qty</label><input type="number" step="{{ $rcmQuantityStep }}" min="0" name="opening_qty" value="{{ old('opening_qty',0) }}"></div>
        <div class="rcm-field" style="grid-column:1/-1"><label>Note</label><input name="note" value="{{ old('note') }}"></div>
        <div><button class="rcm-btn">Add Material</button></div>
    </form>
</div>

<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-pack-material-table','exportName'=>'rice-mill-packaging-material-stock','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'materials','dateEnabled'=>false])
<div class="rcm-table-wrap">
<table id="rcm-pack-material-table" class="rcm-table rcm-managed-table">
<thead><tr><th>Code</th><th>Material</th><th>Unit</th><th class="rcm-num">Current Stock</th><th>Status</th><th data-rcm-no-export>Action</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr>
    <td>{{ $r->code ?: '-' }}</td>
    <td><strong>{{ $r->name }}</strong></td>
    <td>{{ $r->unit }}</td>
    <td class="rcm-num">{{ number_format((float)($r->products_new_current_qty ?? $r->current_qty),$rcmQuantityPrecision) }}</td>
    <td>{{ $r->active ? 'Active' : 'Inactive' }}</td>
    <td>
        <a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-materials.ledger',$r->id) }}"><i class="fa fa-book"></i> Ledger</a>
        <form method="post" action="{{ route('rice-mill.packaging-materials.toggle',$r->id) }}" style="display:inline">@csrf<button class="rcm-btn secondary" type="submit">{{ $r->active ? 'Deactivate' : 'Activate' }}</button></form>
    </td>
</tr>
@empty
<tr data-rcm-empty-row><td colspan="6" class="rcm-muted">No Packaging Material Products are mapped yet. Map Products in Settings → Product Category Mapping → Packaging Material.</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $rows->links() }}
</div>
@endsection
