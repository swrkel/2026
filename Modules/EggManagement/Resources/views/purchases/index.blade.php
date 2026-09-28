@extends('egg::layouts.app',['title'=>'Egg Purchases'])
@section('head_actions')<a class="egg-btn egg-btn-primary" href="{{ route('egg.purchases.create') }}">+ Add New</a>@endsection
@section('egg_content')
@include('egg::partials.toolbar')
<div class="egg-card egg-table-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>Purchase No</th><th>Date</th><th>Supplier</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Payment</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->purchase_no }}</td><td>{{ $row->purchase_date }}</td><td>{{ $row->supplier_id }}</td><td>{{ $row->subtotal }}</td><td>{{ $row->discount }}</td><td>{{ $row->total }}</td><td>{{ $row->payment_status }}</td></tr>@empty<tr><td colspan="7" class="egg-empty">No records found.</td></tr>@endforelse</tbody></table></div></div>
@if(method_exists($rows,'links'))<div class="egg-pagination">{{ $rows->links() }}</div>@endif
@endsection
