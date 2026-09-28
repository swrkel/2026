@extends('communicationhub::layout')

@section('title', 'Communication Hub Production QA')

@section('content')
<div class="ch-page-wrap">
    <div class="ch-page-header">
        <div>
            <h1>Communication Hub Production QA</h1>
            <p>Final post-deployment validation for tenant tables, route/menu readiness, business scoping, queues and failed messages.</p>
        </div>
        <div class="ch-header-actions">
            <a href="{{ route('communicationhub.diagnostics.index') }}" class="btn btn-light btn-sm">Diagnostics</a>
            <a href="{{ route('communicationhub.excellence.executive_centre') }}" class="btn btn-primary btn-sm">Executive Centre</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3"><div class="ch-stat-card"><span>Readiness Score</span><strong>{{ $readinessScore }}%</strong></div></div>
        <div class="col-md-3"><div class="ch-stat-card"><span>Tenant DB</span><strong>{{ $database }}</strong></div></div>
        <div class="col-md-3"><div class="ch-stat-card"><span>Business ID</span><strong>{{ $businessId ?: 'All / Not Set' }}</strong></div></div>
        <div class="col-md-3"><div class="ch-stat-card"><span>Checked At</span><strong>{{ $checkedAt->format('Y-m-d H:i') }}</strong></div></div>
    </div>

    <div class="ch-card mt-3">
        <div class="ch-card-header"><h3>Tenant Table Validation</h3></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped ch-table">
                <thead><tr><th>Table</th><th>Exists</th><th>Business Scope</th><th>Total Rows</th><th>Business Rows</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($tableChecks as $row)
                    <tr>
                        <td>{{ $row['table'] }}</td>
                        <td>{!! $row['exists'] ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>' !!}</td>
                        <td>{!! $row['has_business_id'] ? '<span class="label label-success">business_id</span>' : '<span class="label label-warning">Check</span>' !!}</td>
                        <td>{{ $row['rows'] ?? '-' }}</td>
                        <td>{{ $row['business_rows'] ?? '-' }}</td>
                        <td>{{ strtoupper($row['status']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="ch-card mt-3">
        <div class="ch-card-header"><h3>Route Validation</h3></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped ch-table">
                <thead><tr><th>Route Name</th><th>Status</th><th>URL</th></tr></thead>
                <tbody>
                @foreach($routeChecks as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{!! $row['exists'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                        <td>{{ $row['url'] ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Queue Summary</h3></div>
                <div class="table-responsive"><table class="table table-bordered ch-table">
                    <thead><tr><th>Channel</th><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                    @forelse($queueSummary as $row)
                        <tr><td>{{ $row['channel'] }}</td><td>{{ $row['status'] }}</td><td>{{ $row['total'] }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No queue data found.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Recent Failed/Error Messages</h3></div>
                <div class="table-responsive"><table class="table table-bordered ch-table">
                    <thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                    @forelse($errorSummary as $row)
                        <tr>
                            <td>{{ $row['id'] ?? '-' }}</td>
                            <td>{{ $row['channel'] ?? '-' }}</td>
                            <td>{{ $row['recipient'] ?? ($row['to'] ?? '-') }}</td>
                            <td>{{ $row['status'] ?? '-' }}</td>
                            <td>{{ $row['created_at'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No failed/error messages found.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="ch-card mt-3">
        <div class="ch-card-header"><h3>Recent Audit Entries</h3></div>
        <div class="table-responsive"><table class="table table-bordered table-striped ch-table">
            <thead><tr><th>ID</th><th>Action</th><th>Channel</th><th>User</th><th>Created</th></tr></thead>
            <tbody>
            @forelse($recentAudits as $row)
                <tr><td>{{ $row['id'] ?? '-' }}</td><td>{{ $row['action'] ?? ($row['event'] ?? '-') }}</td><td>{{ $row['channel'] ?? '-' }}</td><td>{{ $row['created_by'] ?? ($row['user_id'] ?? '-') }}</td><td>{{ $row['created_at'] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No audit entries found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
