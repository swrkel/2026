@php
$pageTitle='Waiter Dashboard';
$cards=[['title'=>'Assigned Tables','value'=>0,'hint'=>'Waiter allocation'],['title'=>'Running Orders','value'=>0,'hint'=>'Active service'],['title'=>'Bill Requests','value'=>0,'hint'=>'Customer requested'],['title'=>'Customer Requests','value'=>0,'hint'=>'Needs attention']];
$panelTitle='Waiter Service Flow';
$rows=[['Create Sale','Waiter/cashier can create order','Send to kitchen'],['Assigned Tables','Monitor own tables','Serve faster'],['Bill Request','Request bill print','Coordinate cashier'],['Transfer Table','Move order/table','Track audit']];
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
