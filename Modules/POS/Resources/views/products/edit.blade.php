@extends('pos::layouts.app')
@section('pos_content')<div class="card"><div class="card-header"><strong>Edit Product</strong></div><div class="card-body"><form method="post" action="{{ route('pos.products.update', $product->id) }}">@csrf @method('put') @include('pos::products._form')</form></div></div>@endsection
