@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Medication Administration')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Medication Administration</h3><div class="box-tools"><a href="{{ route('myhealth.nursing.medications.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add</a></div></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Due Time</th><th>Member</th><th>Medicine</th><th>Dose</th><th>Status</th></tr></thead><tbody>
@forelse($records as $row)<tr>
<td>{{ $row->created_at }}</td><td>{{ optional($row->member)->name ?? $row->member_id }}</td><td>{{ $row->shift ?? $row->medicine_name ?? $row->nursing_diagnosis ?? $row->from_shift ?? '-' }}</td><td>{{ $row->note_type ?? $row->dose ?? $row->review_date ?? $row->to_shift ?? '-' }}</td><td>{{ ucfirst($row->status ?? '-') }}</td>
</tr>@empty<tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>@endforelse
</tbody></table>{{ $records->links() }}</div></div>
</section>
@endsection
