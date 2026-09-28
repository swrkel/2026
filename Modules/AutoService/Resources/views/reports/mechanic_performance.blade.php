@extends('autoservice::layouts.master')
@section('title','Mechanic Performance')
@section('autoservice_content')
<form class="form-inline" method="get">
    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
    <button class="btn btn-primary">Filter</button>
</form>
<br>
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped">
<tr><th>Mechanic</th><th>Mobile</th><th class="text-right">Assigned Jobs</th><th class="text-right">Completed Jobs</th></tr>
@foreach($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->mobile }}</td><td class="text-right">{{ $row->jobs_count }}</td><td class="text-right">{{ $row->completed_count }}</td></tr>@endforeach
</table>{{ $rows->appends(request()->query())->links() }}</div></div>
@endsection
