<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('simpleaudit::simpleaudit.module_name') }} - {{ __('simpleaudit::simpleaudit.purchase_audit') }}</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#1f2937;margin:24px;font-size:11px}h1{font-size:21px;margin:0 0 4px}.sub{color:#6b7280;margin-bottom:16px}.meta{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}.meta div{border:1px solid #d1d5db;border-radius:7px;padding:7px}.meta strong{display:block;font-size:9px;text-transform:uppercase;color:#6b7280;margin-bottom:2px}.section{margin-top:14px;page-break-inside:avoid}.section h2{font-size:13px;margin:0;padding:7px 8px;background:#f3f4f6;border:1px solid #d1d5db;border-bottom:0}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d1d5db;padding:5px 6px}th{background:#f9fafb;font-size:9px;text-transform:uppercase;text-align:left}td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}tfoot td{font-weight:bold;background:#f9fafb}.note{padding:9px;border:1px solid #ead9a6;background:#fff9e7;border-radius:7px;margin-bottom:12px}.actions{margin-bottom:12px}.actions button{padding:7px 11px;border:1px solid #9ca3af;border-radius:6px;background:#fff;cursor:pointer}.muted{color:#9ca3af}.bad{color:#a33;font-weight:bold}@media print{body{margin:8mm}.actions{display:none}.section{page-break-inside:auto}thead{display:table-header-group}tr{page-break-inside:avoid}.meta{grid-template-columns:repeat(4,1fr)}}
</style>
</head>
<body>
@if(!$public)<div class="actions"><button onclick="window.print()">{{ __('simpleaudit::simpleaudit.print') }}</button></div>@endif
<h1>{{ __('simpleaudit::simpleaudit.module_name') }} — {{ __('simpleaudit::simpleaudit.purchase_audit') }}</h1>
<div class="sub">{{ __('simpleaudit::simpleaudit.selected_period_help') }}</div>
@if(!empty($shareNote))<div class="note"><strong>{{ __('simpleaudit::simpleaudit.note') }}:</strong> {{ $shareNote }}</div>@endif
<div class="meta">
  <div><strong>{{ __('simpleaudit::simpleaudit.business') }}</strong>{{ $report['meta']['business_name'] }}</div>
  <div><strong>{{ __('simpleaudit::simpleaudit.location') }}</strong>{{ $report['meta']['location_name'] }}</div>
  <div><strong>{{ __('simpleaudit::simpleaudit.store') }}</strong>{{ $report['meta']['store_name'] }}</div>
  <div><strong>{{ __('simpleaudit::simpleaudit.date_period') }}</strong>{{ $report['meta']['from'] }} {{ __('simpleaudit::simpleaudit.to') }} {{ $report['meta']['to'] }}</div>
</div>
@php
$cp=(int)$report['precision']['currency'];$qp=(int)$report['precision']['quantity'];
$nf=function($v,$p){return $v===null?__('simpleaudit::simpleaudit.na'):number_format((float)$v,(int)$p,'.',',');};
@endphp

<div class="section"><h2>{{ __('simpleaudit::simpleaudit.purchase') }}</h2><div class="table-wrap"><table><thead><tr><th>{{ __('simpleaudit::simpleaudit.product') }}</th><th>{{ __('simpleaudit::simpleaudit.code') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.qty') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.unit_cost') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.total') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.discount') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.tax') }}</th></tr></thead><tbody>
@forelse($report['sections']['purchases']['rows'] as $r)<tr><td>{{ $r['product'] }}</td><td>{{ $r['sku'] }}</td><td class="num">{{ $nf($r['qty'],$r['qty_precision']) }}</td><td class="num">{{ $nf($r['unit_cost'],$cp) }}</td><td class="num">{{ $nf($r['total'],$cp) }}</td><td class="num">{{ $nf($r['discount'],$cp) }}</td><td class="num">{{ $nf($r['tax'],$cp) }}</td></tr>@empty<tr><td colspan="7" class="muted">{{ __('simpleaudit::simpleaudit.no_affected_records') }}</td></tr>@endforelse
</tbody><tfoot><tr>@php $t=$report['sections']['purchases']['totals']; @endphp<td>{{ strtoupper(__('simpleaudit::simpleaudit.total')) }}</td><td></td><td class="num">{{ $nf($t['qty'],$qp) }}</td><td class="num">{{ $nf($t['unit_cost'],$cp) }}</td><td class="num">{{ $nf($t['total'],$cp) }}</td><td class="num">{{ $nf($t['discount'],$cp) }}</td><td class="num">{{ $nf($t['tax'],$cp) }}</td></tr></tfoot></table></div></div>

<div class="section"><h2>{{ __('simpleaudit::simpleaudit.stock_movements') }}</h2><div class="table-wrap"><table><thead><tr><th>{{ __('simpleaudit::simpleaudit.product') }}</th><th>{{ __('simpleaudit::simpleaudit.code') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.before') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.purchases') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.purchase_return') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.stock_adjustment') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.after') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.difference') }}</th></tr></thead><tbody>
@forelse($report['sections']['stock_movements']['rows'] as $r)<tr><td>{{ $r['product'] }}</td><td>{{ $r['sku'] }}</td><td class="num">{{ $nf($r['before'],$r['qty_precision']) }}</td><td class="num">{{ $nf($r['purchases'],$r['qty_precision']) }}</td><td class="num">{{ $nf($r['purchase_return'],$r['qty_precision']) }}</td><td class="num">{{ $nf($r['stock_adjustment'],$r['qty_precision']) }}</td><td class="num">{{ $nf($r['after'],$r['qty_precision']) }}</td><td class="num {{ $r['difference']!==null && abs($r['difference'])>0.0000001?'bad':'' }}">{{ $nf($r['difference'],$r['qty_precision']) }}</td></tr>@empty<tr><td colspan="8" class="muted">{{ __('simpleaudit::simpleaudit.no_affected_records') }}</td></tr>@endforelse
</tbody><tfoot>@php $t=$report['sections']['stock_movements']['totals']; @endphp<tr><td>{{ strtoupper(__('simpleaudit::simpleaudit.total')) }}</td><td></td><td class="num">{{ $t['snapshot_complete']?$nf($t['before'],$qp):__('simpleaudit::simpleaudit.na') }}</td><td class="num">{{ $nf($t['purchases'],$qp) }}</td><td class="num">{{ $nf($t['purchase_return'],$qp) }}</td><td class="num">{{ $nf($t['stock_adjustment'],$qp) }}</td><td class="num">{{ $t['snapshot_complete']?$nf($t['after'],$qp):__('simpleaudit::simpleaudit.na') }}</td><td class="num">{{ $t['snapshot_complete']?$nf($t['difference'],$qp):__('simpleaudit::simpleaudit.na') }}</td></tr></tfoot></table></div></div>

@foreach(['supplier_payments'=>__('simpleaudit::simpleaudit.supplier_payments'),'accounts'=>__('simpleaudit::simpleaudit.accounts'),'supplier_ledgers'=>__('simpleaudit::simpleaudit.supplier_ledgers')] as $section=>$title)
<div class="section"><h2>{{ $title }}</h2><div class="table-wrap"><table><thead><tr><th>{{ $section==='accounts'?__('simpleaudit::simpleaudit.account'):__('simpleaudit::simpleaudit.supplier') }}</th>@if($section==='accounts')<th>{{ __('simpleaudit::simpleaudit.account_no') }}</th>@endif<th class="num">{{ __('simpleaudit::simpleaudit.before') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.after') }}</th><th class="num">{{ __('simpleaudit::simpleaudit.difference') }}</th></tr></thead><tbody>
@forelse($report['sections'][$section]['rows'] as $r)<tr><td>{{ $section==='accounts'?$r['account']:$r['supplier'] }}</td>@if($section==='accounts')<td>{{ $r['account_number'] }}</td>@endif<td class="num">{{ $nf($r['before'],$cp) }}</td><td class="num">{{ $nf($r['after'],$cp) }}</td><td class="num">{{ $nf($r['difference'],$cp) }}</td></tr>@empty<tr><td colspan="{{ $section==='accounts'?5:4 }}" class="muted">{{ __('simpleaudit::simpleaudit.no_affected_records') }}</td></tr>@endforelse
</tbody><tfoot>@php $t=$report['sections'][$section]['totals']; @endphp<tr><td>{{ strtoupper(__('simpleaudit::simpleaudit.total')) }}</td>@if($section==='accounts')<td></td>@endif<td class="num">{{ $nf($t['before'],$cp) }}</td><td class="num">{{ $nf($t['after'],$cp) }}</td><td class="num">{{ $nf($t['difference'],$cp) }}</td></tr></tfoot></table></div></div>
@endforeach

@if(!empty($report['meta']['stock_tracking_started_at']) && !$report['sections']['stock_movements']['totals']['snapshot_complete'])
<p class="muted">{{ __('simpleaudit::simpleaudit.stock_history_available_from', ['date'=>$report['meta']['stock_tracking_started_at']]) }}</p>
@endif
@if(($report['meta']['purchase_return_tracking_complete'] ?? true) === false)
<p class="muted">{{ !empty($report['meta']['audit_tracking_started_at'])
    ? __('simpleaudit::simpleaudit.purchase_return_history_incomplete', ['date'=>$report['meta']['audit_tracking_started_at']])
    : __('simpleaudit::simpleaudit.purchase_return_history_unavailable') }}</p>
@endif
</body></html>
