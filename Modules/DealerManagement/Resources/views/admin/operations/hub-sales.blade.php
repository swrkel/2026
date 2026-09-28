@extends('layouts.app')
@section('title','Hub Dealer Sales')
@section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Hub Dealer Sales</h1><div class="dlr-page-intro">Sales quantities entered once by dealers in the Multi-Distributor Dealer Hub and allocated to this distributor only.</div></section>
<section class="content"><div class="box"><div class="box-header"><h3 class="box-title">Dealer Hub Sales Allocations</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Date / Time</th><th>Dealer</th><th>Outlet</th><th>Product</th><th class="dlr-num">Sold / Out Qty</th><th>Hub Entry</th><th>Note</th></tr></thead><tbody>@forelse($rows as $r)<tr><td>{{ $r->movement_at }}</td><td>{{ $r->dealer_code }} - {{ $r->dealer_name }}</td><td>{{ $r->outlet_name }}</td><td>{{ $r->product_name }}</td><td class="dlr-num">{{ $r->qty }}</td><td>{{ $r->reference_no }}</td><td>{{ $r->notes }}</td></tr>@empty<tr><td colspan="7" class="dlr-empty">No Dealer Hub sales have been received yet.</td></tr>@endforelse</tbody></table>{{ $rows->links() }}</div></div></section>
@endsection
