@extends('layouts.app')
@section('title', __('purchase::lang.add_supplier_payment'))
@section('content')
<section class="content-header"><h1>@lang('purchase::lang.add_supplier_payment')</h1></section>
<section class="content">{!! Form::open(['route' => 'purchase.supplier-payments.store', 'method' => 'post', 'id' => 'supplier_payment_form']) !!}@include('purchase::payments.partials.form')<button type="submit" class="btn btn-primary">@lang('messages.save')</button>{!! Form::close() !!}</section>
@endsection
