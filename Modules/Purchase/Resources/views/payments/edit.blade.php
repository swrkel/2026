@extends('layouts.app')
@section('title', __('purchase::lang.edit_supplier_payment'))
@section('content')
<section class="content-header"><h1>@lang('purchase::lang.edit_supplier_payment')</h1></section>
<section class="content">{!! Form::model($payment, ['route' => ['purchase.supplier-payments.update', $payment->id], 'method' => 'put', 'id' => 'supplier_payment_form']) !!}@include('purchase::payments.partials.form')<button type="submit" class="btn btn-primary">@lang('messages.update')</button>{!! Form::close() !!}</section>
@endsection
