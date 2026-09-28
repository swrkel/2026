@extends('layouts.app')
@section('title', 'Vehicle Service')
@section('content')
<section class="content-header">
    <h1>Vehicle Service <small>Service jobs and billing</small></h1>
</section>
<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">Vehicle Service Jobs</h3>
            <div class="box-tools">
                <a href="{{ route('vehicleservice.jobs.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Service Bill</a>
            </div>
        </div>
        <div class="box-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-3"><input type="text" name="vehicle_no" class="form-control" placeholder="Vehicle No" value="{{ request('vehicle_no') }}"></div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        @foreach(['draft'=>'Draft','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v)
                            <option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><button class="btn btn-info">Search</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th><th>Job No</th><th>Vehicle No</th><th>Customer</th><th>Payment</th><th>Status</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td>{{ $job->transaction_date }}</td>
                            <td>{{ $job->job_no }}</td>
                            <td>{{ $job->vehicle_no }}</td>
                            <td>{{ $job->customer_name }}</td>
                            <td>{{ ucfirst($job->payment_type) }}</td>
                            <td>{{ ucfirst($job->status) }}</td>
                            <td class="text-right">{{ number_format($job->total_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($job->paid_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($job->balance_amount, 2) }}</td>
                            <td><a href="{{ route('vehicleservice.jobs.show', $job->id) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center">No service jobs found.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Page Total</th>
                            <th class="text-right">{{ number_format($jobs->sum('total_amount'), 2) }}</th>
                            <th class="text-right">{{ number_format($jobs->sum('paid_amount'), 2) }}</th>
                            <th class="text-right">{{ number_format($jobs->sum('balance_amount'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{ $jobs->links() }}
        </div>
    </div>
</section>
@endsection
