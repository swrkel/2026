@extends('layouts.app')
@section('title', 'Insurance Companies')
@section('content')
<section class="content-header"><h1>Insurance Companies</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<form method="GET" class="mb-3"><div class="input-group"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search company"><span class="input-group-btn"><button class="btn btn-primary">Search</button></span></div></form>
<a href="{{ route('myhealth.insurance.companies.create') }}" class="btn btn-success mb-3">Add Company</a> <a href="{{ route('myhealth.insurance.dashboard') }}" class="btn btn-default mb-3">Dashboard</a>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Contact</th><th>Email</th><th>Status</th></tr></thead><tbody>
@foreach($companies as $company)<tr><td>{{ $company->company_code }}</td><td>{{ $company->company_name }}</td><td>{{ $company->contact_no }}</td><td>{{ $company->email }}</td><td>{{ $company->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach
</tbody></table></div>{{ $companies->links() }}
</section>
@endsection
