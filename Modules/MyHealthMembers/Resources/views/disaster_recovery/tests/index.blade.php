@extends('layouts.app')
@section('title', 'My Health Recovery Tests')
@section('content')
<section class="content-header"><h1>My Health <small>Recovery Testing</small></h1></section>
<section class="content">
@include('myhealthmembers::disaster_recovery._nav')
<div class="alert alert-info">Use this page to record and review scheduled disaster recovery tests. Actual restore execution should remain restricted to system administrators.</div>
<div class="box box-info"><div class="box-header"><h3 class="box-title">Recovery Test History</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Type</th><th>Status</th><th>Tested At</th><th>Duration</th><th>Summary</th><th>Recommendations</th></tr></thead><tbody>
@forelse($tests as $test)<tr><td>{{ $test->test_no }}</td><td>{{ ucfirst($test->test_type) }}</td><td>{{ ucfirst($test->status) }}</td><td>{{ optional($test->tested_at)->format('Y-m-d H:i') }}</td><td>{{ $test->duration_seconds }}</td><td>{{ $test->result_summary }}</td><td>{{ $test->recommendations }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No recovery tests found.</td></tr>@endforelse
</tbody></table>{{ $tests->links() }}</div></div>
</section>
@endsection
