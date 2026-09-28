@extends('autoservice::layouts.master')
@section('title','Auto Service Delivery')
@section('autoservice_content')
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">Vehicle Delivery Queue</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Job No</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Balance</th>
                    <th>Delivery Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td>{{ $job->job_no }}</td>
                        <td>{{ $job->registration_no ?? $job->vehicle_id }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $job->status)) }}</td>
                        <td>{{ number_format((float)($job->balance_amount ?? 0), 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('autoservice.deliveries.store') }}" class="form-inline">
                                @csrf
                                <input type="hidden" name="job_id" value="{{ $job->id }}">
                                <label class="checkbox-inline"><input type="checkbox" name="invoice_confirmed" value="1"> Invoice</label>
                                <label class="checkbox-inline"><input type="checkbox" name="payment_confirmed" value="1"> Payment</label>
                                <label class="checkbox-inline"><input type="checkbox" name="vehicle_handover_confirmed" value="1"> Handover</label>
                                <button type="submit" class="btn btn-success btn-sm">Deliver</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No vehicles ready for delivery.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
