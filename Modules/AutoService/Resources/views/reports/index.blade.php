@extends('autoservice::layouts.master')
@section('title','Auto Service Reports')
@section('autoservice_content')
<div class="box">
    <div class="box-header with-border"><h3 class="box-title">Auto Service Reports</h3></div>
    <div class="box-body">
        <a class="btn btn-primary" href="{{ route('autoservice.reports.daily_summary') }}">Daily Workshop Summary</a>
        <a class="btn btn-primary" href="{{ route('autoservice.reports.service_due') }}">Service Due Report</a>
        <a class="btn btn-primary" href="{{ route('autoservice.reports.profitability') }}">Profitability Report</a>
        <a class="btn btn-primary" href="{{ route('autoservice.reports.mechanic_performance') }}">Mechanic Performance</a>
        <a class="btn btn-primary" href="{{ route('autoservice.reports.invoice_aging') }}">Invoice Aging / Outstanding</a>
    </div>
</div>
@endsection
