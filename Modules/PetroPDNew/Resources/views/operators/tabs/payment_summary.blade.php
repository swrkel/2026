@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
@php($byType = data_get($totals, 'by_type', collect()))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.reports.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.reports.index', ['report' => 'payments']) }}"><i class="fa fa-external-link"></i> Open Pumper Payment Summary</a>@endcan
</div>
<div class="pdn-operator-summary-grid wide">
    <div class="pdn-operator-summary-card"><span>Total Payments</span><strong>{{ number_format((float)($totals['amount'] ?? 0),4) }}</strong></div>
    @foreach(['cash','card','cheque','credit','other'] as $type)
        <div class="pdn-operator-summary-card"><span>{{ ucfirst($type) }}</span><strong>{{ number_format((float) data_get($byType,$type,0),4) }}</strong></div>
    @endforeach
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Payment No.</th><th>Date / Time</th><th>Shift</th><th>Operator</th><th>Type</th><th>Reference</th><th class="amount">Gross</th><th class="amount">Discount</th><th class="amount">Amount</th><th>Status</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><strong>{{ $row->payment_number }}</strong></td><td>{{ $row->transaction_at }}</td><td>{{ $row->shift_number }}</td><td>{{ $row->operator_name }}</td><td>{{ ucfirst($row->payment_type) }}</td><td>{{ $row->reference_no ?: '—' }}</td><td class="amount">{{ number_format((float)$row->gross_amount,4) }}</td><td class="amount">{{ number_format((float)$row->discount_amount,4) }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td>{{ $row->note ?: '—' }}</td></tr>
@empty<tr><td colspan="11" class="pdn-empty">No payment summary records were found.</td></tr>@endforelse
</tbody><tfoot><tr><th colspan="8">Visible Total</th><th class="amount">{{ number_format((float)($totals['amount'] ?? 0),4) }}</th><th colspan="2"></th></tr></tfoot></table></div></div>
