@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.reports.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.reports.index', ['report' => 'meters']) }}"><i class="fa fa-external-link"></i> Open Meter Sales Report</a>@endcan
</div>
<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card"><span>Meter Amount</span><strong>{{ number_format((float)($totals['meter'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Payments</span><strong>{{ number_format((float)($totals['payments'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card {{ ((float)($totals['variance'] ?? 0)) == 0.0 ? 'success' : 'danger' }}"><span>Variance</span><strong>{{ number_format((float)($totals['variance'] ?? 0),4) }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Shift</th><th>Operator</th><th>Pump</th><th>Status</th><th class="amount">Opening Meter</th><th class="amount">Current Meter</th><th class="amount">Closing Meter</th><th class="amount">Testing Qty</th><th class="amount">Sold Qty</th><th class="amount">Unit Price</th><th class="amount">Meter Amount</th><th class="amount">Allocated Payments</th><th class="amount">Variance</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->shift_number }}</td><td>{{ $row->operator_name }}</td><td>Pump #{{ $row->pump_id }}</td><td><span class="pdn-badge {{ $row->pump_status }}">{{ $row->pump_status }}</span></td><td class="amount">{{ number_format((float)$row->opening_meter,3) }}</td><td class="amount">{{ number_format((float)$row->current_meter,3) }}</td><td class="amount">{{ $row->closing_meter !== null ? number_format((float)$row->closing_meter,3) : '—' }}</td><td class="amount">{{ number_format((float)$row->testing_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->sold_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->unit_price,4) }}</td><td class="amount">{{ number_format((float)$row->meter_amount,4) }}</td><td class="amount">{{ number_format((float)$row->payment_amount,4) }}</td><td class="amount">{{ number_format((float)$row->variance_amount,4) }}</td></tr>
@empty<tr><td colspan="13" class="pdn-empty">No meter-with-payment records were found.</td></tr>@endforelse
</tbody></table></div></div>
