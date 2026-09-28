@extends('autoservice::layouts.master')
@section('title',$package->id?'Edit Service Package':'Add Service Package')
@section('autoservice_content')
<form method="post" action="{{ $package->id ? route('autoservice.packages.update',$package->id) : route('autoservice.packages.store') }}">@csrf @if($package->id) @method('PUT') @endif
<div class="box"><div class="box-header"><h3 class="box-title">Package Details</h3></div><div class="box-body row">
<div class="form-group col-md-2"><label>Package Code</label><input class="form-control" name="package_code" value="{{ old('package_code',$package->package_code) }}"></div>
<div class="form-group col-md-4"><label>Package Name</label><input required class="form-control" name="name" value="{{ old('name',$package->name) }}"></div>
<div class="form-group col-md-3"><label>Category</label><select name="category_id" class="form-control"><option value="">Select</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id',$package->category_id)==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="form-group col-md-3"><label>Vehicle Type</label><input class="form-control" name="vehicle_type" value="{{ old('vehicle_type',$package->vehicle_type) }}"></div>
<div class="form-group col-md-3"><label>Vehicle Brand</label><input class="form-control" name="vehicle_brand" value="{{ old('vehicle_brand',$package->vehicle_brand) }}"></div>
<div class="form-group col-md-3"><label>Vehicle Model</label><input class="form-control" name="vehicle_model" value="{{ old('vehicle_model',$package->vehicle_model) }}"></div>
<div class="form-group col-md-2"><label>Estimated Minutes</label><input type="number" class="form-control" name="estimated_minutes" value="{{ old('estimated_minutes',$package->estimated_minutes) }}"></div>
<div class="form-group col-md-2"><label>Warranty Days</label><input type="number" class="form-control" name="warranty_days" value="{{ old('warranty_days',$package->warranty_days) }}"></div>
<div class="form-group col-md-2"><label>Selling Price</label><input type="number" step="0.0001" class="form-control" name="selling_price" value="{{ old('selling_price',$package->selling_price) }}"></div>
<div class="form-group col-md-12"><label>Description</label><textarea class="form-control" name="description">{{ old('description',$package->description) }}</textarea></div>
<div class="form-group col-md-3"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$package->is_active ?? 1))> Active</label></div>
</div></div>

<div class="box"><div class="box-header"><h3 class="box-title">Package Components</h3></div><div class="box-body table-responsive">
<table class="table table-bordered" id="package-lines"><thead><tr><th>Type</th><th>Stock Product/Variation</th><th>Description</th><th>Qty</th><th>Unit</th><th>Unit Price</th><th>Discount</th><th>Tax</th><th>Optional</th><th></th></tr></thead><tbody>
@php $lines=old('lines',$package->lines ? $package->lines->toArray() : []); @endphp
@foreach(array_pad($lines,max(1,8-count($lines)),[]) as $i=>$l)
<tr>
<td><select name="lines[{{ $i }}][component_type]" class="form-control component-type">@foreach(['stock_item'=>'Stock Item','non_stock_item'=>'Non-stock Item','labour'=>'Labour','external_service'=>'External Service','remark'=>'Remark'] as $k=>$v)<option value="{{ $k }}" @selected(($l['component_type']??'non_stock_item')==$k)>{{ $v }}</option>@endforeach</select></td>
<td><select name="lines[{{ $i }}][variation_id]" class="form-control stock-select"><option value="">Select</option>@foreach($stockItems as $s)<option value="{{ $s->variation_id }}" data-product="{{ $s->product_id }}" data-price="{{ $s->unit_price }}" @selected(($l['variation_id']??null)==$s->variation_id)>{{ $s->name }} {{ $s->sub_sku?'('.$s->sub_sku.')':'' }} | Stock {{ number_format($s->qty_available,3) }}</option>@endforeach</select><input type="hidden" class="product-id" name="lines[{{ $i }}][product_id]" value="{{ $l['product_id']??'' }}"></td>
<td><input name="lines[{{ $i }}][description]" class="form-control desc" value="{{ $l['description']??'' }}"></td>
<td><input name="lines[{{ $i }}][quantity]" type="number" step="0.0001" class="form-control" value="{{ $l['quantity']??1 }}"></td>
<td><input name="lines[{{ $i }}][unit_name]" class="form-control" value="{{ $l['unit_name']??'' }}"></td>
<td><input name="lines[{{ $i }}][unit_price]" type="number" step="0.0001" class="form-control price" value="{{ $l['unit_price']??0 }}"></td>
<td><input name="lines[{{ $i }}][discount_amount]" type="number" step="0.0001" class="form-control" value="{{ $l['discount_amount']??0 }}"></td>
<td><input name="lines[{{ $i }}][tax_amount]" type="number" step="0.0001" class="form-control" value="{{ $l['tax_amount']??0 }}"></td>
<td class="text-center"><input type="checkbox" name="lines[{{ $i }}][is_optional]" value="1" @checked(!empty($l['is_optional']))></td><td></td>
</tr>@endforeach
</tbody></table></div><div class="box-footer"><button class="btn btn-primary">Save Package</button></div></div>
</form>
<script>
document.addEventListener('change',function(e){
 if(!e.target.classList.contains('stock-select')) return;
 const row=e.target.closest('tr'), opt=e.target.options[e.target.selectedIndex];
 row.querySelector('.product-id').value=opt.dataset.product||'';
 if(!row.querySelector('.price').value || Number(row.querySelector('.price').value)===0) row.querySelector('.price').value=opt.dataset.price||0;
 if(!row.querySelector('.desc').value) row.querySelector('.desc').value=opt.text.split('|')[0].trim();
 row.querySelector('.component-type').value='stock_item';
});
</script>
@endsection
