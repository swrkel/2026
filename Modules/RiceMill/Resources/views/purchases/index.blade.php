@extends('RiceMill::layout')
@section('rcm-title','Purchase Orders')
@section('rcm-actions')<a class="rcm-btn" href="{{ route('rice-mill.purchases.create') }}">+ New Purchase Order</a>@endsection
@section('rcm-content')
<div class="rcm-card">
    @include('RiceMill::partials.functionality-bar', ['tableId'=>'rcm-purchases-table','exportName'=>'rice-mill-paddy-purchases','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'purchases'])
    <div class="rcm-table-wrap">
        <table id="rcm-purchases-table" class="rcm-table rcm-managed-table">
            <thead><tr><th>Purchase Order No</th><th>Date &amp; Time</th><th>Supplier</th><th>Status</th><th class="rcm-num">Net Total</th><th data-rcm-no-export>Action</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                @php
                    $purchaseDate = $r->purchase_date ? $r->purchase_date->format('Y-m-d') : '';
                    $purchaseTime = $r->created_at ? $r->created_at->format('H:i:s') : '';
                    $supplierName = $supplierNames[(int) $r->supplier_id] ?? ('Supplier #' . $r->supplier_id);
                @endphp
                <tr><td>{{ $r->purchase_no }}</td><td>{{ trim($purchaseDate.' '.$purchaseTime) }}</td><td>{{ $supplierName }}</td><td>{{ $r->status }}</td><td class="rcm-num">{{ number_format($r->net_total,$rcmCurrencyPrecision) }}</td><td>
                    @if($r->status === 'draft' && $canApprovePurchase)<form method="post" action="{{ route('rice-mill.purchases.approve',$r->id) }}">@csrf<button class="rcm-btn">Approve</button></form>
                    @elseif($r->status === 'draft')<span class="rcm-muted">Approval permission required</span>@endif
                </td></tr>
            @empty<tr data-rcm-empty-row><td colspan="6" class="rcm-muted">No Purchase Orders found.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</div>
@endsection
