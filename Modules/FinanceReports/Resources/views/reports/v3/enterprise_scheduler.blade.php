@extends('layouts.app')
@section('title', 'Enterprise Report Scheduler - New')
@section('content')
<section class="content-header"><h1>Enterprise Report Scheduler - New <small>Finance Reports Enterprise v3.0</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Schedule groups</h3></div><div class="box-body">
<table class="table table-bordered table-striped"><thead><tr><th>Schedule</th><th>Reports</th><th>Output</th></tr></thead><tbody><tr><td>Daily</td><td>Cash Position, Bank Position, Daily Financial Summary</td><td>PDF / Excel</td></tr><tr><td>Weekly</td><td>Branch Summary, Receivables, Payables</td><td>PDF / Excel</td></tr><tr><td>Monthly</td><td>Board Pack, P&L, Balance Sheet, Cash Flow</td><td>PDF Pack</td></tr><tr><td>Quarterly / Annual</td><td>Financial Statements, Ratio Analysis, Audit Pack</td><td>PDF / Excel</td></tr></tbody></table>
</div></div>
</section>
@endsection
