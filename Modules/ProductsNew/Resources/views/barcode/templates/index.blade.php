@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="productsnew-card">
    <div class="productsnew-card-header"><h3>Barcode / Label Templates</h3><a class="btn btn-default" href="{{ route('products-new.barcode.index') }}">Back</a></div>
    <form method="post" action="{{ route('products-new.barcode.templates.store') }}" class="productsnew-grid productsnew-grid-4">@csrf
        <div><label>Name</label><input class="form-control" name="name" required></div>
        <div><label>Paper Size</label><select class="form-control" name="paper_size"><option>A4</option><option>A5</option><option>Roll</option></select></div>
        <div><label>Label Width</label><input class="form-control" name="label_width" value="38"></div>
        <div><label>Label Height</label><input class="form-control" name="label_height" value="25"></div>
        <div><label>Labels Per Row</label><input class="form-control" name="labels_per_row" value="3"></div>
        <div><label>Barcode Type</label><select class="form-control" name="barcode_type"><option>CODE128</option><option>EAN13</option><option>QR</option></select></div>
        <label><input type="checkbox" name="show_name" value="1" checked> Show Name</label>
        <label><input type="checkbox" name="show_price" value="1" checked> Show Price</label>
        <label><input type="checkbox" name="show_sku" value="1" checked> Show SKU</label>
        <label><input type="checkbox" name="is_default" value="1"> Default</label>
        <div class="productsnew-actions"><button class="btn btn-success">Save Template</button></div>
    </form>
</div>
<div class="productsnew-card"><table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Paper</th><th>Size</th><th>Type</th><th>Default</th></tr></thead><tbody>@forelse($templates as $t)<tr><td>{{ $t->name }}</td><td>{{ $t->paper_size }}</td><td>{{ $t->label_width }} x {{ $t->label_height }}</td><td>{{ $t->barcode_type }}</td><td>{{ $t->is_default ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="5" class="text-center">No templates yet</td></tr>@endforelse</tbody></table></div>
@endsection
