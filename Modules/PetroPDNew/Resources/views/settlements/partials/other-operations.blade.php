<div class="pdn-grid two">
<div><h3>Other Sales</h3><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Sale No</th><th>Date</th><th>Status</th><th class="amount">Gross</th><th class="amount">Discount</th><th class="amount">Net</th></tr></thead><tbody>
@forelse($settlement->otherSales as $row)<tr><td>{{ $row->sale_number }}</td><td>{{ optional($row->sale_at)->format('d M Y H:i') }}</td><td>{{ $row->status }}</td><td class="amount">{{ number_format((float)$row->gross_amount,4) }}</td><td class="amount">{{ number_format((float)$row->discount_amount,4) }}</td><td class="amount">{{ number_format((float)$row->net_amount,4) }}</td></tr>
@if($row->lines->isNotEmpty())<tr><td></td><td colspan="5">@foreach($row->lines as $line)<span class="pdn-chip">Product {{ $line->product_id }} × {{ number_format((float)$line->quantity,3) }} = {{ number_format((float)$line->amount,4) }}</span>@endforeach</td></tr>@endif
@empty<tr><td colspan="6" class="pdn-empty">No other sales.</td></tr>@endforelse
</tbody></table></div></div>
<div><h3>Unload Stock</h3><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Receipt</th><th>Bill</th><th>Date</th><th class="amount">Quantity</th><th class="amount">Amount</th><th>Status</th></tr></thead><tbody>
@forelse($settlement->unloadStocks as $row)<tr><td>{{ $row->receipt_number }}</td><td>{{ $row->bill_number ?: '—' }}</td><td>{{ optional($row->unloaded_at)->format('d M Y H:i') }}</td><td class="amount">{{ number_format((float)$row->total_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->total_amount,4) }}</td><td>{{ $row->status }}</td></tr>
@if($row->lines->isNotEmpty())<tr><td></td><td colspan="5">@foreach($row->lines as $line)<span class="pdn-chip">Product {{ $line->product_id }} / Qty {{ number_format((float)$line->quantity,3) }}</span>@endforeach</td></tr>@endif
@empty<tr><td colspan="6" class="pdn-empty">No unload records.</td></tr>@endforelse
</tbody></table></div></div>
</div>
<h3 style="margin-top:18px">Day Entries & Collections</h3>
<div class="pdn-grid two">
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Type</th><th>Reference</th><th>Pump</th><th class="amount">Qty</th><th class="amount">Amount</th><th>Status</th></tr></thead><tbody>
@forelse($settlement->dayEntries as $row)<tr><td>{{ $row->entry_type }}</td><td>{{ $row->reference_no ?: '—' }}</td><td>{{ $row->pump_id ?: '—' }}</td><td class="amount">{{ number_format((float)$row->quantity,3) }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="6" class="pdn-empty">No day entries.</td></tr>@endforelse
</tbody></table></div>
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Collection No</th><th>Date</th><th>Status</th><th class="amount">Expected</th><th class="amount">Declared</th><th class="amount">Difference</th></tr></thead><tbody>
@forelse($settlement->collections as $row)<tr><td>{{ $row->collection_number }}</td><td>{{ optional($row->collection_at)->format('d M Y H:i') ?: '—' }}</td><td>{{ $row->status }}</td><td class="amount">{{ number_format((float)$row->expected_amount,4) }}</td><td class="amount">{{ number_format((float)$row->declared_amount,4) }}</td><td class="amount">{{ number_format((float)$row->difference_amount,4) }}</td></tr>@empty<tr><td colspan="6" class="pdn-empty">No daily collections.</td></tr>@endforelse
</tbody></table></div>
</div>

<h3 style="margin-top:18px">Shortage Recoveries & Excess Commissions</h3>
<div class="pdn-grid two">
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Recovery No</th><th>Date</th><th>Method</th><th>Reference</th><th>Status</th><th class="amount">Amount</th></tr></thead><tbody>
@forelse($settlement->recoveries as $row)<tr><td>{{ $row->recovery_number }}</td><td>{{ optional($row->recovery_date)->format('d M Y') }}</td><td>{{ $row->payment_method ?: '—' }}</td><td>{{ $row->reference_no ?: '—' }}</td><td>{{ $row->status }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td></tr>@empty<tr><td colspan="6" class="pdn-empty">No shortage recoveries.</td></tr>@endforelse
</tbody><tfoot><tr><th colspan="5">Recovered Total</th><th class="amount">{{ number_format((float)$settlement->shortage_recovery_total,4) }}</th></tr></tfoot></table></div>
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Commission No</th><th>Date</th><th>Type</th><th class="amount">Base Excess</th><th class="amount">Rate</th><th class="amount">Commission</th></tr></thead><tbody>
@forelse($settlement->commissions as $row)<tr><td>{{ $row->commission_number }}</td><td>{{ optional($row->commission_date)->format('d M Y') }}</td><td>{{ $row->commission_type ?: '—' }}</td><td class="amount">{{ number_format((float)$row->base_excess_amount,4) }}</td><td class="amount">{{ number_format((float)$row->commission_rate,4) }}</td><td class="amount">{{ number_format((float)$row->commission_amount,4) }}</td></tr>@empty<tr><td colspan="6" class="pdn-empty">No excess commissions.</td></tr>@endforelse
</tbody><tfoot><tr><th colspan="5">Commission Total</th><th class="amount">{{ number_format((float)$settlement->excess_commission_total,4) }}</th></tr></tfoot></table></div>
</div>
