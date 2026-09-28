@extends('pos::layouts.app')
@section('title', $title ?? 'POS Sales List')
@section('pos_content')
<div class="pos-hero"><div><span class="eyebrow">Sales Register</span><h2>Completed POS Sales</h2><p>Standalone sales list with receipt reprint.</p></div><a class="btn btn-primary" href="{{ route('pos.sales.index') }}">New Sale</a></div>
<div class="card pos-card"><div class="card-body">
<form method="GET" class="toolbar-row"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search sale no/customer"><button class="btn btn-primary">Search</button><a class="btn btn-warning" href="{{ route('pos.sales.list') }}">Reset</a></form>
<table class="table modern-table"><thead><tr><th>Sale No</th><th>Date</th><th>Customer</th><th class="text-right">Total</th><th class="text-right">Paid</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td>{{ $sale->sale_no }}</td><td>{{ $sale->sale_date }}</td><td>{{ $sale->customer_name }}</td><td class="text-right">{{ number_format($sale->total_amount,2) }}</td><td class="text-right">{{ number_format($sale->paid_amount,2) }}</td><td><span class="badge-soft">{{ $sale->payment_status }}</span></td><td><a class="btn btn-default btn-xs" href="{{ route('pos.sales.receipt', $sale->id) }}">Receipt</a></td></tr>@empty<tr><td colspan="7" class="text-center muted">No sales found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($sales, 'links')) {{ $sales->links() }} @endif
</div></div>
@endsection
