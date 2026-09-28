@extends('autoservice::layouts.master')
@section('title','Workshop Operations')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Workshop Jobs</h3></div><div class="box-body"><table class="table table-bordered"><tr><th>Job No</th><th>Date</th><th>Status</th><th>Total</th><th>Mechanics</th><th>Parts Movements</th><th>Action</th></tr>@foreach($jobs as $j)<tr><td>{{ $j->job_no }}</td><td>{{ $j->job_date }}</td><td>{{ ucwords(str_replace('_',' ',$j->status)) }}</td><td>{{ number_format($j->total_amount,2) }}</td><td>{{ $j->mechanics->count() }}</td><td>{{ $j->partMovements->count() }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.workshop.job',$j->id) }}">Open</a></td></tr>@endforeach</table>{{ $jobs->links() }}</div></div>
@endsection
