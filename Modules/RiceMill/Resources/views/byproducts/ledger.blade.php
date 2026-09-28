@extends('RiceMill::layout')
@section('rcm-title','By-Product Ledger - '.($typeLabel ?? ucwords(str_replace('_',' ',$type))))
@section('rcm-subtitle','Production, sales, free issues, disposals and adjustments')

@section('rcm-content')
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',[
    'tableId'=>'rcm-byproduct-ledger-table',
    'exportName'=>'rice-mill-byproduct-'.$type,
    'serverPaged'=>true,
    'paginator'=>$rows,
    'rowsLabel'=>'movements'
])
<div class="rcm-table-wrap">
<table id="rcm-byproduct-ledger-table" class="rcm-table rcm-managed-table">
    <thead><tr><th>Date</th><th>Movement</th><th>Paddy Lot No.</th><th>Production Batch No.</th><th class="rcm-num">Production / In</th><th class="rcm-num">Sales</th><th class="rcm-num">Free</th><th class="rcm-num">Dispose</th><th class="rcm-num">Other Out</th><th class="rcm-num">Signed Qty</th><th>Reference</th><th>Note</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
        @php
            $isProduction = (string)$r->movement_type === 'production' || (string)$r->reference_type === 'production_batch';
            $movement = strtolower((string)$r->movement_type);
            $isSale = in_array($movement,['sale','sales','sold','dispatch','byproduct_sale'],true);
            $isFree = in_array($movement,['free','free_issue','free_qty','complimentary'],true);
            $isDispose = in_array($movement,['dispose','disposed','disposal','waste'],true);
            $isOtherOut = (float)$r->signed_quantity < 0 && !$isSale && !$isFree && !$isDispose;
            $source = $ledgerSources[(int)$r->id] ?? ['paddy_lots'=>'','production_batches'=>'','paddy_names'=>''];
        @endphp
        <tr>
            <td>{{ $r->movement_date }}</td>
            <td>{{ ucwords(str_replace('_',' ',$r->movement_type)) }}</td>
            <td>{{ $source['paddy_lots'] ?: '-' }}</td>
            <td>{{ $source['production_batches'] ?: '-' }}</td>
            <td class="rcm-num">{{ $r->signed_quantity > 0 ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
            <td class="rcm-num">{{ $isSale ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
            <td class="rcm-num">{{ $isFree ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
            <td class="rcm-num">{{ $isDispose ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
            <td class="rcm-num">{{ $isOtherOut ? number_format($r->quantity,$rcmQuantityPrecision) : '-' }}</td>
            <td class="rcm-num">{{ number_format($r->signed_quantity,$rcmQuantityPrecision) }}</td>
            <td>{{ $r->reference_type ? $r->reference_type.' #'.$r->reference_id : '-' }}</td>
            <td>{{ $r->note }}</td>
        </tr>
    @empty
        <tr data-rcm-empty-row><td colspan="12" class="rcm-muted">No by-product movements found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $rows->links() }}
</div>

<div class="rcm-card">
    <div class="rcm-panel-head"><h3>Post By-Product Movement</h3><span class="rcm-panel-hint">Sales, Free and Dispose quantities reduce current stock and cannot exceed the available balance.</span></div>
    <form class="rcm-inline" method="post" action="{{ route('rice-mill.byproducts.adjust',$type) }}">@csrf
        <select name="movement_type" required>
            <option value="sale">Sale</option>
            <option value="free_issue">Free Issue</option>
            <option value="dispose">Dispose / Waste</option>
            <option value="adjustment_out">Adjustment Out</option>
            <option value="adjustment_in">Adjustment In</option>
        </select>
        <input type="number" step="{{ $rcmQuantityStep }}" name="quantity" required min="{{ $rcmQuantityStep }}" placeholder="Quantity (kg)" value="{{ old('quantity') }}">
        <input name="note" required placeholder="Reference / reason / note" value="{{ old('note') }}">
        <button class="rcm-btn">Post Movement</button>
    </form>
    @error('quantity')<div class="rcm-alert rcm-alert-danger" style="margin-top:10px">{{ $message }}</div>@enderror
</div>
@endsection
