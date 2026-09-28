@extends('managementreport::layouts.master', [
    'pageTitle' => __('managementreport::lang.module_name'),
    'pageSubtitle' => __('managementreport::lang.dashboard_subtitle')
])

@section('page_actions')
<a href="{{ route('managementreport.dashboard') }}" class="mgmt-btn mgmt-btn-default"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('managementreport.daily.index') }}" class="mgmt-btn mgmt-btn-primary"><i class="fa fa-plus"></i> New Report</a>
<a href="{{ route('managementreport.saved.index') }}" class="mgmt-btn mgmt-btn-success"><i class="fa fa-archive"></i> Saved Reports</a>
@endsection

@section('managementreport_content')
@php
    $cards = [
        ['label' => 'Reports Today', 'value' => number_format($stats['reports_today']), 'icon' => 'fa-file-text-o', 'hint' => 'Generated today', 'tone' => '', 'url' => route('managementreport.saved.index')],
        ['label' => 'This Month', 'value' => number_format($stats['reports_month']), 'icon' => 'fa-calendar', 'hint' => 'Monthly reports', 'tone' => 'success', 'url' => route('managementreport.saved.index')],
        ['label' => 'Shared Reports', 'value' => number_format($stats['shared_reports']), 'icon' => 'fa-paper-plane', 'hint' => 'Delivery records', 'tone' => 'warning', 'url' => route('managementreport.shares.index')],
        ['label' => 'Active Links', 'value' => number_format($stats['active_links']), 'icon' => 'fa-link', 'hint' => 'Public report links', 'tone' => 'purple', 'url' => route('managementreport.shares.index')],
    ];
@endphp

<div class="mgmt-kpi-grid mgmt-standard-grid">
    @foreach($cards as $card)
        <a href="{{ $card['url'] }}" class="mgmt-kpi-link">
            <div class="mgmt-kpi {{ $card['tone'] }}">
                <div class="mgmt-kpi-top">
                    <span class="mgmt-kpi-icon"><i class="fa {{ $card['icon'] }}"></i></span>
                    <span class="mgmt-kpi-label">{{ $card['label'] }}</span>
                </div>
                <strong class="mgmt-kpi-value">{{ $card['value'] }}</strong>
                <div class="mgmt-kpi-hint">{{ $card['hint'] }} <span class="mgmt-kpi-drill">Open <i class="fa fa-angle-right"></i></span></div>
                <div class="mgmt-kpi-spark"></div>
            </div>
        </a>
    @endforeach
</div>

<div class="mgmt-dashboard-panels">
    <div class="mgmt-panel mgmt-dashboard-table-panel">
        <div class="mgmt-panel-header">
            <div>
                <h3><i class="fa fa-list-alt text-primary"></i> Recent Reports</h3>
                <p>Latest generated management report snapshots.</p>
            </div>
            <a href="{{ route('managementreport.saved.index') }}" class="mgmt-panel-link">View all <i class="fa fa-angle-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="table mgmt-table mgmt-standard-table">
                <thead><tr><th>Report</th><th>Period</th><th>Generated</th><th>Review</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                @forelse($recent as $run)
                    <tr>
                        <td><strong>{{ $run->report_title }}</strong><small class="mgmt-muted">{{ $run->uuid }}</small></td>
                        <td>{{ \Carbon\Carbon::parse($run->period_start)->format('d M Y') }}@if($run->period_start !== $run->period_end) - {{ \Carbon\Carbon::parse($run->period_end)->format('d M Y') }}@endif</td>
                        <td>{{ optional($run->generated_at)->format('d M Y, h:i A') }}</td>
                        <td><span class="mgmt-status mgmt-status-{{ $run->review_status }}">{{ ucfirst($run->review_status) }}</span></td>
                        <td class="text-right"><a class="mgmt-btn mgmt-btn-sm" href="{{ route('managementreport.saved.show', $run) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="mgmt-empty">No management reports have been generated yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mgmt-panel">
        <div class="mgmt-panel-header">
            <div>
                <h3><i class="fa fa-bolt text-primary"></i> Quick Actions</h3>
                <p>Common Management Report operations.</p>
            </div>
        </div>
        <div class="mgmt-panel-body mgmt-actions-list">
            <a class="mgmt-action-tile" href="{{ route('managementreport.daily.index') }}"><span class="mgmt-action-left"><span class="mgmt-tile-icon"><i class="fa fa-file-text-o"></i></span>Daily Management Report</span><i class="fa fa-angle-right"></i></a>
            <a class="mgmt-action-tile success" href="{{ route('managementreport.saved.index') }}"><span class="mgmt-action-left"><span class="mgmt-tile-icon"><i class="fa fa-archive"></i></span>Saved Reports</span><i class="fa fa-angle-right"></i></a>
            <a class="mgmt-action-tile warning" href="{{ route('managementreport.shares.index') }}"><span class="mgmt-action-left"><span class="mgmt-tile-icon"><i class="fa fa-paper-plane"></i></span>Delivery History</span><i class="fa fa-angle-right"></i></a>
            <a class="mgmt-action-tile purple" href="{{ route('managementreport.settings.index') }}"><span class="mgmt-action-left"><span class="mgmt-tile-icon"><i class="fa fa-cog"></i></span>Settings</span><i class="fa fa-angle-right"></i></a>
        </div>
    </div>
</div>

<div class="mgmt-panel">
    <div class="mgmt-panel-header">
        <div>
            <h3><i class="fa fa-bolt text-warning"></i> Management Operations</h3>
            <p>Start the most frequently used report tasks.</p>
        </div>
    </div>
    <div class="mgmt-panel-body mgmt-quick-operations">
        <a href="{{ route('managementreport.daily.index') }}" class="mgmt-quick-operation"><span class="mgmt-operation-icon"><i class="fa fa-plus"></i></span><span><strong>New Report</strong><small>Generate daily report</small></span></a>
        <a href="{{ route('managementreport.saved.index') }}" class="mgmt-quick-operation"><span class="mgmt-operation-icon"><i class="fa fa-folder-open-o"></i></span><span><strong>Open Reports</strong><small>View saved snapshots</small></span></a>
        <a href="{{ route('managementreport.shares.index') }}" class="mgmt-quick-operation"><span class="mgmt-operation-icon"><i class="fa fa-send-o"></i></span><span><strong>Delivery History</strong><small>Audit shared reports</small></span></a>
        <a href="{{ route('managementreport.settings.index') }}" class="mgmt-quick-operation"><span class="mgmt-operation-icon"><i class="fa fa-cog"></i></span><span><strong>Settings</strong><small>Configure defaults</small></span></a>
    </div>
</div>
@endsection
