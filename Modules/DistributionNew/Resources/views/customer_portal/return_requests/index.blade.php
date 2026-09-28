@extends('layouts.app')
@section('title', __('distributionnew::customer_portal.return_requests'))
@section('content')
<section class="content-header"><h1>@lang('distributionnew::customer_portal.return_requests')</h1></section>
<section class="content disnew-pos-shell">
  <div class="row disnew-kpi-row">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span><div class="info-box-content"><span class="info-box-text">@lang('distributionnew::customer_portal.orders')</span><span class="info-box-number">{{ $summary['open_orders'] ?? 0 }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon"><i class="fa fa-truck"></i></span><div class="info-box-content"><span class="info-box-text">@lang('distributionnew::customer_portal.deliveries')</span><span class="info-box-number">{{ $summary['pending_deliveries'] ?? 0 }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon"><i class="fa fa-file-text"></i></span><div class="info-box-content"><span class="info-box-text">@lang('distributionnew::customer_portal.invoices')</span><span class="info-box-number">{{ $summary['unpaid_invoices'] ?? 0 }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon"><i class="fa fa-comments"></i></span><div class="info-box-content"><span class="info-box-text">@lang('distributionnew::customer_portal.complaints')</span><span class="info-box-number">{{ $summary['open_complaints'] ?? 0 }}</span></div></div></div>
  </div>
  <div class="box box-solid disnew-card"><div class="box-header with-border"><h3 class="box-title">@lang('distributionnew::customer_portal.return_requests')</h3></div><div class="box-body"><div class="table-responsive"><table class="table table-bordered table-striped disnew-table" id="disnew_customer_return_requests_table"><thead><tr><th>@lang('messages.action')</th><th>@lang('messages.date')</th><th>@lang('messages.status')</th><th>@lang('messages.description')</th></tr></thead><tbody></tbody></table></div></div></div>
</section>
@endsection
