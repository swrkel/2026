@extends('RiceMill::layout')
@section('rcm-title','Production / Milling History')
@section('rcm-actions')
    <a class="rcm-btn" href="{{ route('rice-mill.production.create') }}">
        <i class="fa fa-cogs"></i> Milling / Production Operation
    </a>
@endsection

@section('rcm-content')
<div class="rcm-card">
    @include('RiceMill::partials.functionality-bar',[
        'tableId'=>'rcm-production-table',
        'exportName'=>'rice-mill-production',
        'serverPaged'=>true,
        'paginator'=>$rows,
        'rowsLabel'=>'batches'
    ])

    <div class="rcm-table-wrap">
        <table id="rcm-production-table" class="rcm-table rcm-managed-table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Paddy</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th class="rcm-num">Input</th>
                    <th class="rcm-num">Rice</th>
                    <th class="rcm-num">Yield %</th>
                    <th class="rcm-num">Cost / Kg</th>
                    <th data-rcm-no-export>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->batch_no }}</td>
                        <td>{{ $r->paddy_names ?: '-' }}</td>
                        <td>{{ ucwords(str_replace('_',' ',$r->status)) }}</td>
                        <td>{{ $r->started_at ? \Carbon\Carbon::parse($r->started_at)->format('Y-m-d H:i:s') : '-' }}</td>
                        <td class="rcm-num">{{ number_format($r->input_qty,$rcmQuantityPrecision) }}</td>
                        <td class="rcm-num">{{ number_format($r->rice_output_qty,$rcmQuantityPrecision) }}</td>
                        <td class="rcm-num">{{ number_format($r->rice_yield_percent,2) }}</td>
                        <td class="rcm-num">{{ number_format($r->cost_per_kg,$rcmCurrencyPrecision) }}</td>
                        <td>
                            <a class="rcm-btn" href="{{ route('rice-mill.production.show',$r->id) }}">
                                <i class="fa fa-eye"></i> View
                            </a>
                            @if(in_array($r->status,['draft','in_progress']))
                                <a class="rcm-btn" href="{{ route('rice-mill.production.complete-form',$r->id) }}">
                                    <i class="fa fa-play"></i> Complete
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-rcm-empty-row>
                        <td colspan="9" class="rcm-muted">No production batches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</div>
@endsection
