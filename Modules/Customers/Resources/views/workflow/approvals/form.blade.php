@extends('customers::layouts.app')
@section('title', $title ?? 'Customer Approval')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Customer Approval' }}</h1></section>
<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('customers.workflow.approvals.store') }}">
            @csrf
            <input type="hidden" name="workflow_type" value="{{ $workflowType }}">
            <div class="box-body">
                <div class="form-group">
                    <label>Customer</label>
                    <select name="contact_id" class="form-control select2" required>
                        <option value="">Please Select</option>
                        @foreach($customers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Requested Value</label>
                    <input type="text" name="requested_value" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label>Reason / Remarks</label>
                    <textarea name="reason" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Submit Approval Request</button>
                <a href="{{ route('customers.workflow.approvals.index') }}" class="btn btn-default">Back</a>
            </div>
        </form>
    </div>
</section>
@endsection
