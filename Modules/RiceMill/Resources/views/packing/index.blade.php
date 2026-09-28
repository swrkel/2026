@extends('RiceMill::layout')
@section('rcm-title','Packing')
@section('rcm-actions')
<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-materials.index') }}"><i class="fa fa-cubes"></i> Packaging Materials</a>
<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-material-mappings.index') }}"><i class="fa fa-random"></i> Material Usage Mapping</a>
<a class="rcm-btn" href="{{ route('rice-mill.packing.create') }}">+ New Packing Batch</a>
@endsection
@section('rcm-content')
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-packing-table','exportName'=>'rice-mill-packing','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'packing batches'])
<div class="rcm-table-wrap">
<table id="rcm-packing-table" class="rcm-table rcm-managed-table">
    <thead><tr><th>Packing No</th><th>Rice</th><th>Production Batch No.</th><th>Packed At</th><th>Status</th><th class="rcm-num">Packed Qty</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
        @php
            $riceNames=$r->lines->map(fn($line)=>$line->product?->name)->filter()->unique()->values();
            $batchNumbers=$r->lines->flatMap(function($line){
                return $line->sources->map(fn($source)=>$source->productionBatch?->batch_no);
            })->filter()->unique()->values();
            $hasUntraced=$r->lines->flatMap(fn($line)=>$line->sources)->contains(fn($source)=>empty($source->production_batch_id));
        @endphp
        <tr>
            <td><strong>{{ $r->packing_no }}</strong></td>
            <td>{{ $riceNames->isNotEmpty() ? $riceNames->implode(', ') : '-' }}</td>
            <td>
                {{ $batchNumbers->isNotEmpty() ? $batchNumbers->implode(', ') : '-' }}
                @if($hasUntraced)<span class="rcm-muted" title="This quantity originated from a positive finished-stock adjustment rather than a production batch."> / Stock Adjustment</span>@endif
            </td>
            <td>{{ $r->packed_at }}</td>
            <td>{{ $r->status }}</td>
            <td class="rcm-num">{{ number_format($r->total_packed_qty,$rcmQuantityPrecision) }}</td>
        </tr>
    @empty
        <tr data-rcm-empty-row><td colspan="6" class="rcm-muted">No packing batches found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $rows->links() }}
</div>
@endsection
