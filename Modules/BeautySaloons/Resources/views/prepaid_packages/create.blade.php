@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <h3>Create Prepaid Package</h3>
    <form method="POST" action="{{ route('beauty-saloons.prepaid-packages.store') }}" class="bs-form-card">
        @csrf
        <div class="row">
            <div class="col-md-3"><label>Package Code</label><input name="package_code" class="form-control"></div>
            <div class="col-md-5"><label>Package Name</label><input name="package_name" class="form-control" required></div>
            <div class="col-md-2"><label>Type</label><select name="package_type" class="form-control"><option value="service">Service</option><option value="product">Product</option><option value="mixed">Mixed</option></select></div>
            <div class="col-md-2"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div class="col-md-3"><label>Sale Price</label><input name="sale_price" class="form-control input_number" value="0.00"></div>
            <div class="col-md-3"><label>Validity Days</label><input name="valid_days" class="form-control" value="30"></div>
            <div class="col-md-12"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
        </div>
        <hr><h4>Package Lines</h4>
        <div class="table-responsive"><table class="table table-bordered" id="bs-prepaid-lines"><thead><tr><th>Type</th><th>Item Name</th><th>Qty</th><th>Value</th></tr></thead><tbody>
            @for($i=0;$i<5;$i++)<tr><td><select name="lines[{{ $i }}][item_type]" class="form-control"><option value="service">Service</option><option value="product">Product</option></select></td><td><input name="lines[{{ $i }}][item_name]" class="form-control"></td><td><input name="lines[{{ $i }}][qty]" class="form-control input_number" value="1"></td><td><input name="lines[{{ $i }}][value]" class="form-control input_number" value="0.00"></td></tr>@endfor
        </tbody></table></div>
        <div class="text-right"><button class="btn btn-success bs-big-save">Save Package</button></div>
    </form>
</div>
@endsection
