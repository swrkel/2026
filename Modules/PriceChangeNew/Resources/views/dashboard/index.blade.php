@extends('pricechangenew::layouts.app')

@section('pcn_page_title', 'Price Change Dashboard')
@section('pcn_page_subtitle', 'Pricing workflow, approvals and application activity for the logged-in business.')

@section('pcn_page_actions')
    @if($canCreateChanges)
        <a href="{{ route('pricechangenew.changes.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Price Change</a>
    @endif
    @if($canViewChanges)
        <a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
    @endif
    @if($canViewApprovals)
        <a href="{{ route('pricechangenew.approvals.index') }}" class="btn btn-warning btn-sm"><i class="fa fa-check-square-o"></i> Approval Queue</a>
    @endif
@endsection

@section('pcn_content')
@php
    $cards = [
        [
            'label' => 'Drafts',
            'value' => (int) ($counts['draft'] ?? 0),
            'icon' => 'fa-pencil-square-o',
            'sub' => 'Price changes being prepared',
            'tone' => '',
            'url' => $canViewChanges ? route('pricechangenew.changes.index', ['status' => 'draft']) : '#',
        ],
        [
            'label' => 'Awaiting Approval',
            'value' => (int) ($counts['submitted'] ?? 0),
            'icon' => 'fa-paper-plane-o',
            'sub' => 'Submitted for review',
            'tone' => 'warning',
            'url' => $canViewApprovals ? route('pricechangenew.approvals.index') : ($canViewChanges ? route('pricechangenew.changes.index', ['status' => 'submitted']) : '#'),
        ],
        [
            'label' => 'Approved / Scheduled',
            'value' => (int) ($counts['approved'] ?? 0) + (int) ($counts['scheduled'] ?? 0),
            'icon' => 'fa-check-circle-o',
            'sub' => 'Ready for controlled application',
            'tone' => 'success',
            'url' => $canViewChanges ? route('pricechangenew.changes.index', ['status' => 'approved']) : '#',
        ],
        [
            'label' => 'Applied',
            'value' => (int) ($counts['applied'] ?? 0) + (int) ($counts['partial'] ?? 0),
            'icon' => 'fa-exchange',
            'sub' => 'Completed or partly completed',
            'tone' => 'purple',
            'url' => $canViewReports ? route('pricechangenew.reports.history') : ($canViewChanges ? route('pricechangenew.changes.index', ['status' => 'applied']) : '#'),
        ],
    ];
@endphp

<div class="ch-kpi-grid ch-standard-grid">
    @foreach($cards as $card)
        <a href="{{ $card['url'] }}" class="ch-kpi-link">
            <div class="ch-kpi {{ $card['tone'] }}">
                <div class="ch-kpi-top">
                    <div class="ch-icon"><i class="fa {{ $card['icon'] }}"></i></div>
                    <div class="label-text">{{ $card['label'] }}</div>
                </div>
                <div class="value">{{ number_format($card['value']) }}</div>
                <div class="hint">{{ $card['sub'] }} <span class="ch-drill">Open <i class="fa fa-angle-right"></i></span></div>
                <div class="spark"></div>
            </div>
        </a>
    @endforeach
</div>

<div class="pos-dashboard-panels">
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-history text-primary"></i> Recent Price Changes</h3>
                <div class="ch-card-subtitle">Latest draft, approval and application activity.</div>
            </div>
            @if($canViewChanges)
                <a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-default btn-sm"><i class="fa fa-list"></i> View All</a>
            @endif
        </div>
        <div class="ch-card-body">
            <div class="table-responsive">
                <table class="table table-bordered pos-standard-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Locations</th>
                            <th class="text-center">Lines</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentChanges as $change)
                        <tr>
                            <td>
                                @if($canViewChanges)
                                    <a href="{{ route('pricechangenew.changes.show', $change->id) }}"><strong>{{ $change->reference_no }}</strong></a>
                                @else
                                    <strong>{{ $change->reference_no }}</strong>
                                @endif
                            </td>
                            <td>{{ $change->title }}</td>
                            <td>{{ $change->scopes->pluck('location_name')->join(', ') ?: '-' }}</td>
                            <td class="text-center">{{ $change->lines_count }}</td>
                            <td><span class="pcn-status pcn-status-{{ $change->status }}">{{ ucwords(str_replace('_', ' ', $change->status)) }}</span></td>
                            <td>{{ optional($change->created_at)->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted pcn-empty-cell">No price changes have been created yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-bolt text-primary"></i> Quick Actions</h3>
                <div class="ch-card-subtitle">Common price-control operations.</div>
            </div>
        </div>
        <div class="ch-card-body pos-actions-list">
            @if($canCreateChanges)
                <a href="{{ route('pricechangenew.changes.create') }}" class="pos-action-tile">
                    <span class="left"><span class="tile-icon"><i class="fa fa-plus"></i></span>Add Price Change</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canViewApprovals)
                <a href="{{ route('pricechangenew.approvals.index') }}" class="pos-action-tile warning">
                    <span class="left"><span class="tile-icon"><i class="fa fa-check-square-o"></i></span>Approval Queue</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canViewReports)
                <a href="{{ route('pricechangenew.reports.history') }}" class="pos-action-tile success">
                    <span class="left"><span class="tile-icon"><i class="fa fa-file-text-o"></i></span>Application History</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canManageSettings)
                <a href="{{ route('pricechangenew.settings.index') }}" class="pos-action-tile">
                    <span class="left"><span class="tile-icon"><i class="fa fa-cog"></i></span>Settings</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-bolt text-warning"></i> Quick Operations</h3>
            <div class="ch-card-subtitle">Continue the next required step without searching through menus.</div>
        </div>
    </div>
    <div class="ch-card-body pos-quick-operations">
        @if($canCreateChanges)
            <a href="{{ route('pricechangenew.changes.create') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-plus"></i></span><span><strong>New Draft</strong><span>Prepare a price revision</span></span></a>
        @endif
        @if($canViewChanges)
            <a href="{{ route('pricechangenew.changes.index', ['status' => 'draft']) }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-pencil-square-o"></i></span><span><strong>Drafts</strong><span>Complete pending drafts</span></span></a>
        @endif
        @if($canViewApprovals)
            <a href="{{ route('pricechangenew.approvals.index') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-gavel"></i></span><span><strong>Review</strong><span>Approve or reject</span></span></a>
        @endif
        @if($canApplyChanges)
            <a href="{{ route('pricechangenew.changes.index', ['status' => 'approved']) }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-play-circle"></i></span><span><strong>Apply Prices</strong><span>{{ number_format($dueCount) }} currently due</span></span></a>
        @endif
        @if($canViewReports)
            <a href="{{ route('pricechangenew.reports.history') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-bar-chart"></i></span><span><strong>Reports</strong><span>View price history</span></span></a>
        @endif
        @if($canManageSettings)
            <a href="{{ route('pricechangenew.settings.index') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-cogs"></i></span><span><strong>Settings</strong><span>Workflow controls</span></span></a>
        @endif
    </div>
</div>
@endsection
