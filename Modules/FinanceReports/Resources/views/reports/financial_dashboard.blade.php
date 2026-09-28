@extends('layouts.app')
@section('title', 'Financial Dashboard - New')
@section('content')
<section class="content-header"><h1>Financial Dashboard - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.financial-dashboard-new')])
@include('financereports::layouts.toolbar')
<div class="row">
@foreach($report['kpis'] as $label => $amount)
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-line-chart"></i></span><div class="info-box-content"><span class="info-box-text">{{ ucwords(str_replace('_',' ', $label)) }}</span><span class="info-box-number display_currency" data-currency_symbol="true">{{ number_format($amount, 4, '.', '') }}</span></div></div></div>
@endforeach
</div>
<div class="box box-solid"><div class="box-header"><h3 class="box-title">Summary</h3></div><div class="box-body"><p>This dashboard is read-only and supports selected Branch/Location or Consolidated - All Locations.</p></div></div>
</section>
@stop
