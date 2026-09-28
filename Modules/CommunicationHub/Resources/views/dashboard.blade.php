@extends('communicationhub::layout')
@section('communicationhub_title', 'Communication Hub Dashboard')
@section('communicationhub_content')
@if(!empty($setupRequired))
<div class="alert alert-warning" style="border-radius:14px;">
    <strong><i class="fa fa-warning"></i> Communication Hub database setup is not completed for this tenant.</strong><br>
    Run <code>Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql</code> in the current tenant database.
</div>
@endif

@php
    $items = [
        ['Messages Today', $stats['messages_today'] ?? 0, 'fa-comments', 'Today total', '', route('communicationhub.commercial.delivery_reports')],
        ['Sent Today', $stats['sent_today'] ?? 0, 'fa-paper-plane', 'Successfully sent', 'success', route('communicationhub.commercial.delivery_reports')],
        ['Failed Today', $stats['failed_today'] ?? 0, 'fa-exclamation-triangle', 'Need attention', 'danger', route('communicationhub.commercial.delivery_reports')],
        ['Pending Queue', $stats['pending'] ?? 0, 'fa-hourglass-half', 'Waiting in queue', 'warning', route('communicationhub.queue.index')],
        ['Providers', $stats['providers'] ?? 0, 'fa-plug', 'Configured gateways', 'cyan', route('communicationhub.providers.index')],
        ['Templates', $stats['templates'] ?? 0, 'fa-file-text-o', 'Reusable content', 'purple', route('communicationhub.templates.index')],
        ['Cost Today', number_format((float)($stats['cost_today'] ?? 0), 2), 'fa-money', 'Operational cost', 'success', route('communicationhub.reports.index')],
        ['SMS Balance', '1,998.30', 'fa-credit-card', 'Available credits', 'purple', route('communicationhub.commercial.business_wallets')],
    ];
@endphp

<div class="ch-kpi-grid ch-standard-grid">
@foreach($items as $item)
    <a href="{{ $item[5] }}" class="ch-kpi-link">
        <div class="ch-kpi {{ $item[4] }}">
            <div class="ch-kpi-top">
                <div class="ch-icon"><i class="fa {{ $item[2] }}"></i></div>
                <div class="label-text">{{ $item[0] }}</div>
            </div>
            <div class="value">{{ $item[1] }}</div>
            <div class="hint">{{ $item[3] }} <span class="ch-drill">Open <i class="fa fa-angle-right"></i></span></div>
            <div class="spark"></div>
        </div>
    </a>
@endforeach
</div>

<div class="ch-actions-strip">
    <div>
        <strong><i class="fa fa-bolt text-warning"></i> Quick Operations</strong>
        <div class="ch-page-note">Start daily Communication Hub work from here: send SMS, process bulk SMS, refill credits or review reports.</div>
    </div>
    <div>
        <a href="{{ route('communicationhub.commercial.send_sms') }}" class="btn btn-success"><i class="fa fa-paper-plane"></i> Send SMS</a>
        <a href="{{ route('communicationhub.commercial.bulk_sms') }}" class="btn btn-info"><i class="fa fa-list"></i> Bulk SMS</a>
        <a href="{{ route('communicationhub.commercial.credit_refills') }}" class="btn btn-warning"><i class="fa fa-plus-circle"></i> Refill Credits</a>
        <a href="{{ route('communicationhub.reports.index') }}" class="btn btn-default"><i class="fa fa-bar-chart"></i> Reports</a>
    </div>
</div>

<div class="ch-dashboard-panels">
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-line-chart"></i> Message Trend</h3>
                <div class="ch-card-subtitle">Operational message movement for the current business.</div>
            </div>
            <span class="ch-badge-soft info">Today</span>
        </div>
        <div class="ch-card-body">
            <div class="ch-trend-box">
                <div class="ch-trend-line primary"></div>
                <div class="ch-trend-line success"></div>
                <div class="ch-chart-axis left">00:00</div>
                <div class="ch-chart-axis mid">12:00</div>
                <div class="ch-chart-axis right">23:59</div>
            </div>
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header">
            <div><h3 class="ch-card-title"><i class="fa fa-history"></i> Recent Activity</h3><div class="ch-card-subtitle">Latest communication operations.</div></div>
            <a href="{{ route('communicationhub.commercial.delivery_reports') }}" class="btn btn-default btn-xs">View</a>
        </div>
        <div class="ch-card-body">
            <div class="ch-list-row"><span><i class="fa fa-paper-plane text-success"></i> Manual SMS queue</span><span class="ch-badge-soft">Ready</span></div>
            <div class="ch-list-row"><span><i class="fa fa-list text-info"></i> Bulk SMS workflow</span><span class="ch-badge-soft info">Active</span></div>
            <div class="ch-list-row"><span><i class="fa fa-credit-card text-warning"></i> Wallet refill</span><span class="ch-badge-soft warning">Monitor</span></div>
            <div class="ch-list-row"><span><i class="fa fa-plug text-primary"></i> Provider health</span><span class="ch-badge-soft">Healthy</span></div>
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header">
            <div><h3 class="ch-card-title"><i class="fa fa-plug"></i> Provider Status</h3><div class="ch-card-subtitle">Gateway readiness by channel.</div></div>
            <a href="{{ route('communicationhub.providers.index') }}" class="btn btn-default btn-xs">View All</a>
        </div>
        <div class="ch-card-body">
            <div class="ch-provider-row"><div style="display:flex;align-items:center;gap:12px;"><div class="ch-avatar">S</div><div><strong>SMS Gateway</strong><div class="text-muted">Primary Provider</div></div></div><span class="ch-badge-soft">Active</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-comment text-primary"></i> SMS</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-whatsapp text-success"></i> WhatsApp</span><span class="ch-badge-soft warning">Future</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-envelope text-info"></i> Email</span><span class="ch-badge-soft warning">Future</span></div>
        </div>
    </div>
</div>

<div class="ch-card ch-panel-gap">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-map-signs"></i> Official ERP Dashboard Standard</h3><div class="ch-card-subtitle">This dashboard structure is now the reference layout for future modules.</div></div>
        <span class="ch-badge-soft info">EDS v1.0</span>
    </div>
    <div class="ch-card-body">
        <div class="ch-three-col">
            <div class="ch-mini-standard"><strong>1. 4-column KPI grid</strong><span>Eight clickable cards for key metrics.</span></div>
            <div class="ch-mini-standard"><strong>2. Analytics panels</strong><span>Trends, recent activity and operational status.</span></div>
            <div class="ch-mini-standard"><strong>3. Quick actions</strong><span>Daily operations available without searching menus.</span></div>
        </div>
    </div>
</div>
@endsection
