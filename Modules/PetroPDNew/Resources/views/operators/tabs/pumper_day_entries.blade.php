@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.reports.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.reports.index', ['report' => 'day-entries']) }}"><i class="fa fa-external-link"></i> Open Pumper Day Entries</a>@endcan
</div>
<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card"><span>Total Quantity</span><strong>{{ number_format((float) ($totals['quantity'] ?? 0), 3) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Total Amount</span><strong>{{ number_format((float) ($totals['amount'] ?? 0), 4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Entries</span><strong>{{ $rows->count() }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date / Time</th><th>Operator</th><th>Shift</th><th>Pump</th><th>Entry Type</th><th>Reference</th><th class="amount">Quantity</th><th class="amount">Amount</th><th>Meter Range</th><th>Testing Qty</th><th>Status</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->entry_at }}</td><td>{{ $row->operator_name }}</td><td>{{ $row->shift_number }}</td><td>{{ $row->pump_id ? 'Pump #'.$row->pump_id : '—' }}</td><td>{{ ucfirst($row->entry_type) }}</td><td>{{ $row->reference_no ?: '—' }}</td><td class="amount">{{ number_format((float) $row->quantity,3) }}</td><td class="amount">{{ number_format((float) $row->amount,4) }}</td><td>{{ $row->starting_meter !== null ? number_format((float)$row->starting_meter,3) : '—' }} / {{ $row->closing_meter !== null ? number_format((float)$row->closing_meter,3) : '—' }}</td><td>{{ number_format((float)$row->testing_quantity,3) }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td>{{ $row->note ?: '—' }}</td></tr>
@empty<tr><td colspan="12" class="pdn-empty">No pumper day entries were found.</td></tr>@endforelse
</tbody></table></div></div>
