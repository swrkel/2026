@extends('pos::layouts.app')
@section('pos_content')<div class="card"><div class="card-header"><strong>Add Product</strong></div><div class="card-body"><form method="post" action="{{ route('pos.products.store') }}">@csrf @include('pos::products._form')</form></div></div>@endsection
