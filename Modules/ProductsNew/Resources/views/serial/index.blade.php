@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-page">
    <div class="productsnew-header"><h1>Serial Number Centre</h1><p>Track each serialized product independently with status, location and IMEI/asset tags.</p></div>
    <div class="productsnew-card">
        <form method="POST" action="{{ route('products-new.serial.store') }}" class="productsnew-grid productsnew-grid-4">@csrf
            <select name="product_id" class="form-control pn-searchable-select" required>
                                <option value="">Select Active Product</option>
                                @foreach($lookups['productsNew'] ?? [] as $product)
                                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                                        {{ $product->name }}{{ !empty($product->sku) ? ' — ' . $product->sku : '' }}
                                    </option>
                                @endforeach
                            </select>
            <input name="serial_no" class="form-control" placeholder="Serial No" required>
            <input name="imei_no" class="form-control" placeholder="IMEI No">
            <input name="asset_tag" class="form-control" placeholder="Asset Tag">
            <input name="location_id" class="form-control" placeholder="Location ID">
            <input name="purchase_reference" class="form-control" placeholder="Purchase Reference">
            <input name="purchase_date" type="date" class="form-control">
            <select name="status" class="form-control"><option value="available">Available</option><option value="sold">Sold</option><option value="reserved">Reserved</option><option value="service">Service</option><option value="damaged">Damaged</option></select>
            <button class="btn productsnew-btn-primary">Add Serial</button>
        </form>
    </div>
    <div class="productsnew-card">
        <table class="table table-bordered table-striped"><thead><tr><th>Serial</th><th>IMEI</th><th>Product</th><th>Location</th><th>Status</th><th>Action</th></tr></thead><tbody>
        @foreach($serials as $serial)<tr><td>{{ $serial->serial_no }}</td><td>{{ $serial->imei_no }}</td><td>{{ $serial->product_id }}</td><td>{{ $serial->location_id }}</td><td><span class="label label-info">{{ $serial->status }}</span></td><td><a href="{{ route('products-new.serial.show',$serial) }}" class="btn btn-xs productsnew-btn-soft">View</a></td></tr>@endforeach
        </tbody></table>{{ $serials->links() }}
    </div>
</div>

@endsection
