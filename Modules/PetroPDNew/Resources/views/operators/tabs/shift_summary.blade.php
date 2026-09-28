@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.shifts.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.shifts.index') }}"><i class="fa fa-external-link"></i> Manage Pumper Shifts</a>@endcan
</div>
<div class="pdn-operator-summary-grid wide">
    <div class="pdn-operator-summary-card"><span>Meter Sales</span><strong>{{ number_format((float)($totals['meter_sales'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Other Sales</span><strong>{{ number_format((float)($totals['other_sales'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Payments</span><strong>{{ number_format((float)($totals['payments'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Expected</span><strong>{{ number_format((float)($totals['expected'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card danger"><span>Shortage</span><strong>{{ number_format((float)($totals['shortage'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card success"><span>Excess</span><strong>{{ number_format((float)($totals['excess'] ?? 0),4) }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Action</th><th>Shift No.</th><th>Operator</th><th>Opened</th><th>Closed</th><th>Status</th><th class="amount">Meter Sales</th><th class="amount">Other Sales</th><th class="amount">Payments</th><th class="amount">Expected</th><th class="amount">Shortage</th><th class="amount">Excess</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>@if(Route::has('pumper-dashboard-new.admin.shifts.show'))<a class="pdn-btn small light" href="{{ route('pumper-dashboard-new.admin.shifts.show',$row->id) }}">View Shift</a>@else—@endif</td><td><strong>{{ $row->shift_number }}</strong></td><td>{{ $row->operator_name }}</td><td>{{ $row->opened_at ?: '—' }}</td><td>{{ $row->closed_at ?: '—' }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td class="amount">{{ number_format((float)$row->meter_sales_total,4) }}</td><td class="amount">{{ number_format((float)$row->other_sales_total,4) }}</td><td class="amount">{{ number_format((float)$row->payments_total,4) }}</td><td class="amount">{{ number_format((float)$row->expected_total,4) }}</td><td class="amount">{{ number_format((float)$row->shortage_amount,4) }}</td><td class="amount">{{ number_format((float)$row->excess_amount,4) }}</td></tr>
@empty<tr><td colspan="12" class="pdn-empty">No shift summary records were found.</td></tr>@endforelse
</tbody></table></div></div>
