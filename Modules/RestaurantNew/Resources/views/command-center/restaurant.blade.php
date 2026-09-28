@php
$pageTitle='Restaurant Command Center';
$cards=[
['title'=>'Gross Sales','value'=>number_format($summary['gross_sales'] ?? 0,2),'hint'=>'Today'],
['title'=>'Running Orders','value'=>$summary['running_orders'] ?? 0,'hint'=>'Live orders'],
['title'=>'Ready Orders','value'=>$summary['ready_orders'] ?? 0,'hint'=>'Ready to serve'],
['title'=>'Low Stock Alerts','value'=>$summary['low_stock_alerts'] ?? 0,'hint'=>'Inventory attention'],
];
$panelTitle='Restaurant Live Operations';
$rows=[['Tables','Active / Available monitoring','Review occupied and available tables'],['Kitchen','Waiting and ready orders','Expedite delayed tickets'],['Delivery','Assigned and pending deliveries','Check rider assignments'],['Cashier','Sales and open bills','Close pending bills']];
@endphp
@extends('layouts.app')
@section('title', $pageTitle ?? 'Restaurant Command Center')
@section('content')
<section class="content-header"><h1>{{ $pageTitle ?? 'Restaurant Command Center' }}</h1></section>
<section class="content restaurant-new-command-center">
    <div class="restnew-toolbar">
        <a href="{{ route('restaurantnew.command.restaurant') }}" class="btn btn-primary">Restaurant</a>
        <a href="{{ route('restaurantnew.command.kitchen') }}" class="btn btn-primary">Kitchen</a>
        <a href="{{ route('restaurantnew.command.cashier') }}" class="btn btn-primary">Cashier</a>
        <a href="{{ route('restaurantnew.command.waiter') }}" class="btn btn-primary">Waiter</a>
        <a href="{{ route('restaurantnew.command.manager') }}" class="btn btn-primary">Manager</a>
        <a href="{{ route('restaurantnew.command.executive') }}" class="btn btn-primary">Executive</a>
    </div>
    <div class="row restnew-kpi-grid">
        @foreach($cards as $card)
            <div class="col-md-3 col-sm-6">@include('restaurantnew::command-center.partials.kpi-card', $card)</div>
        @endforeach
    </div>
    <div class="box box-solid restnew-panel">
        <div class="box-header"><h3 class="box-title">{{ $panelTitle ?? 'Live Operations' }}</h3></div>
        <div class="box-body">
            <table class="table table-bordered table-striped restnew-command-table">
                <thead><tr><th>Area</th><th>Status</th><th>Action Needed</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/restaurant-command-center.js') }}"></script>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('Modules/RestaurantNew/Resources/assets/css/restaurant-command-center.css') }}">
@endsection
