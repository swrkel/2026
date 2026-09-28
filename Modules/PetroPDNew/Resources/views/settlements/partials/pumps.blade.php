<div class="pdn-page-head compact"><div><h3>Pump Assignment Summary</h3><p>Immutable values imported from the closed Pumper Dashboard-New shift.</p></div></div>
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr>
    <th>PONE Assignment</th><th>Pump</th><th>Product</th><th class="amount">Opening</th><th class="amount">Closing</th>
    <th class="amount">Testing Qty</th><th class="amount">Sold Qty</th><th class="amount">Unit Price</th><th class="amount">Amount</th><th>Status</th>
</tr></thead><tbody>
@forelse($settlement->pumps as $row)<tr>
    <td>{{ $row->pone_assignment_id }}</td><td>{{ $row->pump_id }}</td><td>{{ $row->product_id }}</td>
    <td class="amount">{{ number_format((float)$row->opening_meter,3) }}</td><td class="amount">{{ number_format((float)$row->closing_meter,3) }}</td>
    <td class="amount">{{ number_format((float)$row->testing_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->sold_quantity,3) }}</td>
    <td class="amount">{{ number_format((float)$row->unit_price,4) }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td><td>{{ $row->status }}</td>
</tr>@empty<tr><td colspan="10" class="pdn-empty">No PONE pump assignments were imported.</td></tr>@endforelse
</tbody><tfoot><tr><th colspan="6"></th><th class="amount">{{ number_format((float)$settlement->pumps->sum('sold_quantity'),3) }}</th><th></th><th class="amount">{{ number_format((float)$settlement->pumps->sum('amount'),4) }}</th><th></th></tr></tfoot></table></div>
@if($settlement->meterSales->isNotEmpty())
<details class="pdn-details" style="margin-top:12px"><summary>Meter Reading History ({{ $settlement->meterSales->count() }})</summary>
<div class="pdn-table-wrap" style="margin-top:10px"><table class="pdn-table"><thead><tr><th>Reading At</th><th>Pump</th><th class="amount">Opening</th><th class="amount">Closing</th><th class="amount">Sold</th><th class="amount">Amount</th></tr></thead><tbody>
@foreach($settlement->meterSales as $row)<tr><td>{{ optional($row->reading_at)->format('d M Y H:i') }}</td><td>{{ $row->pump_id }}</td><td class="amount">{{ number_format((float)$row->opening_meter,3) }}</td><td class="amount">{{ number_format((float)$row->closing_meter,3) }}</td><td class="amount">{{ number_format((float)$row->sold_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td></tr>@endforeach
</tbody></table></div></details>
@endif
