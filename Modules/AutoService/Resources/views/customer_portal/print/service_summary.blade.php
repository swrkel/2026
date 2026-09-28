<!doctype html>
<html><head><meta charset="utf-8"><title>Auto Service Summary</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222} .head{border-bottom:2px solid #333;margin-bottom:15px;padding-bottom:8px} h2,h3{margin:6px 0}.muted{color:#777}.kpi{display:inline-block;width:23%;border:1px solid #ddd;padding:8px;margin:4px 0;vertical-align:top} table{width:100%;border-collapse:collapse;margin:10px 0} th,td{border:1px solid #ddd;padding:6px} th{background:#f5f5f5}.right{text-align:right}.section{page-break-inside:avoid;margin-bottom:15px}@media print{.no-print{display:none}}
</style></head><body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">Print / Save PDF</button></div>
<div class="head"><h2>Auto Service Customer Summary</h2><div class="muted">Generated: {{ $generatedAt }} | Lookup: {{ $keyword }}</div></div>
<div class="section"><h3>Vehicle / Current Service</h3>
<div class="kpi"><small>Vehicle</small><h3>{{ $selectedVehicle->registration_no ?? '-' }}</h3></div>
<div class="kpi"><small>Job No</small><h3>{{ $selectedJob->job_no ?? '-' }}</h3></div>
<div class="kpi"><small>Status</small><h3>{{ ucwords(str_replace('_',' ', $selectedJob->status ?? '-')) }}</h3></div>
<div class="kpi"><small>Odometer</small><h3>{{ number_format((float)($selectedJob->odometer ?? 0),0) }}</h3></div>
</div>
@if(!empty($currentBill))
<div class="section"><h3>{{ $currentBill['title'] }}</h3>
<div class="kpi"><small>Total</small><h3>{{ number_format((float)$currentBill['total_amount'],2) }}</h3></div>
<div class="kpi"><small>Paid</small><h3>{{ number_format((float)$currentBill['paid_amount'],2) }}</h3></div>
<div class="kpi"><small>Balance</small><h3>{{ number_format((float)$currentBill['balance_amount'],2) }}</h3></div>
<table><thead><tr><th>Type</th><th>Description</th><th class="right">Qty</th><th class="right">Unit Price</th><th class="right">Discount</th><th class="right">Tax</th><th class="right">Total</th></tr></thead><tbody>
@foreach($currentBill['lines'] as $line)<tr><td>{{ $line->line_type }}</td><td>{{ $line->description }}</td><td class="right">{{ number_format((float)$line->quantity,2) }}</td><td class="right">{{ number_format((float)$line->unit_price,2) }}</td><td class="right">{{ number_format((float)$line->discount_amount,2) }}</td><td class="right">{{ number_format((float)$line->tax_amount,2) }}</td><td class="right">{{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach
</tbody></table></div>
@endif
<div class="section"><h3>Parts & Accessories Used</h3>
<div class="kpi"><small>Total Qty</small><h3>{{ number_format((float)$partsSummary['qty'],2) }}</h3></div>
<div class="kpi"><small>Gross</small><h3>{{ number_format((float)$partsSummary['subtotal'],2) }}</h3></div>
<div class="kpi"><small>Discount</small><h3>{{ number_format((float)$partsSummary['discount'],2) }}</h3></div>
<div class="kpi"><small>Net</small><h3>{{ number_format((float)$partsSummary['total'],2) }}</h3></div>
<table><thead><tr><th>Date</th><th>Job No</th><th>Reference</th><th>Part / Accessory</th><th class="right">Qty</th><th class="right">Unit Price</th><th class="right">Discount</th><th class="right">Tax</th><th class="right">Total</th></tr></thead><tbody>
@forelse($partsHistory as $part)<tr><td>{{ $part->used_date }}</td><td>{{ $part->job_no }}</td><td>{{ $part->reference_no }}</td><td>{{ $part->description }}</td><td class="right">{{ number_format((float)$part->quantity,2) }}</td><td class="right">{{ number_format((float)$part->unit_price,2) }}</td><td class="right">{{ number_format((float)$part->discount_amount,2) }}</td><td class="right">{{ number_format((float)$part->tax_amount,2) }}</td><td class="right">{{ number_format((float)$part->total_amount,2) }}</td></tr>@empty<tr><td colspan="9" style="text-align:center">No records.</td></tr>@endforelse
</tbody></table></div>
<div class="section"><h3>Past Service History</h3><table><thead><tr><th>Job No</th><th>Date</th><th>Status</th><th>Odometer</th><th class="right">Total</th><th class="right">Paid</th><th class="right">Balance</th><th>Next Service</th></tr></thead><tbody>
@forelse($serviceHistory as $job)<tr><td>{{ $job->job_no }}</td><td>{{ $job->job_date }}</td><td>{{ ucwords(str_replace('_',' ', $job->status)) }}</td><td>{{ number_format((float)$job->odometer,0) }}</td><td class="right">{{ number_format((float)$job->total_amount,2) }}</td><td class="right">{{ number_format((float)$job->paid_amount,2) }}</td><td class="right">{{ number_format((float)$job->balance_amount,2) }}</td><td>{{ $job->next_service_date }}</td></tr>@empty<tr><td colspan="8" style="text-align:center">No service history.</td></tr>@endforelse
</tbody></table></div>
</body></html>
