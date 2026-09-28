@extends('layouts.app')
@section('title', __('purchase::lang.purchase_sell_report'))

@section('content')
<section class="content-header"><h1>@lang('purchase::lang.purchase_sell_report')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            @include('purchase::reports.partials.filters')
            @include('purchase::reports.purchase_sell.table')
        </div>
    </div>
</section>
@endsection

@section('javascript')
@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/reports/purchase-sell.js')) !!}</script>
@endsection
