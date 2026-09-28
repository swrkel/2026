@extends('layouts.app')
@section('title', __('purchase::lang.edit_purchase_bill'))
@section('content')
<section class="content-header"><h1>@lang('purchase::lang.edit_purchase_bill')</h1></section>
<section class="content">
{!! Form::model($bill, ['route' => ['purchase.bills.update', $bill->id], 'method' => 'put', 'id' => 'purchase_bill_form']) !!}
@include('purchase::bills.partials.form')
<button type="submit" class="btn btn-primary">@lang('messages.update')</button>
{!! Form::close() !!}
</section>
@endsection
@section('javascript')@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-bill-edit.js')) !!}</script>@endsection
