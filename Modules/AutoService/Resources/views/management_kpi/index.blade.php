@extends('autoservice::layouts.master')
@section('title','Workshop Management KPI')
@section('autoservice_content')
<div class="row">
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['total_jobs'] }}</h3><p>Total Jobs</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['open_jobs'] }}</h3><p>Open Jobs</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['completed_jobs'] }}</h3><p>Completed</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['pending_qc'] }}</h3><p>Pending QC</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['repeat_repairs'] }}</h3><p>Repeat Repairs</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ number_format((float)$stats['revenue'], 2) }}</h3><p>Revenue</p></div></div></div>
</div>

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Management Filters</h3></div>
    <div class="box-body">
        <form method="get" class="row">
            <div class="col-md-3"><label>Search</label><input name="search" value="{{ $search }}" class="form-control" placeholder="Job no, customer, mobile, vehicle"></div>
            <div class="col-md-2"><label>Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['received','diagnosis','in_progress','hold','ready_for_qc','completed','delivered','cancelled'] as $s)<option value="{{ $s }}" @selected($status==$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label>From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
            <div class="col-md-2"><label>To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
            <div class="col-md-3" style="padding-top:25px;"><button class="btn btn-primary">Search</button> <a href="{{ route('autoservice.management_kpi.index') }}" class="btn btn-default">Reset</a></div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Technician Performance</h3></div><div class="box-body table-responsive">
            <table class="table table-bordered table-striped"><thead><tr><th>Technician</th><th>Assigned Jobs</th><th>Completed Jobs</th><th>Completion %</th></tr></thead><tbody>
            @forelse($technicians as $t)<tr><td>{{ $t->name ?: 'Not Assigned' }}</td><td>{{ $t->assigned_jobs }}</td><td>{{ $t->completed_jobs }}</td><td>{{ $t->assigned_jobs ? number_format(($t->completed_jobs / $t->assigned_jobs) * 100, 2) : '0.00' }}%</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No technician data found.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Bay Utilisation</h3></div><div class="box-body table-responsive">
            <table class="table table-bordered table-striped"><thead><tr><th>Bay</th><th>Total Allocations</th><th>Currently Occupied</th></tr></thead><tbody>
            @forelse($bayUtilisation as $b)<tr><td>{{ $b->name ?: 'Unassigned Bay' }}</td><td>{{ $b->allocations }}</td><td>{{ $b->currently_occupied }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">No bay utilisation data found.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
</div>

<div class="box box-warning">
    <div class="box-header with-border"><h3 class="box-title">Record Repeat Repair / Comeback</h3></div>
    <form method="post" action="{{ route('autoservice.management_kpi.repeat_repairs.store') }}">@csrf
        <div class="box-body"><div class="row">
            <div class="col-md-2"><label>Job ID</label><input name="job_id" class="form-control" required></div>
            <div class="col-md-2"><label>Severity</label><select name="severity" class="form-control"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
            <div class="col-md-4"><label>Reason</label><input name="reason" class="form-control" required placeholder="Customer returned / same issue repeated"></div>
            <div class="col-md-4"><label>Corrective Action</label><input name="corrective_action" class="form-control" placeholder="What was done to correct"></div>
        </div></div>
        <div class="box-footer"><button class="btn btn-warning">Record Repeat Repair</button></div>
    </form>
</div>

<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Pending Jobs Aging / Management Job Board</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Job No</th><th>Date</th><th>Age</th><th>Customer</th><th>Vehicle</th><th>Status</th><th>Estimate</th><th>Total</th></tr></thead>
            <tbody>
            @forelse($jobs as $j)
                @php $age = $j->created_at ? \Carbon\Carbon::parse($j->created_at)->diffInDays(now()) : 0; @endphp
                <tr>
                    <td>{{ $j->job_no ?? $j->id }}</td><td>{{ $j->created_at ? date('Y-m-d', strtotime($j->created_at)) : '' }}</td><td><span class="label {{ $age > 7 ? 'label-danger' : ($age > 3 ? 'label-warning' : 'label-info') }}">{{ $age }} days</span></td>
                    <td>{{ $j->customer_name }}<br><small>{{ $j->customer_mobile }}</small></td><td>{{ $j->registration_no }}<br><small>{{ $j->make }} {{ $j->model }}</small></td><td>{{ ucwords(str_replace('_',' ', $j->status ?? '')) }}</td><td>{{ number_format((float)($j->estimate_total ?? 0), 2) }}</td><td>{{ number_format((float)($j->total_amount ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No jobs found for the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
