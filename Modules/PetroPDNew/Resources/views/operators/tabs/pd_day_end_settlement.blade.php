@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-page-head pdn-operator-subhead">
    <div><h3>Day End – Settlements</h3><p>Prepared and finalized Petro PD-New Day End records.</p></div>
    <div class="pdn-actions">
        @can('petro_pd_new.day_end.manage')<a class="pdn-btn primary" href="{{ route('petro-pd-new.day-ends.create') }}"><i class="fa fa-plus"></i> Prepare Day End</a>@endcan
        <a class="pdn-btn light" href="{{ route('petro-pd-new.day-ends.index') }}">View All</a>
    </div>
</div>
<div class="pdn-operator-summary-grid wide">
    <div class="pdn-operator-summary-card"><span>Settlements</span><strong>{{ $totals['settlements'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Settlement Total</span><strong>{{ number_format((float)($totals['amount'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Payments</span><strong>{{ number_format((float)($totals['payments'] ?? 0),4) }}</strong></div>
    <div class="pdn-operator-summary-card {{ ((float)($totals['variance'] ?? 0)) == 0.0 ? 'success' : 'danger' }}"><span>Variance</span><strong>{{ number_format((float)($totals['variance'] ?? 0),4) }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Action</th><th>Day End No.</th><th>Date</th><th>Status</th><th class="amount">Settlements</th><th class="amount">Settlement Total</th><th class="amount">Payments</th><th class="amount">Variance</th><th>Prepared</th><th>Finalized</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a class="pdn-btn small primary" href="{{ route('petro-pd-new.day-ends.show',$row->id) }}">View</a></td><td><strong>{{ $row->day_end_number }}</strong></td><td>{{ $row->day_end_date }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td class="amount">{{ $row->settlement_count }}</td><td class="amount">{{ number_format((float)$row->settlements_total,4) }}</td><td class="amount">{{ number_format((float)$row->payments_total,4) }}</td><td class="amount">{{ number_format((float)$row->variance_total,4) }}</td><td>{{ $row->prepared_at ?: '—' }}</td><td>{{ $row->finalized_at ?: '—' }}</td><td>{{ $row->note ?: '—' }}</td></tr>
@empty<tr><td colspan="11" class="pdn-empty">No Day End records were found.</td></tr>@endforelse
</tbody></table></div></div>
