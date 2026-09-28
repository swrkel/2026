@extends('layouts.app')
@section('title', 'Vaccination Register')
@section('content')
<section class="content-header"><h1>Vaccination Register <a href="{{ route('myhealth.vaccination.records.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Member ID</th><th>Vaccine ID</th><th>Dose</th><th>Date Given</th><th>Next Due</th><th>Status</th><th>Certificate</th></tr></thead><tbody>
@forelse($records as $r)<tr><td>{{ $r->vaccination_no }}</td><td>{{ $r->member_id }}</td><td>{{ $r->vaccine_id }}</td><td>{{ $r->dose_no }}</td><td>{{ optional($r->date_given)->format('Y-m-d') }}</td><td>{{ optional($r->next_due_date)->format('Y-m-d') }}</td><td>{{ ucfirst($r->status) }}</td><td><a class="btn btn-xs btn-success" href="{{ route('myhealth.vaccination.records.certificate', $r->id) }}">Certificate</a></td></tr>@empty<tr><td colspan="8" class="text-center">No records found.</td></tr>@endforelse
</tbody></table>{{ $records->links() }}
</div></div></section>
@endsection
