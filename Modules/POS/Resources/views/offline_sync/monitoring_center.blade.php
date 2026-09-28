@extends('pos::layouts.app', ['title' => 'POS Synchronization Control Center'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-desktop"></i></div><div class="label-text">Online Terminals</div></div><div class="value" id="s384-online">{{ $status['monitor']['online_devices'] ?? 0 }}</div><div class="hint">Heartbeat within 3 minutes</div><div class="spark"></div></div>
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-wifi"></i></div><div class="label-text">Weak / Offline</div></div><div class="value" id="s384-offline">{{ ($status['monitor']['offline_devices'] ?? 0) + ($status['monitor']['weak_network_devices'] ?? 0) }}</div><div class="hint">Needs attention</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-list-check"></i></div><div class="label-text">Pending Queue</div></div><div class="value" id="s384-pending">{{ $status['queue']['pending'] ?? 0 }}</div><div class="hint">Waiting to sync</div><div class="spark"></div></div>
    <div class="ch-kpi danger"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-shield-halved"></i></div><div class="label-text">Open Conflicts</div></div><div class="value" id="s384-conflicts">{{ $status['conflicts_open'] ?? 0 }}</div><div class="hint">Manager review</div><div class="spark"></div></div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="ch-card">
            <div class="ch-card-header">
                <div><h3 class="ch-card-title"><i class="fa fa-tower-broadcast text-primary"></i> Synchronization Control Center</h3><div class="ch-card-subtitle">S384 multi-terminal monitoring, device heartbeat, queue visibility and branch-aware sync health.</div></div>
                <div><button class="btn btn-primary" id="s384-refresh"><i class="fa fa-refresh"></i> Refresh</button> <a class="btn btn-warning" href="{{ route('pos.offline_sync.conflict_manager') }}"><i class="fa fa-triangle-exclamation"></i> Conflicts</a></div>
            </div>
            <div class="ch-card-body table-responsive">
                <table class="table table-bordered pos-standard-table" id="s384-device-table">
                    <thead><tr><th>Device UUID</th><th>Terminal</th><th>Status</th><th>Network</th><th>Queue</th><th>Last Seen</th><th>Trust</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse(($status['devices'] ?? []) as $device)
                        <tr data-device="{{ $device->device_uuid }}">
                            <td><code>{{ $device->device_uuid }}</code></td>
                            <td>{{ $device->terminal_code ?? '-' }}</td>
                            <td><span class="label label-{{ ($device->status ?? '') === 'online' ? 'success' : (($device->status ?? '') === 'blocked' ? 'danger' : 'default') }}">{{ $device->status ?? 'unknown' }}</span></td>
                            <td>{{ $device->network_quality ?? '-' }}</td>
                            <td>{{ $device->queue_size ?? 0 }}</td>
                            <td>{{ $device->last_seen_at ?? '-' }}</td>
                            <td>{{ $device->trust_status ?? 'trusted' }}</td>
                            <td>
                                <button class="btn btn-xs btn-success s384-trust"><i class="fa fa-check"></i> Trust</button>
                                <button class="btn btn-xs btn-danger s384-block"><i class="fa fa-ban"></i> Block</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No terminal heartbeat has been received yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-chart-line text-success"></i> Network & Queue Health</h3><div class="ch-card-subtitle">Live indicators for support and managers.</div></div></div>
            <div class="ch-card-body">
                <div class="pos-soft-box"><strong>Average Latency (6h)</strong><br>{{ $status['monitor']['avg_latency_ms_6h'] ?? 'No samples yet' }} ms</div>
                <div class="pos-soft-box"><strong>Total Devices</strong><br>{{ $status['monitor']['total_devices'] ?? 0 }}</div>
                <div class="pos-soft-box"><strong>Last Checked</strong><br>{{ $status['monitor']['last_checked_at'] ?? '-' }}</div>
                <pre id="s384-console" style="margin-top:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px;max-height:220px;overflow:auto;">Ready.</pre>
            </div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-diagram-project text-info"></i> S384 Protection Layers</h3><div class="ch-card-subtitle">Controls added to make offline/online sync safer in multi-terminal and multi-branch environments.</div></div></div>
    <div class="ch-card-body">
        <div class="row">
            @foreach($controlBlocks as $block)
                <div class="col-md-4"><div class="pos-soft-box"><strong>{{ $block['title'] }}</strong><br>{{ $block['note'] }}</div></div>
            @endforeach
        </div>
    </div>
</div>

<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_s384_monitoring_center.js']) }}?v=s384"></script>
@endsection
