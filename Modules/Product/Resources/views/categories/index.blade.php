@extends('product::layouts.app', ['title'=>__('product::categories.categories'), 'heading'=>__('product::categories.categories')])
@section('product_content')
<div class="box"><div class="box-header"><a class="btn btn-primary" href="{{ route('product.categories.create') }}">@lang('product::common.add')</a></div><div class="box-body">
<table class="table table-bordered table-striped product-simple-table" data-url="{{ route('product.categories.datatable') }}"><thead><tr><th>@lang('product::common.actions')</th><th>@lang('product::common.name')</th></tr></thead></table>
</div></div>
<script src="{{ asset('modules/product/js/settings/simple-table.js') }}"></script>
@endsection
