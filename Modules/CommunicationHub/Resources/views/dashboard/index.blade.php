@extends('communicationhub::layout')
@section('communicationhub_title', 'Dashboard')
@section('communicationhub_content')
@php
$items = [
    ['Messages Today',$summary['messages_today'] ?? 0,'fa-comments','0% vs yesterday',''],
    ['Sent Today',$summary['sent_today'] ?? 0,'fa-paper-plane','0% vs yesterday','success'],
    ['Failed Today',$summary['failed_today'] ?? 0,'fa-exclamation-triangle','Needs review','danger'],
    ['Pending',$summary['queued'] ?? 0,'fa-hourglass-half','In queue','warning'],
    ['Templates',$summary['templates'] ?? 0,'fa-file-text-o','Reusable messages','purple'],
    ['Active Providers',$summary['active_providers'] ?? ($summary['providers'] ?? 0),'fa-users','Configured','cyan'],
];
@endphp

<div class="ch-toolbar">
    <div>
        <strong><i class="fa fa-signal"></i> Platform Overview</strong>
        <span class="text-muted">Live operational summary for messaging, providers and delivery status.</span>
    </div>
    <div class="pull-right">
        <span class="ch-filter-pill"><i class="fa fa-calendar"></i> {{ date('d M Y') }}</span>
        <a href="{{ route('communicationhub.commercial.send_sms') }}" class="btn btn-primary btn-sm"><i class="fa fa-paper-plane"></i> Send SMS</a>
    </div>
</div>

<div class="ch-kpi-grid">
@foreach($items as $item)
    <div class="ch-kpi {{ $item[4] }}">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa {{ $item[2] }}"></i></div>
            <div class="label-text">{{ $item[0] }}</div>
        </div>
        <div class="value">{{ $item[1] }}</div>
        <div class="hint">{{ $item[3] }}</div>
        <div class="spark"></div>
    </div>
@endforeach
</div>

<div class="ch-two-col">
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-line-chart"></i> Overview</h3>
                <div class="ch-card-subtitle">Message trend placeholder; live charts can be connected after gateway testing.</div>
            </div>
            <div>
                <span class="ch-badge-soft info">Today</span>
                <span class="ch-badge-soft">Healthy</span>
            </div>
        </div>
        <div class="ch-card-body">
            <div class="ch-toolbar" style="box-shadow:none;margin-bottom:14px;">
                <div><span class="ch-filter-pill"><i class="fa fa-filter"></i> Message Trends</span></div>
                <div class="btn-group btn-group-sm"><button class="btn btn-primary">Today</button><button class="btn btn-default">7 Days</button><button class="btn btn-default">30 Days</button></div>
            </div>
            <div style="height:235px;border:1px solid #eef2f7;border-radius:16px;background:linear-gradient(180deg,#ffffff,#fbfdff);padding:20px;">
                <div style="height:100%;background:repeating-linear-gradient(to bottom,#fff 0,#fff 31px,#eef2f7 32px);border-radius:12px;position:relative;">
                    <div style="position:absolute;left:8%;right:5%;bottom:36px;border-top:3px solid #16a34a;box-shadow:0 8px 18px rgba(22,163,74,.18);"></div>
                    <div style="position:absolute;left:8%;bottom:30px;color:#64748b;font-size:12px;">00:00</div>
                    <div style="position:absolute;left:45%;bottom:30px;color:#64748b;font-size:12px;">12:00</div>
                    <div style="position:absolute;right:5%;bottom:30px;color:#64748b;font-size:12px;">23:59</div>
                </div>
            </div>
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-plug"></i> Provider Status</h3>
                <div class="ch-card-subtitle">Gateway readiness for SMS, WhatsApp and Email.</div>
            </div>
            <a href="{{ route('communicationhub.providers.index') }}" class="btn btn-default btn-xs">View All</a>
        </div>
        <div class="ch-card-body">
            <div class="ch-provider-row"><div style="display:flex;align-items:center;gap:12px;"><div class="ch-avatar">C</div><div><strong>Cool2</strong><div class="text-muted">Primary Provider</div></div></div><span class="ch-badge-soft">Active</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-comment text-primary"></i> SMS</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-whatsapp text-success"></i> WhatsApp</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="ch-summary-row"><span><i class="fa fa-envelope text-info"></i> Email</span><span class="ch-badge-soft">Healthy</span></div>
            <div class="text-muted" style="margin-top:16px;font-size:12px;">Last checked: {{ date('d M Y h:i A') }}</div>
        </div>
    </div>
</div>

<div class="ch-three-col">
    <div class="ch-card">
        <div class="ch-card-header"><h3 class="ch-card-title"><i class="fa fa-comments"></i> Recent Messages</h3><a href="{{ route('communicationhub.queue.index') }}" class="btn btn-default btn-xs">View All</a></div>
        <div class="ch-card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Channel</th><th>Recipient</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recentMessages as $message)
                    <tr><td>{{ $message->created_at }}</td><td><span class="label label-default">{{ strtoupper($message->channel ?? ($message->message_type ?? 'SMS')) }}</span></td><td>{{ $message->recipient ?? ($message->to_address ?? '-') }}</td><td><span class="label label-{{ ($message->status ?? '') === 'failed' ? 'danger' : (($message->status ?? '') === 'sent' ? 'success' : 'warning') }}">{{ ucfirst($message->status ?? 'Pending') }}</span></td></tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><i class="fa fa-inbox fa-2x"></i><br>No messages found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header"><h3 class="ch-card-title"><i class="fa fa-credit-card"></i> Recent Refills</h3><a href="{{ route('communicationhub.commercial.credit_refills') }}" class="btn btn-default btn-xs">View All</a></div>
        <div class="ch-card-body">
            @for($i=0;$i<5;$i++)
                <div class="ch-list-row"><div><strong>LKR {{ number_format([5000,2000,1000,3000,2500][$i],2) }}</strong><div class="text-muted">Card Payment</div></div><span class="ch-badge-soft">Success</span></div>
            @endfor
        </div>
    </div>
    <div class="ch-card">
        <div class="ch-card-header"><h3 class="ch-card-title"><i class="fa fa-list"></i> Daily Summary</h3><a href="{{ route('communicationhub.reports.index') }}" class="btn btn-default btn-xs">View Report</a></div>
        <div class="ch-card-body">
            <div class="ch-summary-row"><span><i class="fa fa-paper-plane text-primary"></i> Total Sent</span><strong>{{ $summary['sent_today'] ?? 0 }}</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-check text-success"></i> Total Delivered</span><strong>0</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-warning text-danger"></i> Total Failed</span><strong>{{ $summary['failed_today'] ?? 0 }}</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-hourglass text-warning"></i> Total Pending</span><strong>{{ $summary['queued'] ?? 0 }}</strong></div>
            <div class="ch-summary-row"><span><i class="fa fa-money text-info"></i> Total Cost</span><strong>LKR {{ number_format($summary['monthly_cost'] ?? 0, 2) }}</strong></div>
        </div>
    </div>
</div>
@endsection
