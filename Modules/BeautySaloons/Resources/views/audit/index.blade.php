@extends('beautysaloons::layout')

@section('beauty_content')
<div class="container-fluid bs-audit-page">
    <div class="row">
        <div class="col-md-12">
            <h3>Beauty Saloons Final Standalone Audit</h3>
            <p class="text-muted">Use this page before UAT to verify module-local controllers, services, routes, views, assets, migrations, permissions, utilities and reports.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Standalone Structure</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Path</th>
                        <th>Exists</th>
                        <th>File Count</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditRows as $row)
                        <tr>
                            <td>{{ $row['area'] }}</td>
                            <td><code>{{ $row['path'] }}</code></td>
                            <td>{{ $row['exists'] ? 'Yes' : 'No' }}</td>
                            <td>{{ $row['file_count'] }}</td>
                            <td><span class="badge {{ $row['status'] === 'Available' ? 'badge-success' : 'badge-warning' }}">{{ $row['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Release Readiness</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Checklist Item</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($readinessRows as $row)
                        <tr>
                            <td>{{ $row['item'] }}</td>
                            <td><span class="badge {{ $row['status'] === 'Pass' ? 'badge-success' : 'badge-warning' }}">{{ $row['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
