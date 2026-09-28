@extends('layouts.app')
@section('title', 'Immunization Schedules')
@section('content')
<section class="content-header"><h1>Immunization Schedules <a href="{{ route('myhealth.vaccination.schedules.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Type</th><th>Vaccine ID</th><th>Dose</th><th>Age</th><th>Mandatory</th><th>Status</th></tr></thead><tbody>
@forelse($schedules as $s)<tr><td>{{ $s->schedule_name }}</td><td>{{ $s->schedule_type }}</td><td>{{ $s->vaccine_id }}</td><td>{{ $s->dose_no }}</td><td>{{ $s->recommended_age_text }}</td><td>{{ $s->is_mandatory ? 'Yes' : 'No' }}</td><td>{{ ucfirst($s->status) }}</td></tr>@empty<tr><td colspan="7" class="text-center">No schedules found.</td></tr>@endforelse
</tbody></table>{{ $schedules->links() }}
</div></div></section>
@endsection
