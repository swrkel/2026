@extends('autoservice::layouts.master')
@section('title','Parts & Labour')
@section('autoservice_content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Parts & Labour Control</h3>
        <div class="box-tools pull-right"><a href="{{ route('autoservice.jobs.index') }}" class="btn btn-default btn-sm">Job Cards</a></div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Job No</th><th>Vehicle</th><th>Status</th><th>Parts Total</th><th>Labour Total</th><th>Job Total</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($jobs as $job)
                @php
                    $partsTotal = $job->lines->where('line_type','part')->sum('line_total');
                    $labourTotal = $job->lines->where('line_type','labour')->sum('line_total');
                @endphp
                <tr>
                    <td>{{ $job->job_no }}</td>
                    <td>{{ optional($job->vehicle)->registration_no }}</td>
                    <td><span class="label label-info">{{ ucwords(str_replace('_',' ', $job->status)) }}</span></td>
                    <td class="text-right">{{ number_format($partsTotal, 2) }}</td>
                    <td class="text-right">{{ number_format($labourTotal, 2) }}</td>
                    <td class="text-right"><b>{{ number_format($job->total_amount, 2) }}</b></td>
                    <td><a class="btn btn-primary btn-xs" href="{{ route('autoservice.parts_labour.edit', $job->id) }}">Manage</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No job cards found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
