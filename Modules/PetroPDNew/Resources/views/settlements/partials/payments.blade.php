@php
    $activePayments = $settlement->payments->where('status', 'active');
    $collectionPayments = $activePayments->reject(fn ($row) => in_array((string) $row->payment_type, ['shortage', 'excess'], true));
    $shortageEntries = $activePayments->where('payment_type', 'shortage');
    $excessEntries = $activePayments->where('payment_type', 'excess');
@endphp
@if($editable)
@can('petro_pd_new.payments.manage')
<details class="pdn-details" open><summary>Add Settlement Payment</summary>
<form class="pdn-form-grid" style="margin-top:12px" method="post" action="{{ route('petro-pd-new.payments.store',$settlement->id) }}" data-prevent-double-submit>
@csrf
<div class="pdn-field"><label>Payment Type</label><select class="pdn-select" name="payment_type" required>@foreach(config('petropdnew.payment_types',[]) as $type)<option value="{{ $type }}">{{ ucfirst(str_replace('_',' ',$type)) }}</option>@endforeach</select></div>
<div class="pdn-field"><label>Amount</label><input class="pdn-input" type="number" step="0.0001" min="0.0001" name="amount" required></div>
<div class="pdn-field"><label>Reference No</label><input class="pdn-input" name="reference_no" maxlength="191"></div>
<div class="pdn-field"><label>Customer Search</label><input class="pdn-input" type="search" autocomplete="off" placeholder="Type name, number or mobile" data-pdn-filter-select="pdn-payment-customer"></div>
<div class="pdn-field"><label>Customer</label><select class="pdn-select" id="pdn-payment-customer" name="customer_id"><option value="">Not applicable</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->display_name }}</option>@endforeach</select></div>
<div class="pdn-field"><label>Transaction At</label><input class="pdn-input" type="datetime-local" name="transaction_at"></div>
<div class="pdn-field full"><label>Note</label><textarea class="pdn-textarea" name="note"></textarea></div>
<div class="full pdn-actions"><button class="pdn-btn success">Save Payment</button></div>
</form></details>
@endcan
@endif
<div class="pdn-table-wrap" style="margin-top:12px"><table class="pdn-table"><thead><tr>
<th>Payment No</th><th>Source</th><th>Type</th><th>Reference</th><th>Transaction</th><th>Status</th><th class="amount">Gross</th><th class="amount">Discount</th><th class="amount">Amount</th><th>Action</th>
</tr></thead><tbody>
@forelse($settlement->payments as $row)<tr>
<td>{{ $row->payment_number }}</td><td><span class="pdn-badge {{ $row->is_source ? 'imported' : 'draft' }}">{{ $row->is_source ? 'PONE' : 'Manual' }}</span></td>
<td>{{ ucfirst(str_replace('_',' ',$row->payment_type)) }}@if(in_array((string)$row->payment_type,['shortage','excess'],true)) <span class="pdn-chip">Operational variance</span>@endif</td><td>{{ $row->reference_no ?: '—' }}</td><td>{{ optional($row->transaction_at)->format('d M Y H:i') }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td>
<td class="amount">{{ number_format((float)$row->gross_amount,4) }}</td><td class="amount">{{ number_format((float)$row->discount_amount,4) }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td>
<td>@if($editable && !$row->is_source && $row->status==='active')@can('petro_pd_new.payments.manage')
<details><summary class="pdn-btn small light">Manage</summary>
<form method="post" action="{{ route('petro-pd-new.payments.update',$row->id) }}" class="pdn-stack pdn-popover" data-prevent-double-submit>@csrf @method('PUT')
<input class="pdn-input" type="number" step="0.0001" min="0.0001" name="amount" value="{{ $row->amount }}" required>
<input class="pdn-input" name="reference_no" value="{{ $row->reference_no }}" placeholder="Reference">
<textarea class="pdn-textarea" name="note" placeholder="Note">{{ $row->note }}</textarea>
<button class="pdn-btn small primary">Update</button></form>
<form method="post" action="{{ route('petro-pd-new.payments.void',$row->id) }}" data-confirm="Void this manual payment?" class="pdn-inline">@csrf @method('DELETE')
<input class="pdn-input" name="reason" required placeholder="Void reason"><button class="pdn-btn small danger">Void</button></form>
</details>@endcan @else — @endif</td>
</tr>
@if($row->details->isNotEmpty())<tr><td></td><td colspan="9"><small>@foreach($row->details as $detail)<span class="pdn-chip">{{ $detail->detail_type }}: {{ $detail->reference_no }} / {{ number_format((float)$detail->amount,4) }}</span>@endforeach</small></td></tr>@endif
@empty<tr><td colspan="10" class="pdn-empty">No settlement payments.</td></tr>@endforelse
</tbody><tfoot>
<tr><th colspan="8">Active Settlement Collections</th><th class="amount">{{ number_format((float)$collectionPayments->sum('amount'),4) }}</th><th></th></tr>
<tr><th colspan="8">Operational Shortage Entries (not counted as collections)</th><th class="amount">{{ number_format((float)$shortageEntries->sum('amount'),4) }}</th><th></th></tr>
<tr><th colspan="8">Operational Excess Entries (not counted as collections)</th><th class="amount">{{ number_format((float)$excessEntries->sum('amount'),4) }}</th><th></th></tr>
</tfoot></table></div>
