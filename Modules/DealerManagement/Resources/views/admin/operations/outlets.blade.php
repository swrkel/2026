@extends('layouts.app')
@section('title','Dealer Outlets')
@section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Dealer Outlets</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Dealer</th><th>Outlet Code</th><th>Outlet Name</th><th>Mobile</th><th>Address</th><th>Default</th><th>Status</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r->dealer_code }} - {{ $r->dealer_name }}</td><td>{{ $r->outlet_code }}</td><td>{{ $r->name }}</td><td>{{ $r->mobile ?: '-' }}</td><td>{{ $r->address ?: '-' }}</td><td>{{ $r->is_default ? 'Yes':'No' }}</td><td>{{ $r->is_active ? 'Active':'Inactive' }}</td><td>{{ $r->notes ?: '-' }}</td></tr>
@empty<tr><td colspan="8" class="text-center">No dealer outlets available yet. This page will populate after dealers/outlets are created.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div></section>
@endsection
