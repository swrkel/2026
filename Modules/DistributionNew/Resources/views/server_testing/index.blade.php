@extends('distributionnew::layouts.app')
@section('title', __('distributionnew::lang.server_testing'))
@section('content')
<div class="pos-page disnew-server-testing">
    <div class="pos-page-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">{{ __('distributionnew::lang.server_testing') }}</h3>
            <small class="text-muted">{{ __('distributionnew::lang.server_testing_subtitle') }}</small>
        </div>
        <div>
            <a href="{{ route('distributionnew.server-testing.download-checklist') }}" class="btn btn-outline-primary btn-sm">Checklist</a>
            <button class="btn btn-primary btn-sm" id="disnew-run-server-test">Run Full Check</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3"><div class="card pos-card"><div class="card-body"><h6>Missing Tables</h6><h3>{{ count($summary['missing_tables']) }}</h3></div></div></div>
        <div class="col-md-3"><div class="card pos-card"><div class="card-body"><h6>Missing Routes</h6><h3>{{ count($summary['missing_routes']) }}</h3></div></div></div>
        <div class="col-md-3"><div class="card pos-card"><div class="card-body"><h6>Business ID</h6><h3>{{ $summary['business_id'] }}</h3></div></div></div>
        <div class="col-md-3"><div class="card pos-card"><div class="card-body"><h6>Last Status</h6><h3>{{ optional($summary['last_run'])->overall_status ?? 'Not run' }}</h3></div></div></div>
    </div>

    <div class="card pos-card mt-3"><div class="card-body">
        <h5>Table Counts</h5>
        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Table</th><th>Rows</th></tr></thead><tbody>
        @foreach($summary['record_counts'] as $table => $count)
            <tr><td>{{ $table }}</td><td>{{ is_null($count) ? 'Missing' : number_format($count) }}</td></tr>
        @endforeach
        </tbody></table></div>
    </div></div>

    <pre id="disnew-server-test-output" class="mt-3 p-3 bg-light border rounded" style="display:none"></pre>
</div>
@endsection
@push('javascript')
<script src="{{ asset('modules/distributionnew/js/stage25_server_testing.js') }}"></script>
@endpush
