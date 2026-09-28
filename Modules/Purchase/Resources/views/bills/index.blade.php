@extends('layouts.app')
@section('title', __('purchase::lang.purchase_bills'))
@section('content')
<section class="content-header"><h1>@lang('purchase::lang.purchase_bills')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header">
            <a href="{{ route('purchase.bills.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('messages.add')</a>
        </div>
        <div class="box-body">@include('purchase::bills.partials.table')</div>
    </div>
</section>
@endsection
@section('javascript')@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-bill-list.js')) !!}</script>@endsection
