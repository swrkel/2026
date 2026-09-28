@extends('customers::layouts.app')
@section('title', 'Customer Status Changes')
@section('content')
<section class="content-header"><h1>Customer Status Changes</h1></section>
<section class="content">
    <div class="box box-warning">
        <form method="POST" action="{{ route('customers.workflow.status.change') }}">
            @csrf
            <div class="box-body row">
                <div class="col-md-4"><label>Customer</label><select name="contact_id" class="form-control select2" required><option value="">Please Select</option>@foreach($customers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label>Status</label><select name="status" class="form-control" required><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option><option value="blocked">Blocked</option><option value="pending">Pending</option></select></div>
                <div class="col-md-5"><label>Remarks</label><input type="text" name="remarks" class="form-control"></div>
            </div>
            <div class="box-footer"><button class="btn btn-warning">Update Status</button></div>
        </form>
    </div>
    <div class="box box-solid"><div class="box-header"><h3 class="box-title">Status History</h3></div><div class="box-body table-responsive">@include('customers::workflow.history.table', ['history' => $history])</div></div>
</section>
@endsection
