@extends('communicationhub::layout')
@section('communicationhub_title', 'Communication Hub Dashboard')
@section('communicationhub_content')
@php
    $kpis = [
        ['Packages', $stats['packages'] ?? 0, 'Active commercial SMS plans', 'fa-cubes', ''],
        ['Clients', $stats['clients'] ?? 0, 'Communication service buyers', 'fa-users', 'success'],
        ['Wallets', $stats['wallets'] ?? 0, 'Credit wallets configured', 'fa-credit-card', 'purple'],
        ['Messages', $stats['messages'] ?? 0, 'All tracked communications', 'fa-comments', 'cyan'],
        ['Sent', $stats['sent'] ?? 0, 'Successfully sent messages', 'fa-check-circle', 'success'],
        ['Pending', $stats['pending'] ?? 0, 'Waiting in queue', 'fa-clock-o', 'warning'],
        ['Failed', $stats['failed'] ?? 0, 'Need attention', 'fa-exclamation-triangle', 'danger'],
        ['Profit', number_format($stats['profit'] ?? 0, 2), 'Estimated communication margin', 'fa-line-chart', 'success'],
    ];
@endphp

<div class="ch-kpi-grid ch-standard-grid">
@foreach($kpis as $kpi)
    <div class="ch-kpi {{ $kpi[4] }}">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa {{ $kpi[3] }}"></i></div>
            <div class="label-text">{{ $kpi[0] }}</div>
        </div>
        <div class="value">{{ $kpi[1] }}</div>
        <div class="hint">{{ $kpi[2] }}</div>
        <div class="spark"></div>
    </div>
@endforeach
</div>

<div class="ch-actions-strip">
    <div>
        <strong><i class="fa fa-bolt text-warning"></i> Quick Operations</strong>
        <div class="ch-page-note">Manage communication sales and operations from one professional workspace.</div>
    </div>
    <div>
        <a href="{{ route('communicationhub.commercial.send_sms') }}" class="btn btn-success"><i class="fa fa-paper-plane"></i> Send SMS</a>
        <a href="{{ route('communicationhub.commercial.bulk_sms') }}" class="btn btn-info"><i class="fa fa-list"></i> Bulk SMS</a>
        <a href="{{ route('communicationhub.commercial.credit_refills') }}" class="btn btn-warning"><i class="fa fa-plus-circle"></i> Refill Credits</a>
        <a href="{{ route('communicationhub.commercial.profit_reports') }}" class="btn btn-default"><i class="fa fa-bar-chart"></i> Reports</a>
    </div>
</div>

<div class="ch-two-col">
    <div class="ch-card">
        <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-line-chart"></i> Message Overview</h3><div class="ch-card-subtitle">Daily message activity and channel trend.</div></div><div class="btn-group btn-group-xs"><button class="btn btn-primary">Today</button><button class="btn btn-default">7 Days</button><button class="btn btn-default">30 Days</button></div></div>
        <div class="ch-card-body">
            <div style="height:255px;border:1px solid #eef2f7;border-radius:16px;background:linear-gradient(180deg,#ffffff,#fbfdff);padding:20px;">
                <div style="height:100%;background:repeating-linear-gradient(to bottom,#fff 0,#fff 31px,#eef2f7 32px);border-radius:12px;position:relative;">
                    <div style="position:absolute;left:8%;right:5%;bottom:48px;border-top:3px solid #16a34a;box-shadow:0 8px 18px rgba(22,163,74,.18);"></div>
                    <div style="position:absolute;left:8%;bottom:18px;color:#64748b;font-size:12px;">00:00</div><div style="position:absolute;left:45%;bottom:18px;color:#64748b;font-size:12px;">12:00</div><div style="position:absolute;right:5%;bottom:18px;color:#64748b;font-size:12px;">23:59</div>
                </div>
            </div>
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-plug"></i> Provider Status</h3><div class="ch-card-subtitle">SMS, WhatsApp and Email gateway readiness.</div></div><a href="{{ route('communicationhub.providers.index') }}" class="btn btn-default btn-xs">View All</a></div>
        <div class="ch-card-body">
            <div class="ch-provider-row"><div style="display:flex;align-items:center;gap:12px;"><div class="ch-avatar">C</div><div><strong>Cool2</strong><div class="text-muted">Primary provider</div></div></div><span class="ch-badge-soft">Active</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-comment text-primary"></i> SMS</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-whatsapp text-success"></i> WhatsApp</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-envelope text-info"></i> Email</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="text-muted" style="margin-top:16px;font-size:12px;">Last checked: {{ date('d M Y h:i A') }}</div>
        </div>
    </div>
</div>

<div class="ch-three-col ch-panel-gap">
    <div class="ch-card">
        <div class="ch-card-header"><h3 class="ch-card-title"><i class="fa fa-comments"></i> Recent Messages</h3><a href="{{ route('communicationhub.commercial.delivery_reports') }}" class="btn btn-default btn-xs">View All</a></div>
        <div class="ch-card-body table-responsive">
            <table class="table table-hover"><thead><tr><th>ID</th><th>Recipient</th><th>Status</th><th>Date</th></tr></thead><tbody>
            @forelse($recentMessages as $row)
                <tr><td>#{{ $row->id ?? '' }}</td><td>{{ $row->recipient ?? ($row->to_address ?? '-') }}</td><td><span class="label label-{{ ($row->status ?? '') === 'failed' ? 'danger' : (($row->status ?? '') === 'sent' ? 'success' : 'warning') }}">{{ ucfirst($row->status ?? 'Pending') }}</span></td><td>{{ $row->created_at ?? '' }}</td></tr>
            @empty
                <tr><td colspan="4"><div class="empty-state"><i class="fa fa-inbox fa-2x"></i><br>No messages yet.</div></td></tr>
            @endforelse
            </tbody></table>
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header"><h3 class="ch-card-title"><i class="fa fa-credit-card"></i> Recent Refills</h3><a href="{{ route('communicationhub.commercial.credit_refills') }}" class="btn btn-default btn-xs">View All</a></div>
        <div class="ch-card-body">
            @forelse($recentTransactions as $row)
                <div class="ch-list-row"><div><strong>LKR {{ number_format((float)($row->amount ?? 0),2) }}</strong><div class="text-muted">{{ ucfirst($row->type ?? 'Transaction') }}</div></div><span class="ch-badge-soft">{{ strtoupper($row->status ?? 'OK') }}</span></div>
            @empty
                <div class="empty-state"><i class="fa fa-credit-card fa-2x"></i><br>No wallet activity yet.</div>
            @endforelse
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header"><h3 class="ch-card-title"><i class="fa fa-list"></i> Daily Summary</h3><a href="{{ route('communicationhub.commercial.profit_reports') }}" class="btn btn-default btn-xs">View Report</a></div>
        <div class="ch-card-body">
            <div class="ch-summary-row"><span><i class="fa fa-paper-plane text-primary"></i> Total Sent</span><strong>{{ $stats['sent'] ?? 0 }}</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-check text-success"></i> Total Delivered</span><strong>0</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-warning text-danger"></i> Total Failed</span><strong>{{ $stats['failed'] ?? 0 }}</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-hourglass text-warning"></i> Total Pending</span><strong>{{ $stats['pending'] ?? 0 }}</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-money text-info"></i> Total Revenue</span><strong>LKR {{ number_format((float)($stats['revenue'] ?? 0), 2) }}</strong></div>
        </div>
    </div>
</div>
@endsection
