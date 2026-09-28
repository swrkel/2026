@php($shortages = data_get($workspaceData, 'shortages', collect()))
@php($commissions = data_get($workspaceData, 'commissions', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.reconciliation.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.reconciliation.index') }}"><i class="fa fa-external-link"></i> Manage Recoveries / Commissions</a>@endcan
</div>
<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card danger"><span>Shortage Recoveries</span><strong>{{ number_format((float) ($totals['shortage'] ?? 0), 4) }}</strong></div>
    <div class="pdn-operator-summary-card success"><span>Excess Commissions</span><strong>{{ number_format((float) ($totals['commission'] ?? 0), 4) }}</strong></div>
</div>
<div class="pdn-grid two">
    <div class="pdn-card">
        <h3><i class="fa fa-minus-circle"></i> Shortage Recoveries</h3>
        <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date</th><th>Operator</th><th>Shift</th><th>Recovery No.</th><th>Method</th><th class="amount">Amount</th><th>Status</th></tr></thead><tbody>
        @forelse($shortages as $row)<tr><td>{{ $row->transaction_date }}</td><td>{{ $row->operator_name }}</td><td>{{ $row->shift_number ?: '—' }}</td><td>{{ $row->reference_number }}</td><td>{{ ucfirst($row->payment_method) }}</td><td class="amount">{{ number_format((float) $row->amount,4) }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td></tr>
        @empty<tr><td colspan="7" class="pdn-empty">No shortage recovery records.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="pdn-card">
        <h3><i class="fa fa-plus-circle"></i> Excess Commissions</h3>
        <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date</th><th>Operator</th><th>Shift</th><th>Commission No.</th><th>Type / Rate</th><th class="amount">Base Excess</th><th class="amount">Commission</th><th>Status</th></tr></thead><tbody>
        @forelse($commissions as $row)<tr><td>{{ $row->transaction_date }}</td><td>{{ $row->operator_name }}</td><td>{{ $row->shift_number ?: '—' }}</td><td>{{ $row->reference_number }}</td><td>{{ ucfirst($row->commission_type) }} / {{ number_format((float) $row->commission_rate,4) }}</td><td class="amount">{{ number_format((float) $row->base_excess_amount,4) }}</td><td class="amount">{{ number_format((float) $row->amount,4) }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td></tr>
        @empty<tr><td colspan="8" class="pdn-empty">No excess commission records.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
