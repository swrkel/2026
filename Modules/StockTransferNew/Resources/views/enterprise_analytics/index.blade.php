@extends('layouts.app')
@section('title', __('stocktransfernew::messages.enterprise_analytics'))

@section('content')
<section class="content-header stn044-header">
    <h1>{{ __('stocktransfernew::messages.enterprise_analytics') }}</h1>
</section>

<section class="content stn044-wrap">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="stn044-toolbar">
        <form method="GET" action="{{ route('stocktransfernew.enterprise-analytics.index') }}" class="stn044-filter-form">
            <input type="date" name="from" value="{{ $filters['from'] ?? now()->startOfMonth()->toDateString() }}">
            <input type="date" name="to" value="{{ $filters['to'] ?? now()->toDateString() }}">
            <input type="text" name="location_id" placeholder="Location ID" value="{{ $filters['location_id'] ?? '' }}">
            <input type="text" name="store_id" placeholder="Store ID" value="{{ $filters['store_id'] ?? '' }}">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a class="btn btn-default" href="{{ route('stocktransfernew.enterprise-analytics.export', request()->query()) }}">CSV</a>
        </form>
        <form method="POST" action="{{ route('stocktransfernew.enterprise-analytics.bottlenecks') }}">@csrf<button class="btn btn-warning">Detect Bottlenecks</button></form>
        <form method="POST" action="{{ route('stocktransfernew.enterprise-analytics.snapshot') }}">@csrf<button class="btn btn-success">Save Snapshot</button></form>
    </div>

    <div class="stn044-kpi-grid">
        @foreach($dashboard['kpis'] as $kpi)
            <div class="stn044-card">
                <div class="stn044-card-title">{{ $kpi['label'] }}</div>
                <div class="stn044-card-value">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">Transfer Efficiency</h3></div>
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        @foreach($dashboard['efficiency'] as $row)
                            <tr><td>{{ $row['label'] }}</td><td class="text-right">{{ $row['value'] }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">Open Exceptions</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Severity</th><th>Title</th><th>Recommended Action</th></tr></thead>
                        <tbody>
                        @forelse($dashboard['exceptions'] as $exception)
                            <tr><td>{{ $exception['severity'] }}</td><td>{{ $exception['title'] }}</td><td>{{ $exception['recommended_action'] }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center">No open exceptions</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">Location / Store Benchmarking</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn044-table">
                <thead><tr><th>From Location</th><th>To Location</th><th>Total</th><th>Completed</th></tr></thead>
                <tbody>
                @foreach($dashboard['benchmarking'] as $row)
                    <tr><td>{{ $row->from_location_id }}</td><td>{{ $row->to_location_id }}</td><td>{{ $row->total }}</td><td>{{ $row->completed }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/stocktransfernew/js/stn_044.js') }}"></script>
@endsection
