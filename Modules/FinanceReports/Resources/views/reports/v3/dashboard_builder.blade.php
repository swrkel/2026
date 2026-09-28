@extends('layouts.app')
@section('title', 'Enterprise Dashboard Builder - New')
@section('content')
<section class="content-header"><h1>Enterprise Dashboard Builder - New <small>Finance Reports Enterprise v3.0</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Dashboard templates</h3></div><div class="box-body">
<table class="table table-bordered table-striped"><thead><tr><th>Dashboard</th><th>Recommended Widgets</th></tr></thead><tbody><tr><td>CEO Dashboard</td><td>Profit, Revenue, Assets, Cash, Branch Ranking</td></tr><tr><td>CFO Dashboard</td><td>Liquidity, Ratios, Forecast, Working Capital</td></tr><tr><td>Branch Manager Dashboard</td><td>Branch Revenue, Branch Expenses, Branch Profit, Collections</td></tr><tr><td>Finance Manager Dashboard</td><td>Trial Balance, Cash, Bank, Receivables, Payables</td></tr></tbody></table><p class="text-muted">This v3 page provides the standalone structure for configurable dashboards without changing existing Finance logic.</p>
</div></div>
</section>
@endsection
