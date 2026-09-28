@extends('layouts.app')
@section('title', __('purchase::lang.stock_purchase_sale_report'))

@section('content')
<section class="content-header"><h1>@lang('purchase::lang.stock_purchase_sale_report')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            @include('purchase::reports.partials.filters')
            @include('purchase::reports.stock_purchase_sale.table')
        </div>
    </div>
</section>
@endsection

@section('javascript')
@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/reports/stock-purchase-sale.js')) !!}</script>
@endsection
