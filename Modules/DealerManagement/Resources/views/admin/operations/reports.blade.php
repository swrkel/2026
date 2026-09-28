@extends('layouts.app') @section('title','Dealer Management Reports') @section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Dealer Management Reports</h1></section><section class="content">
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Available Reports</h3></div><div class="box-body"><div class="row">
@foreach(['Dealer Current Stock','Dealer Stock Ledger','Dealer Low Stock / Re-order','Dealer Stock Update Status','Dealer Orders','Dealer Deliveries','Dealer Returns','Dealer Stock Variance','Route-wise Replenishment','Dealer Product Movement'] as $x)<div class="col-md-4"><div class="well well-sm">{{ $x }}</div></div>@endforeach
</div></div></div>
<div class="box box-danger"><div class="box-header"><h3 class="box-title">Current Low / Re-order Stock</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Dealer</th><th>Outlet</th><th>Product</th><th>Current</th><th>Re-order Level</th></tr></thead><tbody>@forelse($lowStock as $r)<tr><td>{{ $r->dealer_name }}</td><td>{{ $r->outlet_name }}</td><td>{{ $r->product_name ?: 'Product #'.$r->product_id }}</td><td>{{ number_format($r->confirmed_qty ?? $r->system_qty ?? 0,2) }}</td><td>{{ number_format($r->reorder_level??0,2) }}</td></tr>@empty<tr><td colspan="5">No products currently below re-order level.</td></tr>@endforelse</tbody></table></div></div>
</section>@endsection
