@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'Price Change Details')
@section('pcn_page_subtitle', $change->reference_no . ' — ' . $change->title)
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
@if($change->status === 'draft' && $canEditChanges)
<a href="{{ route('pricechangenew.changes.edit', $change->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit Draft</a>
@endif
@endsection
@section('pcn_content')
@php
    $statusLabel = ucwords(str_replace('_', ' ', $change->status));
    $scopeLabel = $change->application_scope === 'location_price_groups' ? 'Selected location selling-price groups' : 'Business base price (all locations)';
    $canSubmitNow = $change->status === 'draft' && $canSubmitChanges;
    $canApproveNow = $change->status === 'submitted' && $canApproveChanges;
    $canRejectNow = $change->status === 'submitted' && $canRejectChanges;
    $scheduledIsDue = $change->status === 'scheduled' && (!$change->effective_at || !$change->effective_at->isFuture());
    $canApplyNow = (in_array($change->status, ['approved', 'failed', 'partial'], true) || $scheduledIsDue) && $canApplyChanges;
    $canCancelNow = in_array($change->status, ['draft', 'submitted', 'approved', 'scheduled', 'failed'], true) && $canCancelChanges;
@endphp

<div class="pcn-detail-grid">
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-file-text-o text-primary"></i> {{ $change->reference_no }}</h3>
                <div class="ch-card-subtitle">Created {{ optional($change->created_at)->format('d M Y, h:i A') }}</div>
            </div>
            <span class="pcn-status pcn-status-{{ $change->status }}">{{ $statusLabel }}</span>
        </div>
        <div class="ch-card-body">
            <div class="pcn-summary-grid">
                <div class="pcn-summary-item"><span>Title</span><strong>{{ $change->title }}</strong></div>
                <div class="pcn-summary-item"><span>Application Scope</span><strong>{{ $scopeLabel }}</strong></div>
                <div class="pcn-summary-item"><span>Locations</span><strong>{{ $change->scopes->pluck('location_name')->join(', ') ?: '-' }}</strong></div>
                <div class="pcn-summary-item"><span>Effective At</span><strong>{{ $change->effective_at ? $change->effective_at->format('d M Y, h:i A') : 'Immediately after approval/application' }}</strong></div>
                <div class="pcn-summary-item"><span>Stock Rule</span><strong>Apply to all stock</strong></div>
                <div class="pcn-summary-item"><span>Application Attempts</span><strong>{{ number_format((int) $change->application_attempts) }}</strong></div>
            </div>
            @if($change->reason)
                <div class="pcn-reason-box"><span>Reason / Notes</span><p>{{ $change->reason }}</p></div>
            @endif
            @if($change->failure_message)
                <div class="alert alert-danger" style="margin-top:16px;"><i class="fa fa-exclamation-triangle"></i> {{ $change->failure_message }}</div>
            @endif
            @if($change->rejection_reason)
                <div class="alert alert-warning" style="margin-top:16px;"><i class="fa fa-ban"></i> Rejection reason: {{ $change->rejection_reason }}</div>
            @endif
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div><h3 class="ch-card-title"><i class="fa fa-bolt text-warning"></i> Workflow Actions</h3><div class="ch-card-subtitle">Only valid actions for the current status are shown.</div></div>
        </div>
        <div class="ch-card-body pos-actions-list">
            @if($canSubmitNow)
                <a href="#" class="pos-action-tile pcn-workflow-action" data-url="{{ route('pricechangenew.changes.submit', $change->id) }}" data-action="submit" data-message="Submit this price change for approval?">
                    <span class="left"><span class="tile-icon"><i class="fa fa-paper-plane"></i></span>Submit for Approval</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canApproveNow)
                <a href="#" class="pos-action-tile success pcn-workflow-action" data-url="{{ route('pricechangenew.changes.approve', $change->id) }}" data-action="approve" data-message="Approve this price change?">
                    <span class="left"><span class="tile-icon"><i class="fa fa-check"></i></span>Approve</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canRejectNow)
                <a href="#" class="pos-action-tile warning pcn-workflow-action" data-url="{{ route('pricechangenew.changes.reject', $change->id) }}" data-action="reject" data-message="Enter the rejection reason.">
                    <span class="left"><span class="tile-icon"><i class="fa fa-times"></i></span>Reject</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canApplyNow)
                <a href="#" class="pos-action-tile success pcn-workflow-action" data-url="{{ route('pricechangenew.changes.apply', $change->id) }}" data-action="apply" data-message="Apply this price change to live prices? This operation records before and after snapshots.">
                    <span class="left"><span class="tile-icon"><i class="fa fa-play-circle"></i></span>Apply Prices</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if($canCancelNow)
                <a href="#" class="pos-action-tile warning pcn-workflow-action" data-url="{{ route('pricechangenew.changes.cancel', $change->id) }}" data-action="cancel" data-message="Cancel this price change?">
                    <span class="left"><span class="tile-icon"><i class="fa fa-ban"></i></span>Cancel</span><i class="fa fa-angle-right"></i>
                </a>
            @endif
            @if(!$canSubmitNow && !$canApproveNow && !$canRejectNow && !$canApplyNow && !$canCancelNow)
                <div class="pcn-empty-state compact"><span class="pcn-empty-icon"><i class="fa fa-lock"></i></span><strong>No action available</strong><span>The workflow is complete or your role has view-only access.</span></div>
            @endif
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-tags text-primary"></i> Price Lines</h3><div class="ch-card-subtitle">Current snapshot, proposed price and application result for every selected variation.</div></div></div>
    <div class="ch-card-body">
        <div class="table-responsive">
            <table class="table table-bordered pos-standard-table pcn-show-lines">
                <thead><tr><th class="pcn-col-product">Product / Variation</th><th>SKU</th><th class="text-right pcn-num">Tax</th><th class="text-right pcn-num">Stock</th><th class="text-right pcn-num pcn-col-current">Current Purchase<br>Ex / Inc</th><th class="text-right pcn-num pcn-col-current">New Purchase<br>Ex / Inc</th><th class="text-right pcn-num pcn-col-current">Current Selling<br>Ex / Inc</th><th class="text-right pcn-num pcn-col-current">New Selling<br>Ex / Inc</th><th class="text-right pcn-num pcn-col-profit">New Profit %</th><th class="pcn-col-status">Apply Status</th></tr></thead>
                <tbody>
                @foreach($change->lines as $line)
                    <tr>
                        <td><strong>{{ $line->product_name }}</strong><br><span class="text-muted">{{ $line->variation_name }}</span></td>
                        <td>{{ $line->sku ?: '-' }}</td>
                        <td>{{ $line->tax_name ?: 'No tax' }}<br><span class="text-muted">{{ number_format((float) $line->tax_rate, 4) }}%</span></td>
                        <td class="text-right">{{ number_format((float) $line->stock_quantity, 4) }}</td>
                        <td class="text-right">{{ number_format((float) $line->current_purchase_price_ex_tax, 8) }}<br>{{ number_format((float) $line->current_purchase_price_inc_tax, 8) }}</td>
                        <td class="text-right">{{ $line->new_purchase_price_ex_tax !== null ? number_format((float) $line->new_purchase_price_ex_tax, 8) : '-' }}<br>{{ $line->new_purchase_price_inc_tax !== null ? number_format((float) $line->new_purchase_price_inc_tax, 8) : '-' }}</td>
                        <td class="text-right">{{ number_format((float) $line->current_sell_price_ex_tax, 8) }}<br>{{ number_format((float) $line->current_sell_price_inc_tax, 8) }}</td>
                        <td class="text-right"><strong>{{ number_format((float) $line->new_sell_price_ex_tax, 8) }}</strong><br><strong>{{ number_format((float) $line->new_sell_price_inc_tax, 8) }}</strong></td>
                        <td class="text-right">{{ $line->new_profit_percent !== null ? number_format((float) $line->new_profit_percent, 4) : '-' }}</td>
                        <td><span class="pcn-status pcn-status-{{ $line->apply_status ?: 'pending' }}">{{ ucwords(str_replace('_', ' ', $line->apply_status ?: 'Pending')) }}</span>@if($line->apply_message)<div class="pcn-cell-message">{{ $line->apply_message }}</div>@endif</td>
                    </tr>
                    @if($change->application_scope === 'location_price_groups' && $line->scopePrices->isNotEmpty())
                        <tr class="pcn-scope-row"><td colspan="10">
                            <strong>Location price-group snapshots:</strong>
                            @foreach($line->scopePrices as $scopePrice)
                                <span class="pcn-scope-chip">Location #{{ $scopePrice->location_id }} / Group #{{ $scopePrice->price_group_id }}: {{ number_format((float) $scopePrice->current_group_price_inc_tax, 8) }} → {{ number_format((float) $scopePrice->new_group_price_inc_tax, 8) }}</span>
                            @endforeach
                        </td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="pcn-detail-grid">
    <div class="ch-card">
        <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-history text-primary"></i> Audit Trail</h3><div class="ch-card-subtitle">Status transitions and significant edits.</div></div></div>
        <div class="ch-card-body">
            <div class="pcn-timeline">
                @forelse($change->audits as $audit)
                    <div class="pcn-timeline-item">
                        <span class="pcn-timeline-dot"></span>
                        <div><strong>{{ ucwords(str_replace('_', ' ', $audit->action)) }}</strong><span>{{ $audit->from_status ? ucwords($audit->from_status) . ' → ' : '' }}{{ $audit->to_status ? ucwords($audit->to_status) : '' }}</span><small>{{ optional($audit->created_at)->format('d M Y, h:i A') }} @if($audit->user_id) by {{ $userNames[$audit->user_id] ?? ('User #' . $audit->user_id) }} @endif</small></div>
                    </div>
                @empty
                    <div class="text-muted">No audit entries recorded.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-exchange text-primary"></i> Application Attempts</h3><div class="ch-card-subtitle">Each attempt records successful and failed price lines.</div></div></div>
        <div class="ch-card-body">
            @forelse($change->applications as $application)
                <div class="pcn-application-item">
                    <div><strong>Attempt #{{ $application->id }}</strong><span class="pcn-status pcn-status-{{ $application->status }}">{{ ucwords($application->status) }}</span></div>
                    <span>{{ ucwords($application->trigger_type) }} · Success {{ $application->success_count }} · Failed {{ $application->failed_count }}</span>
                    <small>{{ optional($application->started_at)->format('d M Y, h:i A') }} @if($application->completed_at) — completed {{ $application->completed_at->format('h:i A') }} @endif</small>
                    @if($application->message)<p>{{ $application->message }}</p>@endif
                </div>
            @empty
                <div class="pcn-empty-state compact"><span class="pcn-empty-icon"><i class="fa fa-clock-o"></i></span><strong>No application attempts</strong><span>Prices remain unchanged until an approved record is applied.</span></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
