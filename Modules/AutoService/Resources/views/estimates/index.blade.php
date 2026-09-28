@extends('autoservice::layouts.master')
@section('title','Auto Service Estimates')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Estimates / Quotations</h3><a href="{{ route('autoservice.estimates.create') }}" class="btn btn-success pull-right">Add Estimate</a></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date</th><th>Status</th><th>Total</th><th>Action</th></tr></thead><tbody>
@foreach($estimates as $row)<tr><td>{{ $row->estimate_no }}</td><td>{{ $row->estimate_date }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ number_format($row->total_amount,2) }}</td><td><a class="btn btn-xs btn-info" href="{{ route('autoservice.estimates.show',$row->id) }}">View</a> <a class="btn btn-xs btn-primary" href="{{ route('autoservice.estimates.edit',$row->id) }}">Edit</a></td></tr>@endforeach
</tbody></table>{{ $estimates->links() }}</div></div>
@endsection
