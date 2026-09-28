<div class="pdn-section-title pdn-operator-heading">
    <div><h2><i class="fa fa-minus"></i> Pumper Excess / Shortage Payments</h2><small class="text-muted">Recover shortages, pay excess commissions and review operator adjustments.</small></div>
    <button type="button" class="btn btn-primary" data-pdn-open-modal="pdn-adjustment-modal"><i class="fa fa-plus"></i> Add Payment / Recovery</button>
</div>
<div class="pdn-summary-cards">
    <div class="pdn-summary-card">Records<strong>{{ $adjustments->count() }}</strong></div>
    <div class="pdn-summary-card">Shortage / Recovery<strong>{{ number_format((float) $adjustments->whereIn('adjustment_type',['shortage','shortage_recovery'])->sum('amount'),4) }}</strong></div>
    <div class="pdn-summary-card">Excess / Commission<strong>{{ number_format((float) $adjustments->whereIn('adjustment_type',['excess','excess_commission'])->sum('amount'),4) }}</strong></div>
</div>
<div class="pdn-card pdn-compact-card pdn-scroll">
<table class="table table-bordered table-striped mb-0"><thead><tr><th>Date</th><th>Operator</th><th>Type</th><th class="text-right">Amount</th><th>Reason</th><th>Settlement</th><th>Status</th></tr></thead><tbody>
@forelse($adjustments as $row)<tr><td>{{ optional($row->created_at)->format('d/m/Y H:i') }}</td><td>{{ optional($row->operator)->name ?: '—' }}</td><td>{{ ucwords(str_replace('_',' ',$row->adjustment_type)) }}</td><td class="pdn-money">{{ number_format($row->amount,4) }}</td><td>{{ $row->reason }}</td><td>{{ optional($row->settlement)->settlement_no ?: '—' }}</td><td><span class="pdn-status {{ $row->status === 'approved' ? 'finalized' : 'draft' }}">{{ $row->status }}</span></td></tr>@empty<tr><td colspan="7" class="pdn-table-empty">No excess or shortage payment records.</td></tr>@endforelse
</tbody></table></div>
<div class="pdn-modal" id="pdn-adjustment-modal" aria-hidden="true"><div class="pdn-modal-backdrop" data-pdn-close-modal></div><div class="pdn-modal-dialog" role="dialog" aria-modal="true"><div class="pdn-modal-header"><h3><i class="fa fa-money"></i> Add Payment / Recovery</h3><button type="button" class="pdn-modal-close" data-pdn-close-modal>&times;</button></div>
<form method="post" action="{{ route('petro-direct-new.pumper-management.adjustments.store') }}" data-pdn-ajax-form>@csrf<div class="pdn-modal-body"><div class="alert alert-danger pdn-form-errors" hidden></div><div class="pdn-form-grid pdn-form-grid-2">
<div><label>Business Location *</label><select class="form-control" name="location_id" required>@foreach($locations as $loc)<option value="{{ $loc->id }}" @selected((int)$locationId === (int)$loc->id)>{{ $loc->name ?? $loc->id }}</option>@endforeach</select></div>
<div><label>Pump Operator *</label><select class="form-control select2" name="operator_id" required>@foreach($operators as $operator)<option value="{{ $operator->id }}">{{ $operator->operator_no }} - {{ $operator->name }}</option>@endforeach</select></div>
<div><label>Type *</label><select class="form-control" name="adjustment_type" required><option value="shortage_recovery">Shortage Recovery</option><option value="excess_commission">Excess Commission</option><option value="shortage">Shortage</option><option value="excess">Excess</option></select></div>
<div><label>Amount *</label><input type="number" step="0.0001" min="0.0001" class="form-control" name="amount" required></div>
<div class="pdn-span-2"><label>Reason *</label><textarea class="form-control" name="reason" rows="3" required></textarea></div>
</div></div><div class="pdn-modal-footer"><button type="button" class="btn btn-default" data-pdn-close-modal>Close</button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Save</button></div></form></div></div>
