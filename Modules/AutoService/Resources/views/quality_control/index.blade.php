@extends('autoservice::layouts.master')
@section('title','Auto Service Quality Control')
@section('autoservice_content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Quality Control Queue</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Job No</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>QC Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td>{{ $job->job_no }}</td>
                        <td>{{ $job->registration_no ?? $job->vehicle_id }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $job->status)) }}</td>
                        <td>{{ (int)($job->job_progress ?? 0) }}%</td>
                        <td>
                            <form method="POST" action="{{ route('autoservice.quality_control.store') }}" class="form-inline">
                                @csrf
                                <input type="hidden" name="job_id" value="{{ $job->id }}">
                                <label class="checkbox-inline"><input type="checkbox" name="mechanical_checked" value="1"> Mechanical</label>
                                <label class="checkbox-inline"><input type="checkbox" name="electrical_checked" value="1"> Electrical</label>
                                <label class="checkbox-inline"><input type="checkbox" name="road_test_done" value="1"> Road Test</label>
                                <label class="checkbox-inline"><input type="checkbox" name="wash_done" value="1"> Wash</label>
                                <select name="status" class="form-control input-sm">
                                    <option value="passed">Passed</option>
                                    <option value="rework">Needs Rework</option>
                                </select>
                                <button type="submit" class="btn btn-success btn-sm">Save QC</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No jobs waiting for quality control.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
