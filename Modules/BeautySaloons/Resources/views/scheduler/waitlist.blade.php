@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <div class="bs-page-header"><h3>Appointment Waitlist</h3></div>
    <div class="box box-solid">
        <div class="box-body">
            <form method="POST" action="{{ route('beautysaloons.waitlist.store') }}" class="row">
                @csrf
                <div class="col-md-2"><input name="customer_id" class="form-control" placeholder="Customer ID"></div>
                <div class="col-md-2"><input name="service_id" class="form-control" placeholder="Service ID"></div>
                <div class="col-md-2"><input name="preferred_date" type="date" class="form-control"></div>
                <div class="col-md-2"><input name="preferred_start_time" type="time" class="form-control"></div>
                <div class="col-md-2"><select name="priority" class="form-control"><option value="normal">Normal</option><option value="high">High</option><option value="vip">VIP</option></select></div>
                <div class="col-md-2"><button class="btn btn-primary btn-block">Add</button></div>
            </form>
        </div>
    </div>
    <div class="box box-solid"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Time</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($waitlists as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->customer_id }}</td><td>{{ $row->service_id }}</td><td>{{ optional($row->preferred_date)->format('Y-m-d') }}</td><td>{{ $row->preferred_start_time }}</td><td>{{ ucfirst($row->priority) }}</td><td>{{ ucfirst($row->status) }}</td>
                    <td><form method="POST" action="{{ route('beautysaloons.waitlist.convert', $row->id) }}">@csrf<button class="btn btn-xs btn-success">Convert</button></form></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No waitlist entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $waitlists->links() }}
    </div></div>
</div>
@endsection
