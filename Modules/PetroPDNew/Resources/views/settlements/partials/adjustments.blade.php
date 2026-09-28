@if($editable)
@can('petro_pd_new.adjustments.request')
<details class="pdn-details" open><summary>Request Amount Adjustment</summary>
<form method="post" action="{{ route('petro-pd-new.adjustments.store',$settlement->id) }}" class="pdn-form-grid" style="margin-top:12px" data-prevent-double-submit>@csrf
<div class="pdn-field"><label>Field</label><select class="pdn-select" name="field_name" required>
@foreach(['meter_sales_total'=>'Meter Sales Total','other_sales_total'=>'Other Sales Total','source_payments_total'=>'Source Payments Total','manual_payments_total'=>'Manual Payments Total','expected_total'=>'Expected Total','received_total'=>'Received Total'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
</select></div>
<div class="pdn-field"><label>Adjustment Type</label><select class="pdn-select" name="adjustment_type"><option value="replace">Replace</option><option value="increase">Increase</option><option value="decrease">Decrease</option></select></div>
<div class="pdn-field"><label>Requested Amount</label><input class="pdn-input" type="number" step="0.0001" name="requested_amount" required></div>
<div class="pdn-field full"><label>Reason</label><textarea class="pdn-textarea" name="reason" required></textarea></div>
<div class="full pdn-actions"><button class="pdn-btn warning">Submit Request</button></div>
</form></details>
@endcan
@endif
<div class="pdn-table-wrap" style="margin-top:12px"><table class="pdn-table"><thead><tr><th>No</th><th>Field</th><th>Type</th><th>Status</th><th class="amount">Current</th><th class="amount">Requested</th><th class="amount">Approved</th><th>Reason / Decision</th><th>Action</th></tr></thead><tbody>
@forelse($settlement->adjustments->sortByDesc('id') as $row)<tr><td>{{ $row->adjustment_number }}</td><td>{{ str_replace('_',' ',$row->field_name) }}</td><td>{{ $row->adjustment_type }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td class="amount">{{ number_format((float)$row->current_amount,4) }}</td><td class="amount">{{ number_format((float)$row->requested_amount,4) }}</td><td class="amount">{{ number_format((float)$row->approved_amount,4) }}</td><td>{{ $row->reason }} @if($row->decision_note)<br><small>{{ $row->decision_note }}</small>@endif</td><td>
@if($editable && $row->status==='requested')@can('petro_pd_new.adjustments.approve')
<details><summary class="pdn-btn small light">Decide</summary><div class="pdn-popover pdn-stack">
<form method="post" action="{{ route('petro-pd-new.adjustments.approve',$row->id) }}" data-prevent-double-submit>@csrf
<input class="pdn-input" type="number" step="0.0001" name="approved_amount" value="{{ $row->requested_amount }}"><input class="pdn-input" name="note" placeholder="Approval note"><button class="pdn-btn small success">Approve</button></form>
<form method="post" action="{{ route('petro-pd-new.adjustments.reject',$row->id) }}" data-prevent-double-submit>@csrf
<input class="pdn-input" name="note" required placeholder="Rejection reason"><button class="pdn-btn small danger">Reject</button></form></div></details>
@endcan @else — @endif
</td></tr>@empty<tr><td colspan="9" class="pdn-empty">No amount adjustments.</td></tr>@endforelse
</tbody></table></div>
