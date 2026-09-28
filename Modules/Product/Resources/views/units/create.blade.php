@extends('product::layouts.app', ['title'=>__('product::common.add'), 'heading'=>__('product::common.add')])
@section('product_content')
{!! Form::open(['route'=>'product.units.store','method'=>'post']) !!}
<div class="form-group">{!! Form::label('name', __('product::common.name').':') !!}{!! Form::text('name', null, ['class'=>'form-control','required']) !!}</div>
<button class="btn btn-primary">@lang('product::common.save')</button>
{!! Form::close() !!}
@endsection
