@extends('product::layouts.app', ['title'=>__('product::product.import_products'), 'heading'=>__('product::product.import_products')])
@section('product_content')
{!! Form::open(['route'=>'product.imports.products','method'=>'post','files'=>true]) !!}
<div class="form-group">{!! Form::label('products_file', __('product::product.file').':') !!}{!! Form::file('products_file', ['class'=>'form-control','required']) !!}</div>
<button class="btn btn-primary">@lang('product::product.import')</button>
{!! Form::close() !!}
@endsection
