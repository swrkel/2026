@extends('communicationhub::layout')

@section('title', 'Communication Hub Readiness Check')

@section('content')
<div class="ch-page-wrap">
    <div class="ch-page-header">
        <div>
            <h1>Communication Hub Readiness Check</h1>
            <p>Tenant/business deployment validation for routes, SQL tables, permissions and rollout checks.</p>
        </div>
        <div class="ch-header-actions">
            <a href="{{ route('communicationhub.dashboard') }}" class="btn btn-light btn-sm">Back to Dashboard</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="ch-stat-card">
                <span>Tables Ready</span>
                <strong>{{ collect($tableChecks)->where('status', true)->count() }} / {{ count($tableChecks) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ch-stat-card">
                <span>Routes Ready</span>
                <strong>{{ collect($routeChecks)->where('status', true)->count() }} / {{ count($routeChecks) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ch-stat-card">
                <span>Permission Keys</span>
                <strong>{{ $permissionCount }}</strong>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Required Tenant Tables</h3></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ch-table">
                        <thead><tr><th>Table</th><th style="width:120px;">Status</th></tr></thead>
                        <tbody>
                        @foreach($tableChecks as $check)
                            <tr>
                                <td>{{ $check['name'] }}</td>
                                <td>{!! $check['status'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><h3>Required Routes</h3></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ch-table">
                        <thead><tr><th>Route Name</th><th style="width:120px;">Status</th></tr></thead>
                        <tbody>
                        @foreach($routeChecks as $check)
                            <tr>
                                <td>{{ $check['name'] }}</td>
                                <td>{!! $check['status'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="ch-card mt-3">
        <div class="ch-card-header"><h3>Upload Verification Steps</h3></div>
        <ol class="mb-0">
            <li>Upload the replacement <code>CommunicationHub</code> module folder.</li>
            <li>Run the current stage SQL and the master SQL in every tenant database where Communication Hub is enabled.</li>
            <li>Clear Laravel caches: route, config and view.</li>
            <li>Open this page again and confirm all required tables and routes show Ready.</li>
            <li>Test SMS, OTP, Email, WhatsApp, Push, In-App, Chat, Automation, Workflow and Analytics from the UI.</li>
        </ol>
    </div>
</div>
@endsection
