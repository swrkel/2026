@extends('pos::layouts.app', ['title' => 'POS Offline Sync Reliability Audit'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-shield-heart"></i></div><div class="label-text">Critical Pending</div></div><div class="value" id="s385-critical">{{ $status['reliability']['critical_pending_count'] ?? 0 }}</div><div class="hint">Blocks register close if not cleared</div><div class="spark"></div></div>
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-clock-rotate-left"></i></div><div class="label-text">Stuck Processing</div></div><div class="value" id="s385-stuck">{{ $status['reliability']['stuck_processing_count'] ?? 0 }}</div><div class="hint">Older than 15 minutes</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-list-check"></i></div><div class="label-text">Test Scenarios</div></div><div class="value" id="s385-tests">{{ $status['reliability']['test_summary']['total'] ?? 0 }}</div><div class="hint">Reliability records</div><div class="spark"></div></div>
    <div class="ch-kpi {{ ($status['reliability']['register_close_safe'] ?? false) ? '' : 'danger' }}"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-cash-register"></i></div><div class="label-text">Register Close</div></div><div class="value">{{ ($status['reliability']['register_close_safe'] ?? false) ? 'Safe' : 'Blocked' }}</div><div class="hint">Offline queue safety gate</div><div class="spark"></div></div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="ch-card">
            <div class="ch-card-header">
                <div><h3 class="ch-card-title"><i class="fa fa-life-ring text-primary"></i> Enterprise Reliability & Disaster Recovery</h3><div class="ch-card-subtitle">S385 validates crash recovery, guaranteed delivery, duplicate prevention, atomic posting, and high-volume offline queue readiness.</div></div>
                <div><button class="btn btn-primary" id="s385-run-integrity"><i class="fa fa-check-double"></i> Verify Integrity</button> <a class="btn btn-warning" href="{{ route('pos.offline_sync.monitoring_center') }}"><i class="fa fa-tower-broadcast"></i> Monitoring</a></div>
            </div>
            <div class="ch-card-body">
                <div class="row">
                    <div class="col-md-6"><div class="pos-soft-box"><strong>Atomic Posting Policy</strong><br>{{ $status['reliability']['atomic_posting_policy'] ?? 'sale_payment_stock_ledger_transactional' }}</div></div>
                    <div class="col-md-6"><div class="pos-soft-box"><strong>Duplicate Protection</strong><br>{{ $status['reliability']['duplicate_protection'] ?? 'device_uuid_client_token_transaction_hash' }}</div></div>
                    <div class="col-md-6"><div class="pos-soft-box"><strong>Oldest Pending</strong><br>{{ $status['reliability']['oldest_pending_at'] ?? 'No pending queue' }}</div></div>
                    <div class="col-md-6"><div class="pos-soft-box"><strong>Last Checked</strong><br>{{ $status['reliability']['last_checked_at'] ?? '-' }}</div></div>
                </div>
                <pre id="s385-integrity-console" style="margin-top:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px;max-height:260px;overflow:auto;">Ready for integrity verification.</pre>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-chart-simple text-success"></i> Reliability Summary</h3><div class="ch-card-subtitle">Disaster recovery testing status.</div></div></div>
            <div class="ch-card-body">
                <div class="pos-soft-box"><strong>Passed</strong><br>{{ $status['reliability']['test_summary']['passed'] ?? 0 }}</div>
                <div class="pos-soft-box"><strong>Warnings</strong><br>{{ $status['reliability']['test_summary']['warning'] ?? 0 }}</div>
                <div class="pos-soft-box"><strong>Failed</strong><br>{{ $status['reliability']['test_summary']['failed'] ?? 0 }}</div>
                <button class="btn btn-success btn-block" id="s385-record-pass"><i class="fa fa-plus"></i> Record Manual Pass</button>
            </div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-flask-vial text-info"></i> Stress Test Scenario Matrix</h3><div class="ch-card-subtitle">Use this during live testing to prove offline/online sync reliability before production sign-off.</div></div></div>
    <div class="ch-card-body">
        <div class="row">
            @foreach($scenarioGroups as $group => $items)
                <div class="col-md-6">
                    <div class="pos-soft-box">
                        <strong>{{ $group }}</strong>
                        <ul style="padding-left:18px;margin-top:8px;margin-bottom:0;">
                            @foreach($items as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_s385_reliability_audit.js']) }}?v=s385"></script>
@endsection
