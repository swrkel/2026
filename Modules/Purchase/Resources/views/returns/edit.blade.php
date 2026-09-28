@extends('layouts.app')
@section('title', __('purchase::lang.edit_purchase_return'))
@section('content')
<section class="content-header"><h1>@lang('purchase::lang.edit_purchase_return')</h1></section>
<section class="content">{!! Form::model($return, ['route' => ['purchase.returns.update', $return->id], 'method' => 'put', 'id' => 'purchase_return_form']) !!}@include('purchase::returns.partials.form')<button type="submit" class="btn btn-primary">@lang('messages.update')</button>{!! Form::close() !!}</section>
@endsection
