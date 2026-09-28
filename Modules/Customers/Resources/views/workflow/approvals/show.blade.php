@extends('customers::layouts.app')
@section('title', 'Approval Details')
@section('content')
<section class="content-header"><h1>Approval Details</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-body">
        <table class="table table-bordered">
            <tr><th>ID</th><td>{{ $approval->id }}</td></tr>
            <tr><th>Workflow Type</th><td>{{ $approval->workflow_type }}</td></tr>
            <tr><th>Status</th><td>{{ $approval->status }}</td></tr>
            <tr><th>Current Value</th><td>{{ $approval->current_value }}</td></tr>
            <tr><th>Requested Value</th><td>{{ $approval->requested_value }}</td></tr>
            <tr><th>Reason</th><td>{{ $approval->reason }}</td></tr>
        </table>
    </div></div>
</section>
@endsection
