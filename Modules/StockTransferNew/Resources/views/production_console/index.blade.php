@extends('layouts.app')

@section('title', __('stocktransfernew::messages.production_console'))

@section('content')
<section class="content-header stn-production-header">
    <h1>{{ __('stocktransfernew::messages.production_console') }}</h1>
    <p>{{ __('stocktransfernew::messages.production_console_help') }}</p>
</section>

<section class="content stn-production-console">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row stn-kpi-row">
        <div class="col-md-3"><div class="stn-kpi"><span>{{ $checks->where('status', 'passed')->count() }}</span><small>Passed</small></div></div>
        <div class="col-md-3"><div class="stn-kpi"><span>{{ $checks->where('status', 'warning')->count() }}</span><small>Warnings</small></div></div>
        <div class="col-md-3"><div class="stn-kpi"><span>{{ $checks->where('status', 'failed')->count() }}</span><small>Failed</small></div></div>
        <div class="col-md-3"><div class="stn-kpi"><span>{{ $signOffs->count() }}</span><small>Sign-offs</small></div></div>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border">
            <h3 class="box-title">Run Production Checks</h3>
        </div>
        <div class="box-body">
            <form method="POST" action="{{ route('stock-transfer-new.production-console.run') }}" class="form-inline">
                @csrf
                <input type="number" name="location_id" class="form-control" placeholder="Location ID (optional)">
                <input type="number" name="store_id" class="form-control" placeholder="Store ID (optional)">
                <button type="submit" class="btn btn-primary stn-btn">Run Checks</button>
            </form>
        </div>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border"><h3 class="box-title">Latest Checks</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-table">
                <thead>
                    <tr>
                        <th>Time</th><th>Group</th><th>Check</th><th>Status</th><th>Severity</th><th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checks as $check)
                        <tr>
                            <td>{{ optional($check->checked_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $check->check_group }}</td>
                            <td>{{ $check->check_name }}</td>
                            <td><span class="stn-badge stn-{{ $check->status }}">{{ ucfirst($check->status) }}</span></td>
                            <td>{{ ucfirst($check->severity) }}</td>
                            <td>{{ $check->message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No production checks found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border"><h3 class="box-title">Release Sign-off</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('stock-transfer-new.production-console.signoff') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="release_stage" class="form-control" placeholder="Stage e.g. UAT / Go Live" required></div>
                    <div class="col-md-3">
                        <select name="status" class="form-control" required>
                            <option value="approved">Approved</option>
                            <option value="approved_with_notes">Approved with Notes</option>
                            <option value="blocked">Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-4"><input name="remarks" class="form-control" placeholder="Remarks"></div>
                    <div class="col-md-2"><button class="btn btn-success stn-btn" type="submit">Save Sign-off</button></div>
                </div>
            </form>
            <hr>
            <table class="table table-bordered stn-table">
                <thead><tr><th>Time</th><th>Stage</th><th>Status</th><th>Remarks</th></tr></thead>
                <tbody>
                    @foreach($signOffs as $signOff)
                        <tr>
                            <td>{{ optional($signOff->signed_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $signOff->release_stage }}</td>
                            <td>{{ $signOff->status }}</td>
                            <td>{{ $signOff->remarks }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/stocktransfernew/js/stn_040.js') }}"></script>
@endsection
