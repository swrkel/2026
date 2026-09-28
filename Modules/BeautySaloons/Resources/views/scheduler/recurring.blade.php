@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <div class="bs-page-header"><h3>Recurring Appointments</h3></div>
    <div class="box box-solid"><div class="box-body">
        <form method="POST" action="{{ route('beautysaloons.recurring-appointments.store') }}" class="row">
            @csrf
            <div class="col-md-2"><input name="customer_id" class="form-control" placeholder="Customer ID"></div>
            <div class="col-md-2"><input name="service_id" class="form-control" placeholder="Service ID"></div>
            <div class="col-md-2"><input name="staff_id" class="form-control" placeholder="Staff ID"></div>
            <div class="col-md-2"><select name="frequency" class="form-control"><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
            <div class="col-md-2"><input name="starts_on" type="date" class="form-control" required></div>
            <div class="col-md-2"><button class="btn btn-primary btn-block">Save</button></div>
        </form>
    </div></div>
    <div class="box box-solid"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Customer</th><th>Service</th><th>Staff</th><th>Frequency</th><th>Starts</th><th>Next Run</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($templates as $row)
                <tr><td>{{ $row->id }}</td><td>{{ $row->customer_id }}</td><td>{{ $row->service_id }}</td><td>{{ $row->staff_id }}</td><td>{{ ucfirst($row->frequency) }}</td><td>{{ optional($row->starts_on)->format('Y-m-d') }}</td><td>{{ optional($row->next_run_date)->format('Y-m-d') }}</td><td>{{ ucfirst($row->status) }}</td></tr>
            @empty
                <tr><td colspan="8" class="text-center">No recurring appointment templates found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $templates->links() }}
    </div></div>
</div>
@endsection
