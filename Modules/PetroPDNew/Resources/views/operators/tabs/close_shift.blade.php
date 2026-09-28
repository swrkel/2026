@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.shifts.view')<a class="pdn-btn primary" href="{{ route('pumper-dashboard-new.admin.shifts.index') }}"><i class="fa fa-external-link"></i> Open Pumper Shift Control</a>@endcan
</div>
<div class="pdn-alert warning">
    Shift closing is performed in Pumper Dashboard-New. This control page shows the current state and provides direct access to the related shift without duplicating or altering the operator source record.
</div>
<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card warning"><span>Open</span><strong>{{ $totals['open'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card warning"><span>Closing</span><strong>{{ $totals['closing'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card success"><span>Closed</span><strong>{{ $totals['closed'] ?? 0 }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Action</th><th>Shift No.</th><th>Operator</th><th>Opened</th><th>Closed</th><th>Status</th><th>Integration</th><th class="amount">Expected</th><th class="amount">Payments</th><th class="amount">Shortage</th><th class="amount">Excess</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td class="pdn-inline">@if(Route::has('pumper-dashboard-new.admin.shifts.show'))<a class="pdn-btn small light" href="{{ route('pumper-dashboard-new.admin.shifts.show',$row->id) }}">Open Shift</a>@endif @if($row->status === 'closed' && Route::has('petro-pd-new.sources.show'))<a class="pdn-btn small primary" href="{{ route('petro-pd-new.sources.show',$row->id) }}">PD Source</a>@endif</td><td><strong>{{ $row->shift_number }}</strong></td><td>{{ $row->operator_name }}</td><td>{{ $row->opened_at ?: '—' }}</td><td>{{ $row->closed_at ?: '—' }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td><span class="pdn-badge {{ $row->integration_status }}">{{ $row->integration_status }}</span></td><td class="amount">{{ number_format((float)$row->expected_total,4) }}</td><td class="amount">{{ number_format((float)$row->payments_total,4) }}</td><td class="amount">{{ number_format((float)$row->shortage_amount,4) }}</td><td class="amount">{{ number_format((float)$row->excess_amount,4) }}</td></tr>
@empty<tr><td colspan="11" class="pdn-empty">No shifts were found.</td></tr>@endforelse
</tbody></table></div></div>
