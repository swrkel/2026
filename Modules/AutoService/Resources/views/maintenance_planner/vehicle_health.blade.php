@extends('autoservice::layouts.master')
@section('title','Vehicle Health Summary')
@section('autoservice_content')
<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Vehicle Health Summary</h3></div>
    <div class="box-body">
        <form method="get" class="row" style="margin-bottom:15px;">
            <div class="col-md-5"><input name="search" value="{{ $search }}" class="form-control" placeholder="Search vehicle, customer or mobile"></div>
            <div class="col-md-4"><button class="btn btn-primary">Search</button> <a href="{{ route('autoservice.maintenance_planner.vehicle_health') }}" class="btn btn-default">Reset</a> <a href="{{ route('autoservice.maintenance_planner.index') }}" class="btn btn-default">Maintenance Planner</a></div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Vehicle</th><th>Customer</th><th>Last Service</th><th>Open Job</th><th>Health Note</th></tr></thead>
                <tbody>
                @forelse($vehicles as $v)
                    @php
                        $days = $v->last_service_date ? \Carbon\Carbon::parse($v->last_service_date)->diffInDays(now()) : null;
                        $status = is_null($days) ? 'No service history' : ($days > 180 ? 'Service due / review required' : 'Normal');
                    @endphp
                    <tr><td>{{ $v->registration_no }}<br><small>{{ $v->make }} {{ $v->model }}</small></td><td>{{ $v->customer_name }}<br><small>{{ $v->customer_mobile }}</small></td><td>{{ $v->last_service_date ?: '-' }}</td><td>{{ $v->last_job_id ?: '-' }}</td><td>{{ $status }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No vehicles found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $vehicles->links() }}
    </div>
</div>
@endsection
