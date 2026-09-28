<div class="box"><div class="box-body">
<div class="row">
 <div class="col-md-3"><label>Service Code</label><input name="service_code" class="form-control" value="{{ old('service_code', $service->service_code ?? '') }}"></div>
 <div class="col-md-3"><label>Service Name</label><input name="name" class="form-control" required value="{{ old('name', $service->name ?? '') }}"></div>
 <div class="col-md-3"><label>Category</label><select name="category_id" class="form-control"><option value="">Select</option>@foreach($categories as $id=>$name)<option value="{{ $id }}" @selected(old('category_id', $service->category_id ?? '')==$id)>{{ $name }}</option>@endforeach</select></div>
 <div class="col-md-3"><label>Price</label><input name="price" type="number" step="0.01" class="form-control" value="{{ old('price', $service->price->price ?? '') }}"></div>
</div>
<div class="row" style="margin-top:15px;">
 <div class="col-md-3"><label>Duration Minutes</label><input name="duration_minutes" type="number" class="form-control" value="{{ old('duration_minutes', $service->duration_minutes ?? 0) }}"></div>
 <div class="col-md-3"><label>Buffer Minutes</label><input name="buffer_minutes" type="number" class="form-control" value="{{ old('buffer_minutes', $service->buffer_minutes ?? 0) }}"></div>
 <div class="col-md-3"><label><input name="commission_applicable" type="checkbox" value="1" checked> Commission Applicable</label></div>
 <div class="col-md-3"><label><input name="is_active" type="checkbox" value="1" checked> Active</label></div>
</div>
<div class="row" style="margin-top:15px;"><div class="col-md-12"><label>Description</label><textarea name="description" class="form-control">{{ old('description', $service->description ?? '') }}</textarea></div></div>
</div><div class="box-footer text-right"><button class="btn btn-success btn-lg">Save</button></div></div>
