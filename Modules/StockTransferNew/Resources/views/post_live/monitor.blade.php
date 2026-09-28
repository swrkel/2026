@extends('layouts.app')

@section('title', __('stocktransfernew::post_live.monitor'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-post-live.css') }}">
<section class="stn-post-live">
    <div class="stn-page-header">
        <div>
            <h1>{{ __('stocktransfernew::post_live.monitor') }}</h1>
            <p>Live support view for Stock Transfer-New after production deployment.</p>
        </div>
        <a href="{{ url('stock-transfer-new') }}" class="btn btn-default">Back to Module</a>
    </div>

    <div class="stn-kpi-grid">
        <div class="stn-kpi"><span>Total</span><strong>{{ number_format($summary['total_transfers'] ?? 0) }}</strong></div>
        <div class="stn-kpi"><span>Draft</span><strong>{{ number_format($summary['draft_transfers'] ?? 0) }}</strong></div>
        <div class="stn-kpi"><span>Pending Approval</span><strong>{{ number_format($summary['pending_approval'] ?? 0) }}</strong></div>
        <div class="stn-kpi"><span>In Transit</span><strong>{{ number_format($summary['in_transit'] ?? 0) }}</strong></div>
        <div class="stn-kpi"><span>Completed</span><strong>{{ number_format($summary['completed'] ?? 0) }}</strong></div>
        <div class="stn-kpi"><span>Audit Events</span><strong>{{ number_format($summary['audit_events'] ?? 0) }}</strong></div>
    </div>

    <div class="stn-two-col">
        <div class="stn-card">
            <h3>{{ __('stocktransfernew::post_live.exceptions') }}</h3>
            @foreach($exceptions as $exception)
                <div class="stn-alert stn-alert-{{ $exception['level'] ?? 'info' }}">
                    <strong>{{ $exception['title'] ?? '' }}</strong>
                    <p>{{ $exception['message'] ?? '' }}</p>
                </div>
            @endforeach
        </div>

        <div class="stn-card">
            <h3>{{ __('stocktransfernew::post_live.table_status') }}</h3>
            <table class="table table-condensed stn-table">
                <tbody>
                    @foreach(($summary['tables_ready'] ?? []) as $table => $ready)
                        <tr>
                            <td>{{ $table }}</td>
                            <td><span class="label label-{{ $ready ? 'success' : 'danger' }}">{{ $ready ? 'Ready' : 'Missing' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="stn-card">
        <h3>{{ __('stocktransfernew::post_live.activity') }}</h3>
        <table class="table table-striped stn-table">
            <thead>
                <tr><th>Date</th><th>Action</th><th>Description</th><th>User</th></tr>
            </thead>
            <tbody>
                @forelse($activity as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['action'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>{{ $row['user_id'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No activity records available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew-post-live.js') }}"></script>
@endsection
