@extends('product::layouts.app', ['title'=>__('product::common.edit'), 'heading'=>__('product::common.edit')])
@section('product_content')
{!! Form::model(2769singular, ['route'=>['product.brands.update', 2769singular->id],'method'=>'put']) !!}
<div class="form-group">{!! Form::label('name', __('product::common.name').':') !!}{!! Form::text('name', null, ['class'=>'form-control','required']) !!}</div>
<button class="btn btn-primary">@lang('product::common.update')</button>
{!! Form::close() !!}
@endsection
