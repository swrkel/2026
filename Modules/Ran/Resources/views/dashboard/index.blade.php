@extends('ran::layouts.app', ['title' => 'Ran Dashboard'])
@section('ran-content')
@php
    $ranRoute = static fn (string $name, array $parameters = []): string => Route::has($name) ? route($name, $parameters) : '#';
    $ranRouteClass = static fn (string $name): string => Route::has($name) ? '' : ' ran-link-disabled';
@endphp
<x-ran::page-header title="Ran Dashboard" subtitle="Jewellery production, storage and sales command centre">
    <form method="POST" action="{{ route('ran.setup.run') }}">@csrf<button class="btn btn-default"><i class="fa fa-refresh"></i> Prepare Defaults</button></form>
</x-ran::page-header>

<div class="ran-kpi-grid">
    <a class="ran-kpi{{ $ranRouteClass('ran.inventory.index') }}" href="{{ $ranRoute('ran.inventory.index') }}"><span>Available Lots</span><strong>{{ number_format($stockLots) }}</strong><small>{{ number_format($stockWeight, 3) }} net weight</small></a>
    <a class="ran-kpi{{ $ranRouteClass('ran.inventory.index') }}" href="{{ $ranRoute('ran.inventory.index') }}"><span>Stock Cost Value</span><strong>{{ number_format($stockValue, 4) }}</strong><small>Current available stock</small></a>
    <a class="ran-kpi{{ $ranRouteClass('ran.production.index') }}" href="{{ $ranRoute('ran.production.index') }}"><span>Open Production</span><strong>{{ number_format($openProduction) }}</strong><small>Orders not completed</small></a>
    <a class="ran-kpi{{ $ranRouteClass('ran.sales.index') }}" href="{{ $ranRoute('ran.sales.index') }}"><span>Today's Sales</span><strong>{{ number_format($todaySales, 4) }}</strong><small>Posted invoices</small></a>
    <a class="ran-kpi{{ $ranRouteClass('ran.sales.index') }}" href="{{ $ranRoute('ran.sales.index') }}"><span>Customer Due</span><strong>{{ number_format($customerDue, 4) }}</strong><small>Outstanding Ran invoices</small></a>
</div>

<div class="row">
    <div class="col-md-7"><div class="ran-card"><div class="ran-card-title">Operational Shortcuts</div><div class="ran-shortcuts">
        <a class="{{ $ranRouteClass('ran.items.create') }}" href="{{ $ranRoute('ran.items.create') }}"><i class="fa fa-diamond"></i><span>New Item</span></a>
        <a class="{{ $ranRouteClass('ran.purchases.create') }}" href="{{ $ranRoute('ran.purchases.create') }}"><i class="fa fa-truck"></i><span>New Purchase</span></a>
        <a class="{{ $ranRouteClass('ran.production.create') }}" href="{{ $ranRoute('ran.production.create') }}"><i class="fa fa-cogs"></i><span>Production Order</span></a>
        <a class="{{ $ranRouteClass('ran.inventory.receive.create') }}" href="{{ $ranRoute('ran.inventory.receive.create') }}"><i class="fa fa-cubes"></i><span>Receive Stock</span></a>
        <a class="{{ $ranRouteClass('ran.sales.create') }}" href="{{ $ranRoute('ran.sales.create') }}"><i class="fa fa-shopping-bag"></i><span>New Sale</span></a>
        <a class="{{ $ranRouteClass('ran.reports.index') }}" href="{{ $ranRoute('ran.reports.index') }}"><i class="fa fa-bar-chart"></i><span>Reports</span></a>
    </div></div></div>
    <div class="col-md-5"><div class="ran-card"><div class="ran-card-title">Master Data</div><div class="list-group ran-master-links">
        <a class="list-group-item" href="{{ route('ran.masters.metals.index') }}">Metals</a>
        <a class="list-group-item" href="{{ route('ran.masters.purities.index') }}">Purities</a>
        <a class="list-group-item" href="{{ route('ran.masters.gemstones.index') }}">Gemstones</a>
        <a class="list-group-item" href="{{ route('ran.masters.artisans.index') }}">Artisans</a>
        <a class="list-group-item" href="{{ route('ran.masters.designs.index') }}">Designs</a>
    </div></div></div>
</div>
@endsection
