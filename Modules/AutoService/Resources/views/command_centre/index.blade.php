@extends('layouts.app')

@section('title', $page_title ?? __('Workshop Command Centre'))

@section('content')
<section class="content-header autoservice-page-header as-command-header">
    <div>
        <p class="autoservice-eyebrow">AUTO SERVICE</p>
        <h1>Workshop Command Centre</h1>
        <p class="autoservice-subtitle">Real-time workshop board for reception, inspection, approval, parts, repair, QC, billing and delivery.</p>
    </div>
    <div class="autoservice-header-actions">
        <a href="{{ route('autoservice.jobs.create') }}" class="btn autoservice-btn autoservice-btn-primary"><i class="fa fa-plus"></i> New Job</a>
        <a href="{{ route('autoservice.service_flow.index') }}" class="btn autoservice-btn autoservice-btn-green"><i class="fa fa-random"></i> Service Flow</a>
        <a href="{{ route('autoservice.billing_delivery.index') }}" class="btn autoservice-btn autoservice-btn-light"><i class="fa fa-credit-card"></i> Billing</a>
    </div>
</section>

<section class="content autoservice-command-centre">
    @php
        $tiles = $tiles ?? [];
        $kpis = $kpis ?? [];
        $attention = $attention ?? [];
        $queues = $queues ?? [];
        $workload = $workload ?? [];
        $quickLinks = $quick_links ?? [];
        $flowTiles = [
            ['key' => 'vehicles_waiting', 'title' => 'Vehicles Waiting', 'icon' => 'fa-car', 'class' => 'blue'],
            ['key' => 'under_inspection', 'title' => 'Under Inspection', 'icon' => 'fa-search', 'class' => 'purple'],
            ['key' => 'waiting_approval', 'title' => 'Waiting Approval', 'icon' => 'fa-clock-o', 'class' => 'orange'],
            ['key' => 'waiting_parts', 'title' => 'Waiting Parts', 'icon' => 'fa-cubes', 'class' => 'red'],
            ['key' => 'under_repair', 'title' => 'Under Repair', 'icon' => 'fa-wrench', 'class' => 'green'],
            ['key' => 'quality_control', 'title' => 'Quality Control', 'icon' => 'fa-check-square-o', 'class' => 'teal'],
            ['key' => 'ready_for_delivery', 'title' => 'Ready Delivery', 'icon' => 'fa-flag-checkered', 'class' => 'indigo'],
            ['key' => 'delivered_today', 'title' => 'Delivered Today', 'icon' => 'fa-truck', 'class' => 'gray'],
        ];
    @endphp

    <div class="as-command-toolbar">
        <form method="GET" action="{{ route('autoservice.command_centre.index') }}" class="as-command-filter">
            <label>Delayed if no update for</label>
            <select name="delay_hours" class="form-control input-sm" onchange="this.form.submit()">
                @foreach([6,12,24,48,72] as $hour)
                    <option value="{{ $hour }}" {{ (int)($filters['delay_hours'] ?? 24) === $hour ? 'selected' : '' }}>{{ $hour }} hours</option>
                @endforeach
            </select>
        </form>
        <div class="as-live-stamp">
            <i class="fa fa-refresh"></i> Last updated: <span id="as-live-time">{{ $filters['as_of'] ?? now()->format('Y-m-d H:i:s') }}</span>
        </div>
    </div>

    <div class="autoservice-kpi-grid as-flow-grid">
        @foreach($flowTiles as $tile)
            <a href="{{ route('autoservice.service_flow.index') }}" class="autoservice-kpi-card {{ $tile['class'] }} as-flow-card">
                <div class="autoservice-kpi-icon"><i class="fa {{ $tile['icon'] }}"></i></div>
                <span>{{ $tile['title'] }}</span>
                <strong data-as-tile="{{ $tile['key'] }}">{{ number_format($tiles[$tile['key']] ?? 0) }}</strong>
                <small>Live workshop count</small>
            </a>
        @endforeach
    </div>

    <div class="row autoservice-main-grid">
        <div class="col-md-8">
            <div class="autoservice-panel as-flow-panel">
                <div class="autoservice-panel-header">
                    <div>
                        <h3>Workshop Flow Board</h3>
                        <p>End-to-end operating position from reception to delivery.</p>
                    </div>
                    <span class="autoservice-status-pill">Live</span>
                </div>
                <div class="as-progress-flow">
                    @foreach($flowTiles as $tile)
                        <div class="as-progress-step {{ $tile['class'] }}">
                            <i class="fa {{ $tile['icon'] }}"></i>
                            <span>{{ $tile['title'] }}</span>
                            <strong>{{ number_format($tiles[$tile['key']] ?? 0) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="autoservice-panel">
                <div class="autoservice-panel-header">
                    <div>
                        <h3>Delayed Jobs</h3>
                        <p>Jobs needing management attention based on promised date or no recent update.</p>
                    </div>
                    <span class="as-danger-pill">{{ number_format($attention['delayed_jobs'] ?? 0) }} delayed</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-condensed as-command-table">
                        <thead>
                            <tr>
                                <th>Job No</th>
                                <th>Customer / Vehicle</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($queues['delayed_jobs'] ?? collect()) as $job)
                                <tr>
                                    <td><strong>{{ $job->job_no ?? ('JOB-'.$job->id) }}</strong></td>
                                    <td>{{ $job->customer_name ?? $job->vehicle_no ?? $job->registration_no ?? 'N/A' }}</td>
                                    <td><span class="label label-warning">{{ ucwords(str_replace('_', ' ', $job->status ?? $job->workflow_stage ?? 'open')) }}</span></td>
                                    <td>{{ $job->updated_at ?? $job->created_at ?? '-' }}</td>
                                    <td class="text-right"><a class="btn btn-xs btn-primary" href="{{ route('autoservice.jobs.show', $job->id) }}">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No delayed jobs found for the selected threshold.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="autoservice-panel">
                        <div class="autoservice-panel-header compact"><div><h3>Technician Workload</h3><p>Active jobs by technician.</p></div></div>
                        <div class="as-mini-list">
                            @forelse(($workload['technicians'] ?? collect()) as $tech)
                                <div class="as-mini-row"><span>Technician #{{ $tech->mechanic_id ?? 'N/A' }}</span><strong>{{ number_format($tech->active_jobs ?? 0) }}</strong></div>
                            @empty
                                <div class="as-empty">No active technician workload.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="autoservice-panel">
                        <div class="autoservice-panel-header compact"><div><h3>Bay Occupancy</h3><p>Currently occupied bays.</p></div></div>
                        <div class="as-mini-list">
                            @forelse(($workload['bays'] ?? collect()) as $bay)
                                <div class="as-mini-row"><span>Bay #{{ $bay->bay_id ?? $bay->id }}</span><strong>{{ $bay->job_id ? 'Job '.$bay->job_id : 'Occupied' }}</strong></div>
                            @empty
                                <div class="as-empty">No occupied bays.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="autoservice-panel as-attention-panel">
                <div class="autoservice-panel-header compact"><div><h3>Today Attention</h3><p>Priority counters.</p></div></div>
                <div class="autoservice-priority-list">
                    <a href="{{ route('autoservice.service_flow.index') }}"><i class="fa fa-exclamation-circle"></i><span>Delayed jobs</span><strong>{{ number_format($attention['delayed_jobs'] ?? 0) }}</strong></a>
                    <a href="{{ route('autoservice.approvals.index') }}"><i class="fa fa-clock-o"></i><span>Waiting customer approval</span><strong>{{ number_format($attention['waiting_approval'] ?? 0) }}</strong></a>
                    <a href="{{ route('autoservice.parts_labour.index') }}"><i class="fa fa-cubes"></i><span>Waiting parts</span><strong>{{ number_format($attention['waiting_parts'] ?? 0) }}</strong></a>
                    <a href="{{ route('autoservice.quality_control.index') }}"><i class="fa fa-check-square-o"></i><span>QC pending</span><strong>{{ number_format($attention['qc_pending'] ?? 0) }}</strong></a>
                    <a href="{{ route('autoservice.billing_delivery.index') }}"><i class="fa fa-credit-card"></i><span>Invoice pending</span><strong>{{ number_format($attention['invoice_pending'] ?? 0) }}</strong></a>
                </div>
            </div>

            <div class="autoservice-panel">
                <div class="autoservice-panel-header compact"><div><h3>Management KPIs</h3><p>Daily operating snapshot.</p></div></div>
                <div class="as-kpi-stack">
                    <div><span>Jobs Today</span><strong>{{ number_format($kpis['jobs_today'] ?? 0) }}</strong></div>
                    <div><span>Open Jobs</span><strong>{{ number_format($kpis['open_jobs'] ?? 0) }}</strong></div>
                    <div><span>Occupied Bays</span><strong>{{ number_format($kpis['bay_occupied'] ?? 0) }}</strong></div>
                    <div><span>Pending Invoices</span><strong>{{ number_format($kpis['pending_invoices'] ?? 0) }}</strong></div>
                    <div><span>Today Revenue</span><strong class="display_currency" data-currency_symbol="true">{{ $kpis['revenue_today'] ?? 0 }}</strong></div>
                </div>
            </div>

            <div class="autoservice-panel">
                <div class="autoservice-panel-header compact"><div><h3>Quick Actions</h3><p>Operational shortcuts.</p></div></div>
                <div class="autoservice-shortcut-list">
                    @foreach($quickLinks as $link)
                        <a href="{{ $link['url'] }}"><i class="fa {{ $link['icon'] }}"></i> {{ $link['title'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@push('javascript')
<script>
(function () {
    var liveUrl = "{{ route('autoservice.command_centre.live', ['delay_hours' => $filters['delay_hours'] ?? 24]) }}";
    function refreshCommandCentre() {
        fetch(liveUrl, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.tiles) return;
                Object.keys(payload.tiles).forEach(function (key) {
                    document.querySelectorAll('[data-as-tile="' + key + '"]').forEach(function (el) {
                        el.textContent = Number(payload.tiles[key] || 0).toLocaleString();
                    });
                });
                if (payload.filters && payload.filters.as_of && document.getElementById('as-live-time')) {
                    document.getElementById('as-live-time').textContent = payload.filters.as_of;
                }
            })
            .catch(function () {});
    }
    setInterval(refreshCommandCentre, 60000);
})();
</script>
@endpush
@endsection
