@extends('autoservice::layouts.master')
@section('title','Maintenance Planner')
@section('autoservice_content')
<div class="row">
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['active'] }}</h3><p>Active Plans</p></div></div></div>
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['due_soon'] }}</h3><p>Due in 30 Days</p></div></div></div>
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['overdue'] }}</h3><p>Overdue</p></div></div></div>
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['completed'] }}</h3><p>Completed</p></div></div></div>
</div>

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Create Next Service Plan</h3></div>
    <form method="post" action="{{ route('autoservice.maintenance_planner.store') }}">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-2"><label>Vehicle ID</label><input name="vehicle_id" class="form-control" required></div>
                <div class="col-md-2"><label>Job ID</label><input name="job_id" class="form-control"></div>
                <div class="col-md-2"><label>Plan Type</label><select name="plan_type" class="form-control"><option value="periodic_service">Periodic Service</option><option value="oil_change">Oil Change</option><option value="tyre_rotation">Tyre Rotation</option><option value="inspection">Inspection</option><option value="custom">Custom</option></select></div>
                <div class="col-md-2"><label>Current Meter</label><input name="current_meter" class="form-control"></div>
                <div class="col-md-2"><label>Next Service Date</label><input type="date" name="next_service_date" class="form-control" required></div>
                <div class="col-md-2"><label>Next Meter</label><input name="next_service_meter" class="form-control"></div>
            </div>
            <div class="row" style="margin-top:10px;">
                <div class="col-md-2"><label>Interval Days</label><input type="number" name="interval_days" class="form-control" value="180"></div>
                <div class="col-md-2"><label>Interval Meter</label><input name="interval_meter" class="form-control" value="5000"></div>
                <div class="col-md-4"><label>Service Note</label><input name="service_note" class="form-control" placeholder="Next recommended service"></div>
                <div class="col-md-4"><label>Recommended Parts / Accessories</label><input name="recommended_parts" class="form-control" placeholder="Oil filter, air filter, brake pads..."></div>
            </div>
            <div class="checkbox"><label><input type="checkbox" name="customer_visible" value="1" checked> Show this plan in customer portal</label></div>
        </div>
        <div class="box-footer"><button class="btn btn-primary">Create Plan</button> <a href="{{ route('autoservice.maintenance_planner.vehicle_health') }}" class="btn btn-default">Vehicle Health Summary</a></div>
    </form>
</div>

<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Maintenance Plans</h3></div>
    <div class="box-body">
        <form method="get" class="row" style="margin-bottom:15px;">
            <div class="col-md-3"><input name="search" value="{{ $search }}" class="form-control" placeholder="Search plan, vehicle, customer, mobile"></div>
            <div class="col-md-2"><select name="status" class="form-control"><option value="">All Status</option>@foreach(['active','paused','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($status==$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
            <div class="col-md-3"><button class="btn btn-primary">Search</button> <a href="{{ route('autoservice.maintenance_planner.index') }}" class="btn btn-default">Reset</a></div>
        </form>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Plan No</th><th>Customer</th><th>Vehicle</th><th>Type</th><th>Next Date</th><th>Next Meter</th><th>Parts/Accessories</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($plans as $p)
                <tr>
                    <td>{{ $p->plan_no }}</td><td>{{ $p->customer_name }}<br><small>{{ $p->customer_mobile }}</small></td><td>{{ $p->registration_no }}<br><small>{{ $p->make }} {{ $p->model }}</small></td>
                    <td>{{ str_replace('_',' ',ucfirst($p->plan_type)) }}</td><td>{{ $p->next_service_date }}</td><td>{{ $p->next_service_meter }}</td><td>{{ $p->recommended_parts }}</td>
                    <td><span class="label label-info">{{ $p->status }}</span></td>
                    <td><form method="post" action="{{ route('autoservice.maintenance_planner.status', $p->id) }}" class="form-inline">@csrf<select name="status" class="form-control input-sm"><option value="active">Active</option><option value="paused">Paused</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><button class="btn btn-xs btn-primary">Update</button></form></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">No maintenance plans found.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $plans->links() }}
    </div>
</div>
@endsection
