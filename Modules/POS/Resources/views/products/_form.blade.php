<div class="row">
    <div class="col-md-6"><label>Product Name</label><input class="form-control" name="name" value="{{ old('name', $product->name ?? '') }}" required></div>
    <div class="col-md-3"><label>SKU</label><input class="form-control" name="sku" value="{{ old('sku', $product->sku ?? '') }}"></div>
    <div class="col-md-3"><label>Barcode</label><input class="form-control" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"></div>
    <div class="col-md-4"><label>Category</label><select class="form-control" name="category_id"><option value="">None</option>@foreach($categories as $row)<option value="{{ $row->id }}" @selected(old('category_id', $product->category_id ?? '') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label>Brand</label><select class="form-control" name="brand_id"><option value="">None</option>@foreach($brands as $row)<option value="{{ $row->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label>Unit</label><select class="form-control" name="unit_id"><option value="">None</option>@foreach($units as $row)<option value="{{ $row->id }}" @selected(old('unit_id', $product->unit_id ?? '') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label>Cost Price</label><input class="form-control" type="number" step="0.0001" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? 0) }}"></div>
    <div class="col-md-3"><label>Selling Price</label><input class="form-control" type="number" step="0.0001" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? 0) }}" required></div>
    <div class="col-md-3"><label>Tax %</label><input class="form-control" type="number" step="0.0001" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate ?? 0) }}"></div>
    <div class="col-md-3"><label>Alert Quantity</label><input class="form-control" type="number" step="0.001" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity ?? 0) }}"></div>
    <div class="col-md-3"><label>Current Stock</label><input class="form-control" type="number" step="0.001" name="current_stock" value="{{ old('current_stock', $product->current_stock ?? 0) }}"></div>
    <div class="col-md-3" style="padding-top:26px"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? 1))> Active</label></div>
    <div class="col-md-12"><label>Description</label><textarea class="form-control" name="description" rows="3">{{ old('description', $product->description ?? '') }}</textarea></div>
</div>
<div style="margin-top:16px"><button class="btn btn-success">Save Product</button> <a class="btn btn-default" href="{{ route('pos.products.index') }}">Cancel</a></div>
