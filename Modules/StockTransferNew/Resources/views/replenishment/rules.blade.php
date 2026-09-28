@extends('layouts.app')
@section('title', __('stocktransfernew::lang.min_stock_rules'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.min_stock_rules')</h1></section>
<section class="content stn-page">
<div class="box box-solid"><div class="box-body">
<form method="post" action="{{ route('stock-transfer-new.replenishment.rules.store') }}" class="row">@csrf
<div class="col-md-2"><label>Location</label><input name="location_id" class="form-control" required></div><div class="col-md-2"><label>Store</label><input name="store_id" class="form-control"></div>
<div class="col-md-2"><label>Source Location</label><input name="source_location_id" class="form-control"></div><div class="col-md-2"><label>Source Store</label><input name="source_store_id" class="form-control"></div>
<div class="col-md-2"><label>Product ID</label><input name="product_id" class="form-control" required></div><div class="col-md-2"><label>Variation ID</label><input name="variation_id" class="form-control"></div>
<div class="col-md-2"><label>Min Qty</label><input name="min_qty" class="form-control input_number" required></div><div class="col-md-2"><label>Reorder Qty</label><input name="reorder_qty" class="form-control input_number" required></div>
<div class="col-md-2"><label>Preferred Qty</label><input name="preferred_transfer_qty" class="form-control input_number"></div><div class="col-md-2"><br><button class="btn btn-primary">@lang('messages.save')</button></div>
</form></div></div>
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Location</th><th>Store</th><th>Product</th><th>Min</th><th>Reorder</th><th>Status</th></tr></thead><tbody>
@foreach($rules as $rule)<tr><td>{{ $rule->location_id }}</td><td>{{ $rule->store_id }}</td><td>{{ $rule->product_id }}</td><td>{{ $rule->min_qty }}</td><td>{{ $rule->reorder_qty }}</td><td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach
</tbody></table>{{ $rules->links() }}</div></div>
</section>
@endsection
