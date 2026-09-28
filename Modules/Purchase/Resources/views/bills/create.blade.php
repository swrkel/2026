@extends('layouts.app')
@section('title', __('purchase::lang.add_purchase_bill'))
@section('content')
<section class="content-header"><h1>@lang('purchase::lang.add_purchase_bill')</h1></section>
<section class="content">
{!! Form::open(['route' => 'purchase.bills.store', 'method' => 'post', 'id' => 'purchase_bill_form']) !!}
@include('purchase::bills.partials.form')
<button type="submit" class="btn btn-primary">@lang('messages.save')</button>
{!! Form::close() !!}
</section>
@endsection
@section('javascript')@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-bill-create.js')) !!}</script>@endsection
