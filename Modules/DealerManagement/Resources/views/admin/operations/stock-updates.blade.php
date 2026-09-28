@extends('layouts.app')
@section('title','Dealer Stock Updates')
@section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Dealer Stock Updates / Confirmations</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Update No</th><th>Date</th><th>Dealer</th><th>Outlet</th><th>Submitted By</th><th>Submitted At</th><th>Products</th><th>Total Variance</th><th>Notes</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r->update_no }}</td><td>{{ $r->update_date }}</td><td>{{ $r->dealer_code }} - {{ $r->dealer_name }}</td><td>{{ $r->outlet_name }}</td><td>{{ $r->submitted_by_name ?: '-' }}</td><td>{{ $r->submitted_at }}</td><td>{{ $r->product_lines }}</td><td>{{ number_format($r->total_absolute_variance,2) }}</td><td>{{ $r->notes ?: '-' }}</td></tr>
@empty<tr><td colspan="9" class="text-center">No dealer stock confirmations available yet. Dealer-submitted stock updates will appear here.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div></section>
@endsection
