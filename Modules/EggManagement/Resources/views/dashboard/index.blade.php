@extends('egg::layouts.app',['title'=>'Dashboard'])

@section('egg_subtitle')
Production, stock, purchases and sales overview for the selected period.
@endsection

@section('head_actions')
<div class="egg-dashboard-head-actions no-print">
    <div class="egg-date-pill"><i class="fa fa-calendar"></i><span>{{ now()->format('Y-m-d H:i') }}</span></div>
    <a href="{{ route('egg.sales.create') }}" class="egg-btn egg-btn-purple egg-new-sale-btn"><i class="fa fa-plus"></i><span>New Sale</span></a>
</div>
@endsection

@section('egg_styles')
@php
    $eggDashboardCssPublic = 'modules/egg-management/css/dashboard.css';
    $eggDashboardCssSource = base_path('Modules/EggManagement/Resources/assets/css/dashboard.css');
@endphp
@if(file_exists(public_path($eggDashboardCssPublic)))
<link rel="stylesheet" href="{{ asset($eggDashboardCssPublic) }}?v=20260917-1">
@elseif(file_exists($eggDashboardCssSource))
<style>{!! file_get_contents($eggDashboardCssSource) !!}</style>
@endif
@endsection

@section('egg_content')
    @include('egg::partials.date_filter')
    @include('egg::dashboard.partials.kpis')

    <div class="egg-dashboard-main-grid">
        @include('egg::dashboard.partials.recent_activity')
        @include('egg::dashboard.partials.quick_actions')
    </div>

    <div class="egg-dashboard-bottom-grid">
        @include('egg::dashboard.partials.operational_flow')
        @include('egg::dashboard.partials.module_status')
    </div>
@endsection
