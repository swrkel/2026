@extends('layouts.app')
@section('title', 'Billing Services')
@section('content')
<section class="content-header"><h1>Billing Services <a href="{{ route('myhealth.billing.services.create') }}" class="btn btn-primary pull-right">Add Service</a></h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<form method="get" class="form-inline"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"> <button class="btn btn-default">Search</button></form><br>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th class="text-right">Amount</th><th>Status</th></tr></thead><tbody>
@forelse($services as $service)<tr><td>{{ $service->service_code }}</td><td>{{ $service->service_name }}</td><td>{{ $service->service_type }}</td><td class="text-right">{{ number_format($service->default_amount, 4) }}</td><td>{{ $service->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="5">No records found.</td></tr>@endforelse
</tbody></table></div>{{ $services->links() }}
</section>
@endsection
