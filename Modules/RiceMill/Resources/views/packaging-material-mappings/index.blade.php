@extends('RiceMill::layout')
@section('rcm-title','Packaging Material Usage Mapping')
@section('rcm-subtitle','Define the bags, thread and other materials automatically consumed for each Rice Product + Bag Size')
@section('rcm-actions')
<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-materials.index') }}"><i class="fa fa-cubes"></i> Packaging Materials</a>
<a class="rcm-btn secondary" href="{{ route('rice-mill.packing.index') }}"><i class="fa fa-archive"></i> Packing</a>
@endsection
@section('rcm-content')
<div class="rcm-card">
    <div class="rcm-panel-head"><h3>Add / Update Usage Mapping</h3><span class="rcm-panel-hint">Example: 50 kg Rice Bag + Empty Bag = 1 pcs per bag; Thread = 0.005 kg per bag.</span></div>
    <form class="rcm-form-grid" method="post" action="{{ route('rice-mill.packaging-material-mappings.store') }}">@csrf
        <div class="rcm-field"><label>Rice Product</label><select class="rcm-searchable" name="product_id" required><option value="">Select Rice Product</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
        <div class="rcm-field"><label>Bag Size (kg)</label><input type="number" name="bag_size_kg" required min="{{ $rcmQuantityStep }}" step="{{ $rcmQuantityStep }}" value="{{ old('bag_size_kg') }}"></div>
        <div class="rcm-field"><label>Packaging Material</label><select class="rcm-searchable" name="material_id" required><option value="">Select Mapped Packaging Material</option>@foreach($materials as $m)<option value="{{ $m->id }}">{{ $m->name }} — {{ number_format($m->current_qty,$rcmQuantityPrecision) }} {{ $m->unit }}</option>@endforeach</select><small>Only Products mapped in Settings → Product Category Mapping → Packaging Material are available.</small></div>
        <div class="rcm-field"><label>Usage per Bag</label><input type="number" name="usage_per_bag" required min="0.0001" step="0.0001" inputmode="decimal" value="{{ old('usage_per_bag') }}"></div>
        <div><button class="rcm-btn">Save Mapping</button></div>
    </form>
</div>

<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-pack-material-map-table','exportName'=>'rice-mill-packaging-material-mapping','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'mappings','dateEnabled'=>false])
<div class="rcm-table-wrap">
<table id="rcm-pack-material-map-table" class="rcm-table rcm-managed-table">
<thead><tr><th>Rice Product</th><th class="rcm-num">Bag Size (kg)</th><th>Material</th><th class="rcm-num">Usage / Bag</th><th>Unit</th><th>Status</th><th data-rcm-no-export>Action</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr>
    <td>{{ $r->product?->name ?: '-' }}</td>
    <td class="rcm-num">{{ number_format($r->bag_size_kg,$rcmQuantityPrecision) }}</td>
    <td>{{ $r->material?->name ?: '-' }}</td>
    <td class="rcm-num">{{ number_format($r->usage_per_bag,$rcmQuantityPrecision) }}</td>
    <td>{{ $r->material?->unit ?: '-' }}</td>
    <td>{{ $r->active ? 'Active' : 'Inactive' }}</td>
    <td><form method="post" action="{{ route('rice-mill.packaging-material-mappings.destroy',$r->id) }}" onsubmit="return confirm('Remove this material usage mapping?')">@csrf @method('DELETE')<button class="rcm-btn danger" type="submit">Delete</button></form></td>
</tr>
@empty
<tr data-rcm-empty-row><td colspan="7" class="rcm-muted">No packaging material mappings configured yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $rows->links() }}
</div>
@endsection
