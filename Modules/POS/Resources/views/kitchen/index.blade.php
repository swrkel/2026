@extends('layouts.app')
@section('title', $title ?? __('pos::lang.kitchen_display'))

@section('content')
@include('pos::partials.erp-standard-styles')
<section class="content pos-erp-standard-ui communication-hub-ui">
    <div class="ch-shell">
        <div class="ch-hero pos-module-hero">
            <div>
                <div class="ch-eyebrow">POS Module</div>
                <h1>{{ $title ?? __('pos::lang.kitchen_display') }}</h1>
                <p>{{ __('pos::lang.kitchen_display_note') }}</p>
            </div>
            <div class="ch-quick-actions">
                <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload();"><i class="fa fa-refresh"></i> Refresh</button>
            </div>
        </div>

        <div class="row ch-kpi-row">
            <div class="col-md-4"><div class="ch-kpi ch-kpi-orange"><div class="ch-kpi-icon"><i class="fa fa-clock-o"></i></div><div><small>Waiting</small><h2>0</h2><span>Orders waiting to start</span></div></div></div>
            <div class="col-md-4"><div class="ch-kpi ch-kpi-blue"><div class="ch-kpi-icon"><i class="fa fa-fire"></i></div><div><small>Preparing</small><h2>0</h2><span>Orders in preparation</span></div></div></div>
            <div class="col-md-4"><div class="ch-kpi ch-kpi-green"><div class="ch-kpi-icon"><i class="fa fa-check-circle"></i></div><div><small>Ready</small><h2>0</h2><span>Orders ready to serve</span></div></div></div>
        </div>

        <div class="box box-solid ch-card">
            <div class="box-header with-border ch-card-header"><h3 class="box-title"><i class="fa fa-cutlery"></i> Kitchen Order Queue</h3></div>
            <div class="box-body">
                <div class="well text-center" style="margin:0;padding:40px;">
                    <i class="fa fa-cutlery fa-3x text-muted"></i>
                    <h4>No kitchen orders are waiting.</h4>
                    <p class="text-muted" style="margin-bottom:0;">Restaurant and KOT orders will appear here when sent to the kitchen.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
