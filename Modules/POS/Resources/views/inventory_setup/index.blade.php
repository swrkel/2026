@extends('pos::layouts.app')
@section('pos_content')
<div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-cogs text-primary"></i> Inventory Setup</h3><div class="ch-card-subtitle">POS-owned categories, brands, and units. These do not depend on the main product module.</div></div><a class="btn btn-primary" href="{{ route('pos.barcodes.index') }}"><i class="fa fa-barcode"></i> Barcode Labels</a></div>
<div class="ch-card-body"><div class="row">
@foreach(['categories' => $categories, 'brands' => $brands, 'units' => $units] as $type => $rows)
<div class="col-md-4"><div class="box box-solid"><div class="box-header"><h3 class="box-title">{{ ucfirst($type) }}</h3></div><div class="box-body"><form method="post" action="{{ route('pos.inventory_setup.store', $type) }}" class="form-inline" style="margin-bottom:14px;display:flex;gap:8px;">@csrf<input class="form-control" name="name" placeholder="Name" required style="flex:1;">@if($type==='units')<input class="form-control" name="short_name" placeholder="Short" style="width:90px;">@endif<button class="btn btn-success">Add</button></form><table class="table pos-standard-table"><tbody>@forelse($rows as $row)<tr><td><strong>{{ $row->name }}</strong>@if(isset($row->short_name))<br><small>{{ $row->short_name }}</small>@endif</td><td class="text-right"><form method="post" action="{{ route('pos.inventory_setup.destroy', [$type, $row->id]) }}">@csrf @method('delete')<button class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')">Delete</button></form></td></tr>@empty<tr><td class="text-muted">No {{ $type }} yet.</td></tr>@endforelse</tbody></table></div></div></div>
@endforeach
</div></div></div>
@endsection
