@extends('pos::layouts.app', ['title' => 'POS Offline / Online Sync'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-wifi"></i></div><div class="label-text">Connection</div></div><div class="value" id="pos-online-status">Checking</div><div class="hint">Browser network status</div><div class="spark"></div></div>
    <div class="ch-kpi success"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-list"></i></div><div class="label-text">Pending</div></div><div class="value">{{ $status['queue']['pending'] ?? 0 }}</div><div class="hint">Waiting for sync</div><div class="spark"></div></div>
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-triangle-exclamation"></i></div><div class="label-text">Conflicts</div></div><div class="value">{{ $status['conflicts_open'] ?? 0 }}</div><div class="hint">Manager review needed</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-desktop"></i></div><div class="label-text">Device</div></div><div class="value" style="font-size:16px;word-break:break-all;">{{ $status['device_uuid'] }}</div><div class="hint">Terminal identity</div><div class="spark"></div></div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-arrows-rotate text-primary"></i> Offline Sales Sync Foundation</h3><div class="ch-card-subtitle">This page verifies offline sale queue, server posting, duplicate protection, local product/customer cache, stock snapshot and conflict controls.</div></div></div>
            <div class="ch-card-body">
                <table class="table table-bordered pos-standard-table">
                    <thead><tr><th>Component</th><th>Status</th><th>Purpose</th></tr></thead>
                    <tbody>
                        @foreach($status['tables'] as $table => $ready)
                            <tr>
                                <td><strong>{{ $table }}</strong></td>
                                <td>{!! $ready ? '<span class="label label-success">Ready</span>' : '<span class="label label-danger">Missing SQL</span>' !!}</td>
                                <td>{{ $table == 'pos_offline_sync_queue' ? 'Stores pending browser/device transactions safely.' : ($table == 'pos_offline_sync_conflicts' ? 'Stores transactions needing manager conflict resolution.' : 'Stores/registers POS terminal identity.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="alert alert-info" style="margin-top:12px;">Offline sales are now protected by browser queue + server queue + server-side validation. Enable it terminal-by-terminal after cashier and receipt testing.</div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-database text-success"></i> Browser Cache Test</h3><div class="ch-card-subtitle">Local IndexedDB queue and online/offline detector.</div></div></div>
            <div class="ch-card-body">
                <button type="button" class="btn btn-primary" id="pos-sync-test-btn"><i class="fa fa-vial"></i> Create Local Test Queue Item</button>
                <button type="button" class="btn btn-success" id="pos-sync-send-btn"><i class="fa fa-cloud-arrow-up"></i> Send Browser Queue</button>
                <button type="button" class="btn btn-info" id="pos-sync-server-btn"><i class="fa fa-rotate"></i> Process Server Pending</button>
                <button type="button" class="btn btn-warning" id="pos-sync-cache-btn"><i class="fa fa-download"></i> Refresh Offline Cache</button>
                <button type="button" class="btn btn-danger" id="pos-sync-conflict-btn"><i class="fa fa-triangle-exclamation"></i> Refresh Conflicts</button>
                <a href="{{ route('pos.offline_sync.conflict_manager') }}" class="btn btn-warning"><i class="fa fa-shield-halved"></i> Conflict Manager</a>
                <pre id="pos-sync-console" style="margin-top:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px;max-height:220px;overflow:auto;">Waiting...</pre>
            </div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-shield-halved text-danger"></i> Risk Controls Implemented in S379/S380/S381</h3><div class="ch-card-subtitle">These controls protect sales, stock, payments and Customers module ledger from common offline sync risks.</div></div></div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered pos-standard-table">
            <thead><tr><th style="width:260px;">Risk</th><th>Control</th></tr></thead>
            <tbody>
                @foreach($riskControls as $row)
                    <tr><td><strong>{{ $row['risk'] }}</strong></td><td>{{ $row['control'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'offline-sync.js']) }}?v=s382"></script>
@endsection
