@extends('layouts.app')
@section('title', __('purchase::lang.purchase_orders'))

@section('content')
<section class="content-header"><h1>@lang('purchase::lang.purchase_orders')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header">
            <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('messages.add')</a>
        </div>
        <div class="box-body">@include('purchase::orders.partials.table')</div>
    </div>
</section>
@endsection
