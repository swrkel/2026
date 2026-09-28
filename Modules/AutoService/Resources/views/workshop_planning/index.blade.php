@extends('autoservice::layouts.master')

@section('title', __('Auto Service - Workshop Planning'))

@section('content')
@include('autoservice::layouts.nav')

<section class="content-header">
    <h1>Workshop Planning <small>Bay, Technician, Parts and Capacity Planner</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Planning Date</h3>
        </div>
        <div class="box-body">
            <form method="get" class="form-inline">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>
                <button class="btn btn-primary btn-sm">Refresh</button>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach([
            'Open Jobs' => $capacity['open_jobs'],
            'Planned Jobs' => $capacity['planned_jobs'],
            'Technicians Planned' => $capacity['planned_technicians'],
            'Bays Reserved' => $capacity['planned_bays'],
            'Parts Reservations' => $capacity['reserved_parts'],
        ] as $label => $value)
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner"><h3>{{ $value }}</h3><p>{{ $label }}</p></div>
                <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title">Assign Technician Schedule</h3></div>
                <div class="box-body">
                    <form method="post" action="{{ route('autoservice.workshop_planning.technician_schedule.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6"><label>Job</label><select name="job_id" class="form-control" required>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_no ?? ('JOB-'.$job->id) }} - {{ $job->status }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label>Technician</label><select name="technician_id" class="form-control" required>@foreach($technicians as $tech)<option value="{{ $tech->id }}">{{ $tech->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label>Date</label><input type="date" name="planned_date" value="{{ $date }}" class="form-control" required></div>
                            <div class="col-md-4"><label>Start</label><input type="time" name="start_time" class="form-control"></div>
                            <div class="col-md-4"><label>End</label><input type="time" name="end_time" class="form-control"></div>
                            <div class="col-md-12"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        </div><br>
                        <button class="btn btn-success btn-sm">Save Technician Schedule</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">Reserve Bay</h3></div>
                <div class="box-body">
                    <form method="post" action="{{ route('autoservice.workshop_planning.bay_schedule.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6"><label>Job</label><select name="job_id" class="form-control" required>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_no ?? ('JOB-'.$job->id) }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label>Bay</label><select name="bay_id" class="form-control" required>@foreach($bays as $bay)<option value="{{ $bay->id }}">{{ $bay->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label>Date</label><input type="date" name="planned_date" value="{{ $date }}" class="form-control" required></div>
                            <div class="col-md-4"><label>Start</label><input type="time" name="start_time" class="form-control"></div>
                            <div class="col-md-4"><label>End</label><input type="time" name="end_time" class="form-control"></div>
                            <div class="col-md-12"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        </div><br>
                        <button class="btn btn-warning btn-sm">Reserve Bay</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Parts Reservation Planning</h3></div>
        <div class="box-body">
            <form method="post" action="{{ route('autoservice.workshop_planning.parts_reservation.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3"><label>Job</label><select name="job_id" class="form-control" required>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_no ?? ('JOB-'.$job->id) }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label>Part / Accessory</label><input name="part_name" class="form-control" required></div>
                    <div class="col-md-2"><label>Qty</label><input type="number" step="0.001" name="quantity" class="form-control" required></div>
                    <div class="col-md-2"><label>Required Date</label><input type="date" name="required_date" value="{{ $date }}" class="form-control" required></div>
                    <div class="col-md-2"><label>&nbsp;</label><button class="btn btn-info btn-block">Reserve</button></div>
                    <div class="col-md-12"><label>Notes</label><input name="notes" class="form-control"></div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Daily Plan Summary</h3></div>
        <div class="box-body table-responsive">
            <h4>Technician Schedule</h4>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Time</th><th>Technician</th><th>Job</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>@forelse($techSchedules as $row)<tr><td>{{ $row->start_time }} - {{ $row->end_time }}</td><td>{{ $row->technician_name }}</td><td>{{ $row->job_no }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->notes }}</td></tr>@empty<tr><td colspan="5" class="text-center">No technician schedules for this date.</td></tr>@endforelse</tbody>
            </table>
            <h4>Bay Schedule</h4>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Time</th><th>Bay</th><th>Job</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>@forelse($baySchedules as $row)<tr><td>{{ $row->start_time }} - {{ $row->end_time }}</td><td>{{ $row->bay_name }}</td><td>{{ $row->job_no }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->notes }}</td></tr>@empty<tr><td colspan="5" class="text-center">No bay reservations for this date.</td></tr>@endforelse</tbody>
            </table>
            <h4>Parts Reservations</h4>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Job</th><th>Part / Accessory</th><th>Qty</th><th>Required Date</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>@forelse($partsReservations as $row)<tr><td>{{ $row->job_no }}</td><td>{{ $row->part_name }}</td><td>{{ number_format($row->quantity, 3) }}</td><td>{{ $row->required_date }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->notes }}</td></tr>@empty<tr><td colspan="6" class="text-center">No parts reservations for this date.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</section>
@endsection
