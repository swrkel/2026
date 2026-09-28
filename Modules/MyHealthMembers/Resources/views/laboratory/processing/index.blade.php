@extends('layouts.app')
@section('title', 'Laboratory Processing')
@section('content')
<section class="content-header"><h1>Laboratory Processing & Verification</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Results</h3><div class="box-tools"><a href="{{ route('myhealth.laboratory.processing.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Enter Result</a></div></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Sample ID</th><th>Test ID</th><th>Member ID</th><th>Result</th><th>Reference</th><th>Abnormal</th><th>Critical</th><th>Status</th></tr></thead><tbody>
@forelse($results as $result)<tr><td>{{ $result->sample_id }}</td><td>{{ $result->test_id }}</td><td>{{ $result->member_id }}</td><td>{{ $result->result_value }} {{ $result->unit }}</td><td>{{ $result->reference_range }}</td><td>{{ $result->is_abnormal ? 'Yes' : 'No' }}</td><td>{{ $result->is_critical ? 'Yes' : 'No' }}</td><td>{{ ucfirst($result->status) }}</td></tr>@empty<tr><td colspan="8" class="text-center">No results entered.</td></tr>@endforelse
</tbody></table>{{ $results->links() }}</div></div>
</section>
@endsection
