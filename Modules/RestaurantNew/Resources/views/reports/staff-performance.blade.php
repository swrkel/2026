@extends('restaurantnew::layouts.app')
@section('title', 'Staff Performance')
@section('content')
<div class="rn-page"><div class="rn-header-card"><h3>Staff Performance Report</h3></div><div class="rn-card"><table class="table table-bordered rn-datatable"><thead><tr><th>Staff</th><th>Role</th><th>Orders</th><th>Total Sales</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->role }}</td><td>{{ $row->order_count }}</td><td>{{ number_format($row->total_sales, 4) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
