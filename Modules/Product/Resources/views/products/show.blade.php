@extends('product::layouts.app', ['title'=>__('product::product.view_product'), 'heading'=>$product->name])
@section('product_content')
<div class="box"><div class="box-body"><table class="table table-bordered"><tr><th>@lang('product::common.name')</th><td>{{ $product->name }}</td></tr><tr><th>@lang('product::product.sku')</th><td>{{ $product->sku }}</td></tr></table></div></div>
@endsection
