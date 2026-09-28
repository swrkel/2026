@extends('customers::layouts.app')
@section('title', $title ?? 'Customer Approvals')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Customer Approvals' }}</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Approval List</h3>
            @if(request()->routeIs('customers.workflow.approvals.index'))
                <a href="{{ route('customers.workflow.approvals.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> New Approval</a>
            @endif
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped customer-workflow-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Customer Code</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Current</th>
                        <th>Requested</th>
                        <th>Requested By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $approval)
                        <tr>
                            <td>{{ $approval->customer_name }}</td>
                            <td>{{ $approval->customer_code }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $approval->workflow_type)) }}</td>
                            <td><span class="label label-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($approval->status) }}</span></td>
                            <td>{{ $approval->current_value }}</td>
                            <td>{{ $approval->requested_value }}</td>
                            <td>{{ trim($approval->requested_by_name) }}</td>
                            <td>{{ $approval->created_at }}</td>
                            <td>
                                @if($approval->status == 'pending')
                                    <form method="POST" action="{{ $approval->workflow_type == 'credit_approval' ? route('customers.workflow.credit_approvals.approve', $approval->id) : route('customers.workflow.approvals.approve', $approval->id) }}" style="display:inline">@csrf<button class="btn btn-xs btn-success">Approve</button></form>
                                    <form method="POST" action="{{ $approval->workflow_type == 'credit_approval' ? route('customers.workflow.credit_approvals.reject', $approval->id) : route('customers.workflow.approvals.reject', $approval->id) }}" style="display:inline">@csrf<button class="btn btn-xs btn-danger">Reject</button></form>
                                @else
                                    <span class="text-muted">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">No approvals found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
