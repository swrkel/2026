@extends('layouts.app')
@section('title', 'Finance Reports')
@section('content')
<section class="content-header"><h1>Finance Reports</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.dashboard')])
@include('financereports::layouts.toolbar')
<div class="row">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-arrow-up"></i></span><div class="info-box-content"><span class="info-box-text">Income</span><span class="info-box-number display_currency" data-currency_symbol="true">{{ number_format($pl['totals']['income'], 4, '.', '') }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-arrow-down"></i></span><div class="info-box-content"><span class="info-box-text">Expenses</span><span class="info-box-number display_currency" data-currency_symbol="true">{{ number_format($pl['totals']['expenses'], 4, '.', '') }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-balance-scale"></i></span><div class="info-box-content"><span class="info-box-text">Net Profit / Loss</span><span class="info-box-number display_currency" data-currency_symbol="true">{{ number_format($pl['totals']['net_profit'], 4, '.', '') }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-calculator"></i></span><div class="info-box-content"><span class="info-box-text">Trial Balance Difference</span><span class="info-box-number display_currency" data-currency_symbol="true">{{ number_format($tb['totals']['difference'], 4, '.', '') }}</span></div></div></div>
</div>
<div class="box box-solid"><div class="box-header"><h3 class="box-title">Read-only reporting module</h3></div><div class="box-body">
    <p>This standalone module reads accounting data only. It does not post, edit, delete, or replace any existing Finance module transactions.</p>
    <a class="btn btn-primary" href="{{ route('finance-reports.trial-balance-new') }}">Trial Balance - New</a>
    <a class="btn btn-primary" href="{{ route('finance-reports.balance-sheet-new') }}">Balance Sheet - New</a>
    <a class="btn btn-primary" href="{{ route('finance-reports.profit-loss-new') }}">Profit & Loss - New</a>
    <a class="btn btn-primary" href="{{ route('finance-reports.account-ledger-new') }}">Account Ledger - New</a>
</div></div>
</section>
@stop
