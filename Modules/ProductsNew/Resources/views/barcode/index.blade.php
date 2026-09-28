@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="productsnew-card">
    <div class="productsnew-card-header"><h3>{{ __('productsnew::product.barcode_center') }}</h3><a class="btn btn-primary" href="{{ route('products-new.barcode.templates') }}">Templates</a></div>
    <form method="post" action="{{ route('products-new.barcode.queue') }}" class="productsnew-grid productsnew-grid-4">@csrf
        <div><label>Product</label><select name="product_id" class="form-control" required><option value="">Select</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} @if($p->sku)({{ $p->sku }})@endif</option>@endforeach</select></div>
        <div><label>Template</label><select name="template_id" class="form-control"><option value="">Default</option>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
        <div><label>Barcode / QR Value</label><input class="form-control" name="barcode_value" placeholder="Leave blank to use SKU"></div>
        <div><label>Quantity</label><input class="form-control" type="number" min="1" name="quantity" value="1"></div>
        <div class="productsnew-actions"><button class="btn btn-success">Add To Print Queue</button></div>
    </form>
</div>
<div class="productsnew-card">
    <h4>Print Options</h4>
    <div class="productsnew-toolbar"><button class="btn btn-info">Preview Labels</button><button class="btn btn-warning">Print Queue</button><button class="btn btn-secondary">Clear Selected</button></div>
</div>
@endsection
