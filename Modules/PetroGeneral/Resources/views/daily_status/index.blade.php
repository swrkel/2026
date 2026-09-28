@extends('layouts.app')
@section('title', __('petrogeneral::lang.daily_status_report'))
@section('content')
<section class="content-header"><h1>@lang('petrogeneral::lang.daily_status_report')</h1></section>
<section class="content no-print pg-page"><div class="nav-tabs-custom">
<ul class="nav nav-tabs">
<li class="{{ $active_tab == 'summary' ? 'active' : '' }}"><a href="#pg_daily_summary" data-toggle="tab">Summary</a></li>
<li class="{{ $active_tab == 'sales' ? 'active' : '' }}"><a href="#pg_daily_sales" data-toggle="tab">Sales</a></li>
<li class="{{ $active_tab == 'payments' ? 'active' : '' }}"><a href="#pg_daily_payments" data-toggle="tab">Payments</a></li>
<li class="{{ $active_tab == 'stock' ? 'active' : '' }}"><a href="#pg_daily_stock" data-toggle="tab">Stock</a></li>
</ul><div class="tab-content">
@include('petrogeneral::daily_status.tabs.summary')
@include('petrogeneral::daily_status.tabs.sales')
@include('petrogeneral::daily_status.tabs.payments')
@include('petrogeneral::daily_status.tabs.stock')
</div></div></section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/daily_status/summary.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/daily_status/sales.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/daily_status/payments.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/daily_status/stock.js') }}"></script>
@endsection
