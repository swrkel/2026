@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section><section class="content">
@include('myhealthmembers::reports._filters')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Request No</th><th>Date</th><th>Member ID</th><th>Test</th><th>Status</th><th>Result</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->lab_request_no }}</td><td>{{ $row->request_date }}</td><td>{{ $row->member_id }}</td><td>{{ $row->test_name }}</td><td>{{ $row->status }}</td><td>{{ optional($row->result)->result_summary }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records found</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
