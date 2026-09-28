@php
$pageTitle='Restaurant Manager Dashboard';
$cards=[
['title'=>'Sales Total','value'=>number_format($kpis['sales_total'] ?? 0,2),'hint'=>'Today'],
['title'=>'Gross Profit','value'=>number_format($kpis['gross_profit'] ?? 0,2),'hint'=>'Today'],
['title'=>'Food Cost %','value'=>number_format($kpis['food_cost_percent'] ?? 0,2).'%','hint'=>'Today'],
['title'=>'Avg Rating','value'=>number_format($kpis['average_rating'] ?? 0,2),'hint'=>'Feedback'],
];
$panelTitle='Manager KPIs';
$rows=[['Sales','Daily/weekly/monthly trends','Review performance'],['Kitchen','Preparation performance','Reduce delays'],['Staff','Waiter/cashier performance','Improve service'],['Customer','Feedback and ratings','Resolve complaints']];
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
