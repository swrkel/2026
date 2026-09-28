@extends('restaurantnew::layouts.app')
@section('content')
@include('restaurantnew::reports.partials.toolbar', ['title' => 'Daily Summary'])
<table class="table table-bordered restaurantnew-report-table"><thead><tr><th>Date</th><th>Bills</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Service Charge</th><th>Total</th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ $row->sale_date }}</td><td>{{ $row->bills }}</td><td>{{ number_format($row->subtotal, 2) }}</td><td>{{ number_format($row->discount, 2) }}</td><td>{{ number_format($row->tax, 2) }}</td><td>{{ number_format($row->service_charge, 2) }}</td><td>{{ number_format($row->total, 2) }}</td></tr>@endforeach
</tbody><tfoot><tr><th>Total</th><th>{{ $totals['bills'] }}</th><th>{{ number_format($totals['subtotal'],2) }}</th><th>{{ number_format($totals['discount'],2) }}</th><th>{{ number_format($totals['tax'],2) }}</th><th>{{ number_format($totals['service_charge'],2) }}</th><th>{{ number_format($totals['total'],2) }}</th></tr></tfoot></table>
@endsection
