@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-card"><div class="productsnew-card-header"><div><h3>Expiry Alert Centre</h3><p>Find expired and soon-expiring stock across branches.</p></div><form method="POST" action="{{ route('products-new.expiry.refresh') }}">@csrf<input type="hidden" name="days" value="30"><button class="btn btn-warning">Refresh 30 Days</button></form></div></div>
<div class="productsnew-card"><table class="table table-bordered table-striped"><thead><tr><th>Product</th><th>Batch</th><th>Location</th><th>Expiry</th><th>Severity</th><th>Resolved</th><th>Action</th></tr></thead><tbody>@forelse($alerts as $a)<tr><td>{{ $a->product_id }}</td><td>{{ $a->batch_no }}</td><td>{{ $a->location_id }}</td><td>{{ optional($a->expiry_at)->format('Y-m-d') }}</td><td><span class="label label-{{ $a->severity == 'expired' ? 'danger' : 'warning' }}">{{ $a->severity }}</span></td><td>{{ $a->is_resolved ? 'Yes' : 'No' }}</td><td>@if(!$a->is_resolved)<form method="POST" action="{{ route('products-new.expiry.resolve',$a->id) }}">@csrf<button class="btn btn-xs btn-success">Resolve</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="text-center">No expiry alerts found.</td></tr>@endforelse</tbody></table>{{ $alerts->links() }}</div>

@endsection
