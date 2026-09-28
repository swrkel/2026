@extends('pos::layouts.app', ['title' => 'POS Background Sync Engine'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-arrows-rotate"></i></div><div class="label-text">Pending Queue</div></div><div class="value" id="s383-pending">{{ $status['engine']['pending'] ?? 0 }}</div><div class="hint">Waiting for background sync</div><div class="spark"></div></div>
    <div class="ch-kpi success"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-check-circle"></i></div><div class="label-text">Synced</div></div><div class="value" id="s383-synced">{{ $status['engine']['synced'] ?? 0 }}</div><div class="hint">Confirmed on server</div><div class="spark"></div></div>
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-triangle-exclamation"></i></div><div class="label-text">Conflicts</div></div><div class="value" id="s383-conflict">{{ $status['engine']['conflict'] ?? 0 }}</div><div class="hint">Manager review</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-signal"></i></div><div class="label-text">Network</div></div><div class="value" id="s383-quality">{{ $status['engine']['network_quality'] ?? 'unknown' }}</div><div class="hint">Terminal quality</div><div class="spark"></div></div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-gears text-primary"></i> S383 Background Synchronization Engine</h3><div class="ch-card-subtitle">Automatic retry, dependency-aware batch sync, resume protection, device heartbeat and network monitoring.</div></div></div>
            <div class="ch-card-body table-responsive">
                <table class="table table-bordered pos-standard-table">
                    <thead><tr><th>Control</th><th>Status</th><th>Protection</th></tr></thead>
                    <tbody>
                    @foreach($engineControls as $row)
                        <tr><td><strong>{{ $row['name'] }}</strong></td><td><span class="label label-success">{{ $row['status'] }}</span></td><td>{{ $row['note'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-cloud-arrow-up text-success"></i> Live Engine Controls</h3><div class="ch-card-subtitle">Use during server testing to verify background sync behavior.</div></div></div>
            <div class="ch-card-body">
                <button type="button" class="btn btn-primary" id="s383-run-batch"><i class="fa fa-play"></i> Run Next Batch</button>
                <button type="button" class="btn btn-info" id="s383-heartbeat"><i class="fa fa-heart-pulse"></i> Send Heartbeat</button>
                <button type="button" class="btn btn-warning" id="s383-progress"><i class="fa fa-chart-line"></i> Refresh Progress</button>
                <a class="btn btn-danger" href="{{ route('pos.offline_sync.conflict_manager') }}"><i class="fa fa-shield-halved"></i> Conflict Manager</a>
                <pre id="s383-console" style="margin-top:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px;max-height:260px;overflow:auto;">Waiting...</pre>
            </div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-list-check text-info"></i> Background Sync Rules</h3><div class="ch-card-subtitle">Safe queue order prevents duplicate invoices, return-before-sale, register close before pending sales and partial payment issues.</div></div></div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-3"><div class="pos-soft-box"><strong>1. Dependency Order</strong><br>Register → Shift → Sale → Payment → Return → Close.</div></div>
            <div class="col-md-3"><div class="pos-soft-box"><strong>2. Idempotency</strong><br>Every sync uses device UUID + client token + transaction hash.</div></div>
            <div class="col-md-3"><div class="pos-soft-box"><strong>3. Resume Safe</strong><br>Failed batches leave rows pending/conflict; synced rows are never posted twice.</div></div>
            <div class="col-md-3"><div class="pos-soft-box"><strong>4. Manager Control</strong><br>Unsafe stock/credit/register cases move to Conflict Manager.</div></div>
        </div>
    </div>
</div>

<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_s383_background_sync.js']) }}?v=s383"></script>
@endsection
