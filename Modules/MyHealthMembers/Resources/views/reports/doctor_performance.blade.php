@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section><section class="content">
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Doctor</th><th>Specialization</th><th>Reg No</th><th>Schedules</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->doctor_name ?? $row->name ?? $row->id }}</td><td>{{ $row->specialization }}</td><td>{{ $row->registration_no }}</td><td>{{ $row->schedules_count ?? 0 }}</td><td>{{ $row->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
