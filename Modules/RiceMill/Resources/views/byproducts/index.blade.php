@extends('RiceMill::layout')
@section('rcm-title','By-Product Current Stock')
@section('rcm-subtitle','Current balances with Paddy Lot and Milling / Production Batch traceability')

@section('rcm-content')
<div class="rcm-card">
    <div class="rcm-panel-head">
        <h3>By-Product Summary</h3>
        <span class="rcm-panel-hint">All by-product types are detected automatically from the movement ledger.</span>
    </div>
    <div class="rcm-table-wrap">
        <table class="rcm-table">
            <thead><tr><th>By-Product</th><th class="rcm-num">Produced / In</th><th class="rcm-num">Sales</th><th class="rcm-num">Free</th><th class="rcm-num">Dispose</th><th class="rcm-num">Other Out</th><th class="rcm-num">Current Stock</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($summary as $r)
                <tr>
                    <td><strong>{{ $typeLabels[$r['byproduct_type']] ?? ucwords(str_replace('_',' ',$r['byproduct_type'])) }}</strong></td>
                    <td class="rcm-num">{{ number_format($r['produced_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['sale_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['free_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['dispose_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['other_out_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num"><strong>{{ number_format($r['balance_qty'],$rcmQuantityPrecision) }}</strong></td>
                    <td><a class="rcm-btn secondary" href="{{ route('rice-mill.byproducts.ledger',$r['byproduct_type']) }}"><i class="fa fa-book"></i> Ledger</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="rcm-muted">No by-products recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rcm-card">
    <div class="rcm-panel-head">
        <h3>Current Stock by Production Source</h3>
        <span class="rcm-panel-hint">Outgoing quantities are allocated FIFO against production sources for stock traceability.</span>
    </div>

    @include('RiceMill::partials.functionality-bar',[
        'tableId'=>'rcm-byproducts-table',
        'exportName'=>'rice-mill-byproduct-current-stock',
        'serverPaged'=>true,
        'paginator'=>$rows,
        'rowsLabel'=>'stock sources'
    ])

    <div class="rcm-table-wrap">
        <table id="rcm-byproducts-table" class="rcm-table rcm-managed-table">
            <thead>
                <tr>
                    <th>By-Product</th>
                    <th>Paddy Lot No.</th>
                    <th>Paddy</th>
                    <th>Production Batch No.</th>
                    <th>Produced / In Date</th>
                    <th class="rcm-num">Produced / In Qty</th>
                    <th class="rcm-num">Sales</th>
                    <th class="rcm-num">Free</th>
                    <th class="rcm-num">Dispose</th>
                    <th class="rcm-num">Other Out</th>
                    <th class="rcm-num">Current Qty</th>
                    <th data-rcm-no-export>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $typeLabels[$r['byproduct_type']] ?? ucwords(str_replace('_',' ',$r['byproduct_type'])) }}</td>
                    <td>{{ $r['paddy_lots'] ?: '-' }}</td>
                    <td>{{ $r['paddy_names'] ?: '-' }}</td>
                    <td>{{ $r['production_batch_no'] ?: '-' }}</td>
                    <td>{{ $r['movement_date'] }}</td>
                    <td class="rcm-num">{{ number_format($r['source_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['sale_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['free_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['dispose_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r['other_out_qty'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num"><strong>{{ number_format($r['balance_qty'],$rcmQuantityPrecision) }}</strong></td>
                    <td><a class="rcm-btn secondary" href="{{ route('rice-mill.byproducts.ledger',$r['byproduct_type']) }}"><i class="fa fa-book"></i> Ledger</a></td>
                </tr>
            @empty
                <tr data-rcm-empty-row><td colspan="12" class="rcm-muted">No current by-product stock exists for the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</div>
@endsection
