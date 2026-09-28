@extends('RiceMill::layout')
@section('rcm-title','Sales / Dispatch')
@section('rcm-actions')<a class="rcm-btn" href="{{ route('rice-mill.dispatch.create') }}">+ New Sale</a>@endsection
@section('rcm-content')
<div class="rcm-card">
    @include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-dispatch-table','exportName'=>'rice-mill-dispatch','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'sales'])
    <div class="rcm-table-wrap">
        <table id="rcm-dispatch-table" class="rcm-table rcm-managed-table">
            <thead><tr><th>Sales Invoice</th><th>Date &amp; Time</th><th>Customer</th><th>Status</th><th class="rcm-num">Net Total</th><th data-rcm-no-export>Action</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                @php
                    $saleDate = $r->dispatch_date ? $r->dispatch_date->format('Y-m-d') : '';
                    $saleTime = $r->created_at ? $r->created_at->format('H:i:s') : '';
                    $customerName = $customerNames[(int)$r->customer_id] ?? ('Customer #'.$r->customer_id);
                @endphp
                <tr>
                    <td>{{ $r->dispatch_no }}</td>
                    <td>{{ trim($saleDate.' '.$saleTime) }}</td>
                    <td>{{ $customerName }}</td>
                    <td>{{ ucfirst($r->status) }}</td>
                    <td class="rcm-num">{{ number_format($r->net_total,$rcmCurrencyPrecision) }}</td>
                    <td><a class="rcm-btn" href="{{ route('rice-mill.dispatch.show',$r->id) }}">View Sales Invoice</a></td>
                </tr>
            @empty
                <tr data-rcm-empty-row><td colspan="6" class="rcm-muted">No sales found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</div>
@endsection
