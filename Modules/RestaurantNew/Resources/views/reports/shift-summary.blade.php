@extends('restaurantnew::layouts.app')
@section('title', 'Shift Summary')
@section('content')
<div class="rn-page"><div class="rn-header-card"><h3>Cashier Shift Summary</h3></div><div class="rn-card"><table class="table table-bordered rn-datatable"><thead><tr><th>Shift</th><th>Status</th><th>Opening</th><th>Cash Sales</th><th>Cash In</th><th>Cash Out</th><th>Expected</th><th>Counted</th><th>Short/Excess</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->shift_no }}</td><td>{{ $row->status }}</td><td>{{ number_format($row->opening_cash, 4) }}</td><td>{{ number_format($row->cash_sales, 4) }}</td><td>{{ number_format($row->cash_in, 4) }}</td><td>{{ number_format($row->cash_out, 4) }}</td><td>{{ number_format($row->expected_cash, 4) }}</td><td>{{ number_format($row->counted_cash ?? 0, 4) }}</td><td>{{ number_format($row->shortage_excess, 4) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
