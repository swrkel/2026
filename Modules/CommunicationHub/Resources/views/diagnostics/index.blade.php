@extends('communicationhub::layout')

@section('title', 'Communication Hub Diagnostics')

@section('content')
<div class="ch-page-wrap">
    <div class="ch-page-header">
        <div>
            <h1>Communication Hub Diagnostics</h1>
            <p>Server-side tenant/business troubleshooting for tables, queues, failed messages, OTP, providers and rollout checks.</p>
        </div>
        <div class="ch-header-actions">
            <a href="{{ route('communicationhub.readiness.index') }}" class="btn btn-light btn-sm">Readiness Check</a>
            <a href="{{ route('communicationhub.dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3"><div class="ch-stat-card"><span>Tenant DB</span><strong>{{ $database }}</strong></div></div>
        <div class="col-md-3"><div class="ch-stat-card"><span>Business ID</span><strong>{{ $businessId ?: 'All / Not Set' }}</strong></div></div>
        <div class="col-md-3"><div class="ch-stat-card"><span>Location ID</span><strong>{{ $locationId ?: 'All / Not Set' }}</strong></div></div>
        <div class="col-md-3"><div class="ch-stat-card"><span>Checked At</span><strong>{{ $checkedAt->format('Y-m-d H:i') }}</strong></div></div>
    </div>

    <div class="ch-card mt-3">
        <div class="ch-card-header"><h3>Tenant Table & Business Scope Status</h3></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped ch-table">
                <thead><tr><th>Table</th><th>Exists</th><th>Total Rows</th><th>Current Business Rows</th></tr></thead>
                <tbody>
                    @foreach($tableStatus as $row)
                        <tr>
                            <td>{{ $row['table'] }}</td>
                            <td>{!! $row['exists'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                            <td>{{ $row['rows'] ?? '-' }}</td>
                            <td>{{ $row['business_rows'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Message Channel / Status Summary</h3></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ch-table">
                        <thead><tr><th>Channel</th><th>Status</th><th>Total</th></tr></thead>
                        <tbody>
                        @forelse($messageStats as $row)
                            <tr><td>{{ $row['channel'] }}</td><td>{{ $row['status'] }}</td><td>{{ $row['total'] }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No message statistics available.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ch-card">
                <div class="ch-card-header"><h3>OTP Status</h3></div>
                <table class="table table-bordered ch-table"><tbody>
                @forelse($otpStats as $row)<tr><td>{{ $row['status'] }}</td><td>{{ $row['total'] }}</td></tr>@empty<tr><td class="text-muted">No OTP data</td></tr>@endforelse
                </tbody></table>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Provider Status</h3></div>
                <table class="table table-bordered ch-table"><tbody>
                @forelse($providerStats as $row)<tr><td>{{ $row['status'] }}</td><td>{{ $row['total'] }}</td></tr>@empty<tr><td class="text-muted">No provider status data</td></tr>@endforelse
                </tbody></table>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Recent Failed/Error Messages</h3></div>
                <div class="table-responsive"><table class="table table-bordered table-striped ch-table">
                    <thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                    @forelse($failedMessages as $row)
                        <tr><td>{{ $row['id'] ?? '-' }}</td><td>{{ $row['channel'] ?? '-' }}</td><td>{{ $row['recipient'] ?? ($row['to'] ?? '-') }}</td><td>{{ $row['status'] ?? '-' }}</td><td>{{ $row['created_at'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No failed messages found.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Recent Pending / Queued Messages</h3></div>
                <div class="table-responsive"><table class="table table-bordered table-striped ch-table">
                    <thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                    @forelse($pendingMessages as $row)
                        <tr><td>{{ $row['id'] ?? '-' }}</td><td>{{ $row['channel'] ?? '-' }}</td><td>{{ $row['recipient'] ?? ($row['to'] ?? '-') }}</td><td>{{ $row['status'] ?? '-' }}</td><td>{{ $row['created_at'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No pending messages found.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="ch-card mt-3">
        <div class="ch-card-header"><h3>Server Troubleshooting Notes</h3></div>
        <ol class="mb-0">
            <li>If a table is missing, run the current stage SQL and then the master Communication Hub SQL in the affected tenant database.</li>
            <li>If business rows are zero while total rows exist, verify selected business/location and whether the data was created under another business.</li>
            <li>If pending messages keep increasing, check provider credentials, queue processor route, cron/job configuration and failed queue table.</li>
            <li>If route/menu changes do not appear, clear Laravel route, config and view caches after upload.</li>
        </ol>
    </div>
</div>
@endsection
