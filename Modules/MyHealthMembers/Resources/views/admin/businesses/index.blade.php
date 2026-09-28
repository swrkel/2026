@extends('layouts.app')
@section('title', 'My Health Business Administration')
@section('content')
<section class="content-header"><h1>Hospital / Business Management</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Registered</th><th>Action</th></tr></thead><tbody>
@forelse($businesses as $business)<tr><td>{{ $business->name ?? '-' }}</td><td>{{ $business->email ?? '-' }}</td><td>{{ $business->mobile ?? '-' }}</td><td>{{ $business->created_at ?? '-' }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('myhealth.admin.businesses.show', $business->id) }}">View</a></td></tr>@empty<tr><td colspan="5" class="text-center">No businesses found.</td></tr>@endforelse
</tbody></table>@if(method_exists($businesses, 'links')) {{ $businesses->links() }} @endif</div></div></section>
@endsection
