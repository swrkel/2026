@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.reports.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.reports.index', ['report' => 'unloads']) }}"><i class="fa fa-external-link"></i> Open Unload Stock Report</a>@endcan
</div>
<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card"><span>Total Quantity</span><strong>{{ number_format((float)($totals['quantity'] ?? 0),3) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Total Amount</span><strong>{{ number_format((float)($totals['amount'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Unload Records</span><strong>{{ $rows->count() }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Receipt No.</th><th>Date / Time</th><th>Shift</th><th>Operator</th><th>Bill No.</th><th>Supplier Reference</th><th>Store</th><th class="amount">Quantity</th><th class="amount">Amount</th><th>Status</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><strong>{{ $row->receipt_number }}</strong></td><td>{{ $row->unloaded_at }}</td><td>{{ $row->shift_number }}</td><td>{{ $row->operator_name }}</td><td>{{ $row->bill_number ?: '—' }}</td><td>{{ $row->supplier_reference ?: '—' }}</td><td>{{ $row->store_id ? 'Store #'.$row->store_id : '—' }}</td><td class="amount">{{ number_format((float)$row->total_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->total_amount,4) }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td>{{ $row->note ?: '—' }}</td></tr>
@empty<tr><td colspan="11" class="pdn-empty">No unload-stock records were found.</td></tr>@endforelse
</tbody></table></div></div>
