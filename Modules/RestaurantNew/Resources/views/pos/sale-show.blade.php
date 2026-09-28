@extends('restaurantnew::layouts.app')
@section('title', $sale->order_no)
@section('content')
<div class="rn-page">
    <div class="rn-header"><h3>{{ $sale->order_no }}</h3><div><a class="btn btn-warning" target="_blank" href="{{ route('restaurantnew.sales.bill.print', $sale->id) }}">Print Bill</a><a class="btn btn-primary" href="{{ route('restaurantnew.sales.create') }}">New Sale</a></div></div>
    <div class="rn-card"><p>Status: <b>{{ $sale->status }}</b> | Kitchen: <b>{{ $sale->kitchen_status }}</b> | Payment: <b>{{ $sale->payment_status }}</b></p>
        <table class="table table-bordered"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>Status</th></tr></thead><tbody>@foreach($sale->lines as $line)<tr><td>{{ $line->menu_item_name }}</td><td>{{ number_format($line->quantity,3) }}</td><td>{{ number_format($line->unit_price,2) }}</td><td>{{ number_format($line->line_total,2) }}</td><td>{{ $line->status }}</td></tr>@endforeach</tbody></table>
        <h4 class="text-right">Grand Total: {{ number_format($sale->grand_total,2) }}</h4>
    </div>
</div>
@endsection
