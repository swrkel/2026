@extends('autoservice::layouts.master')
@section('content')
@include('autoservice::layouts.nav')
<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">Service Flow Control</h3>
        <div class="box-tools pull-right">
            <form method="get" class="form-inline">
                <select name="status" class="form-control input-sm">
                    <option value="">All Status</option>
                    @foreach(['assigned','in_progress','repair_completed','quality_check','ready','delivered'] as $st)
                        <option value="{{ $st }}" @selected(request('status')===$st)>{{ ucwords(str_replace('_',' ', $st)) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="box-body">
        @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Technicians</th>
                        <th>Inspection</th>
                        <th>QC</th>
                        <th>Delivery</th>
                        <th style="min-width:320px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td><strong>{{ $job->job_no ?? ('JOB-'.$job->id) }}</strong><br><small>#{{ $job->id }}</small></td>
                        <td>{{ $job->registration_no ?? '-' }}</td>
                        <td><span class="label label-info">{{ ucwords(str_replace('_',' ', $job->status ?? 'draft')) }}</span></td>
                        <td>
                            <div class="progress progress-xs"><div class="progress-bar progress-bar-success" style="width: {{ (int)($job->job_progress ?? 0) }}%"></div></div>
                            <small>{{ (int)($job->job_progress ?? 0) }}%</small>
                        </td>
                        <td>{{ $job->completed_mechanics ?? 0 }} / {{ $job->assigned_mechanics ?? 0 }}</td>
                        <td>{{ $job->inspection_count ?? 0 }} checkpoint(s)</td>
                        <td>{{ $job->latest_qc_status ?? ($job->qc_status ?? '-') }}</td>
                        <td>{{ $job->latest_delivery_status ?? ($job->delivery_status ?? '-') }}</td>
                        <td>
                            <button class="btn btn-xs btn-primary" data-toggle="collapse" data-target="#assign-{{ $job->id }}">Assign</button>
                            <button class="btn btn-xs btn-warning" data-toggle="collapse" data-target="#inspect-{{ $job->id }}">Inspection</button>
                            <form method="post" action="{{ route('autoservice.service_flow.ready_for_qc', $job->id) }}" style="display:inline">@csrf
                                <button class="btn btn-xs btn-success" onclick="return confirm('Move this job to Quality Control?')">Send QC</button>
                            </form>
                            <a class="btn btn-xs btn-default" href="{{ route('autoservice.jobs.show', $job->id) }}">View</a>
                        </td>
                    </tr>
                    <tr class="collapse" id="assign-{{ $job->id }}"><td colspan="9">
                        <form method="post" action="{{ route('autoservice.service_flow.assign') }}" class="form-inline">@csrf
                            <input type="hidden" name="job_id" value="{{ $job->id }}">
                            <select name="mechanic_id" class="form-control input-sm" required>
                                <option value="">Select Technician</option>
                                @foreach($mechanics as $mechanic)
                                    <option value="{{ $mechanic->id }}">{{ $mechanic->name ?? ('Mechanic '.$mechanic->id) }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.25" name="estimated_hours" class="form-control input-sm" placeholder="Est. Hours">
                            <input type="text" name="note" class="form-control input-sm" placeholder="Note" style="min-width:240px">
                            <button class="btn btn-primary btn-sm">Save Assignment</button>
                        </form>
                    </td></tr>
                    <tr class="collapse" id="inspect-{{ $job->id }}"><td colspan="9">
                        <form method="post" action="{{ route('autoservice.service_flow.inspection') }}">@csrf
                            <input type="hidden" name="job_id" value="{{ $job->id }}">
                            <div class="row">
                                <div class="col-md-2"><input class="form-control input-sm" name="odometer" placeholder="Odometer"></div>
                                <div class="col-md-2"><input class="form-control input-sm" name="fuel_level" placeholder="Fuel Level"></div>
                                <div class="col-md-2"><select class="form-control input-sm" name="brake_condition"><option value="ok">Brake OK</option><option value="attention">Brake Attention</option></select></div>
                                <div class="col-md-2"><select class="form-control input-sm" name="tyre_condition"><option value="ok">Tyre OK</option><option value="attention">Tyre Attention</option></select></div>
                                <div class="col-md-2"><select class="form-control input-sm" name="engine_condition"><option value="ok">Engine OK</option><option value="attention">Engine Attention</option></select></div>
                                <div class="col-md-2"><select class="form-control input-sm" name="electrical_condition"><option value="ok">Electrical OK</option><option value="attention">Electrical Attention</option></select></div>
                            </div>
                            <br>
                            <textarea class="form-control input-sm" name="advisor_remarks" placeholder="Advisor remarks / inspection notes"></textarea><br>
                            <button class="btn btn-warning btn-sm">Save Inspection Checkpoint</button>
                        </form>
                    </td></tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No Auto Service jobs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
