@extends('layouts.app')

@section('title', 'Customers Dashboard')

@section('content')

<section class="content-header customers-page-header">
    <h1>
        Customers Dashboard
        <small>Standalone Customers Module</small>
    </h1>
</section>

<section class="content customers-dashboard-page">

    @include('customers::dashboard.partials.kpi_cards')

    <div class="row">
        <div class="col-md-8 col-sm-12">
            @include('customers::dashboard.partials.recent_customers')
        </div>
        <div class="col-md-4 col-sm-12">
            @include('customers::dashboard.partials.quick_actions')
            @include('customers::dashboard.partials.ageing_snapshot')
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-sm-12">
            @include('customers::dashboard.partials.recent_transactions')
        </div>
        <div class="col-md-4 col-sm-12">
            @include('customers::dashboard.partials.menu_overview')
        </div>
    </div>

</section>

<style>
    .customers-dashboard-page .customers-kpi-card {
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
        min-height: 98px;
    }
    .customers-dashboard-page .info-box-icon {
        border-radius: 0;
    }
    .customers-dashboard-page .customers-quick-link {
        text-align: left;
        margin-bottom: 8px;
        border-radius: 8px;
        font-weight: 600;
    }
    .customers-dashboard-page .customers-quick-link i {
        width: 18px;
        text-align: center;
        margin-right: 6px;
    }
    .customers-dashboard-page .customers-dashboard-box {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
    }
    .customers-dashboard-page .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .customers-dashboard-page .customers-money,
    .customers-dashboard-page .customers-number {
        text-align: right;
        white-space: nowrap;
    }
    .customers-dashboard-page .customers-status-badge {
        display: inline-block;
        min-width: 74px;
        text-align: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
    }
    .customers-dashboard-page .customers-status-active { background: #dcfce7; color: #166534; }
    .customers-dashboard-page .customers-status-inactive { background: #fee2e2; color: #991b1b; }
    @media (max-width: 767px) {
        .customers-dashboard-page .box-tools { float: none !important; margin-top: 8px; }
        .customers-dashboard-page .table-responsive { border: 0; }
        .customers-dashboard-page .info-box-content { min-height: 92px; }
    }
</style>

@endsection
