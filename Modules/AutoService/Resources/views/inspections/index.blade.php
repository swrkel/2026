@extends('autoservice::layouts.master')
@section('title','Auto Service Inspections')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Digital Inspections</h3><a href="{{ route('autoservice.inspections.create') }}" class="btn btn-success pull-right">Add Inspection</a></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date</th><th>Status</th><th>Odometer</th><th>Action</th></tr></thead><tbody>
@foreach($inspections as $row)<tr><td>{{ $row->inspection_no }}</td><td>{{ $row->inspection_date }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->odometer }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.inspections.edit',$row->id) }}">Edit</a></td></tr>@endforeach
</tbody></table>{{ $inspections->links() }}</div></div>
@endsection
