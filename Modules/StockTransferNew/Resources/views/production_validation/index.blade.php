@extends('layouts.app')

@section('title', __('stocktransfernew::messages.production_validation'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_045.css') }}">
<section class="content-header stn45-header">
    <h1>{{ __('stocktransfernew::messages.production_validation') }}</h1>
    <p>Final production validation for tenant, business, location, store, product bridge, transfers, stock movements, permissions, and variances.</p>
</section>

<section class="content stn45-wrap">
    @if(session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <div class="stn45-toolbar">
        <form method="POST" action="{{ route('stocktransfernew.production-validation.run') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Run Validation</button>
        </form>
        <a href="{{ route('stocktransfernew.production-validation.export') }}" class="btn btn-success">Export CSV</a>
    </div>

    <div class="row stn45-cards">
        <div class="col-md-3"><div class="stn45-card"><span>Latest Status</span><strong>{{ optional($latest_run)->status ?? 'Not Run' }}</strong></div></div>
        <div class="col-md-3"><div class="stn45-card"><span>Critical</span><strong>{{ $open_critical }}</strong></div></div>
        <div class="col-md-3"><div class="stn45-card"><span>Failed</span><strong>{{ $open_failed }}</strong></div></div>
        <div class="col-md-3"><div class="stn45-card"><span>Warnings</span><strong>{{ $open_warnings }}</strong></div></div>
    </div>

    <div class="box stn45-box">
        <div class="box-header with-border"><h3 class="box-title">Open Validation Issues</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn45-table">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Message</th>
                        <th>Recommended Action</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issues as $issue)
                        <tr>
                            <td>{{ $issue->check_key }}</td>
                            <td>{{ $issue->title }}</td>
                            <td><span class="stn45-badge stn45-{{ $issue->severity }}">{{ strtoupper($issue->severity) }}</span></td>
                            <td>{{ $issue->message }}</td>
                            <td>{{ $issue->recommended_action }}</td>
                            <td>
                                <form method="POST" action="{{ route('stocktransfernew.production-validation.resolve', $issue->id) }}">
                                    @csrf
                                    <input type="hidden" name="resolved_note" value="Resolved after admin review">
                                    <button class="btn btn-xs btn-default">Mark Resolved</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No open validation issues.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="box stn45-box">
        <div class="box-header with-border"><h3 class="box-title">Recent Validation Runs</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>ID</th><th>Status</th><th>Total</th><th>Passed</th><th>Warning</th><th>Failed</th><th>Completed</th></tr></thead>
                <tbody>
                    @foreach($recent_runs as $run)
                        <tr>
                            <td>{{ $run->id }}</td>
                            <td>{{ $run->status }}</td>
                            <td>{{ $run->total_checks }}</td>
                            <td>{{ $run->passed_checks }}</td>
                            <td>{{ $run->warning_checks }}</td>
                            <td>{{ $run->failed_checks }}</td>
                            <td>{{ optional($run->completed_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stn_045.js') }}"></script>
@endsection
