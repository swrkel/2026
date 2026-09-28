@extends('layouts.app')
@section('title', 'My Health Doctor Administration')
@section('content')
<section class="content-header"><h1>Doctor Management</h1></section>
<section class="content"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Doctors</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Doctor Code</th><th>Name</th><th>Specialty</th><th>Registration No</th><th>Mobile</th><th>Email</th><th>Status</th></tr></thead><tbody>
@forelse($doctors as $doctor)<tr><td>{{ $doctor->doctor_code ?? $doctor->code ?? $doctor->id }}</td><td>{{ $doctor->name ?? '-' }}</td><td>{{ $doctor->specialty ?? '-' }}</td><td>{{ $doctor->registration_no ?? '-' }}</td><td>{{ $doctor->mobile ?? '-' }}</td><td>{{ $doctor->email ?? '-' }}</td><td>{{ ucfirst($doctor->status ?? 'active') }}</td></tr>@empty<tr><td colspan="7" class="text-center">No doctors found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($doctors, 'links')) {{ $doctors->links() }} @endif
</div></div></section>
@endsection
