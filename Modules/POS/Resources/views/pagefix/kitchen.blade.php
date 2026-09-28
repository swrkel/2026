@extends('pos::layouts.app', ['title' => $title ?? 'POS Kitchen Display'])

@section('pos_page_description', 'Monitor kitchen orders by waiting, preparing and ready status in a clear dashboard view.')
@section('pos_page_actions')
    <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload();"><i class="fa fa-refresh"></i> Refresh</button>
    <a href="{{ url('/pos-module/sales') }}" class="btn btn-success btn-sm"><i class="fa fa-shopping-cart"></i> Sales</a>
@endsection

@section('pos_content')
@php
    $cards = [
        ['label' => 'Waiting', 'value' => '0', 'icon' => 'fa-clock-o', 'hint' => 'Orders waiting to start', 'tone' => 'warning'],
        ['label' => 'Preparing', 'value' => '0', 'icon' => 'fa-fire', 'hint' => 'Orders being prepared', 'tone' => ''],
        ['label' => 'Ready', 'value' => '0', 'icon' => 'fa-check-circle', 'hint' => 'Orders ready to serve', 'tone' => 'success'],
        ['label' => 'Total Queue', 'value' => '0', 'icon' => 'fa-cutlery', 'hint' => 'Current kitchen orders', 'tone' => 'purple'],
    ];
@endphp
<div class="ch-kpi-grid ch-standard-grid">
    @foreach($cards as $card)
        @include('pos::pagefix.partials.kpi-card', ['card' => $card])
    @endforeach
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-cutlery text-primary"></i> Kitchen Order Queue</h3><div class="ch-card-subtitle">Orders sent from POS and restaurant operations will appear here.</div></div>
        <div class="ch-quick-actions">
            <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload();"><i class="fa fa-refresh"></i> Refresh</button>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="ch-toolbar">
            <div class="ch-filter-pill"><i class="fa fa-circle text-warning"></i> Waiting</div>
            <div class="ch-filter-pill"><i class="fa fa-circle text-primary"></i> Preparing</div>
            <div class="ch-filter-pill"><i class="fa fa-circle text-success"></i> Ready</div>
            <div style="margin-left:auto;"><span class="label label-success">Display online</span></div>
        </div>
        <div class="empty-state">
            <i class="fa fa-cutlery fa-3x"></i>
            <h4>No kitchen orders are waiting</h4>
            <p>Restaurant and KOT orders will appear here when they are sent to the kitchen.</p>
        </div>
    </div>
</div>
@endsection
