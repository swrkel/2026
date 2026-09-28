@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Management <small>Testing Readiness</small></h1>
</section>

<section class="content">
    @include('hotelmanagement::partials.nav')

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Tenant Business</div><div class="hm-kpi-value">{{ $summary['business_id'] ?: '-' }}</div><div class="hm-kpi-sub">Active business scope</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Location</div><div class="hm-kpi-value">{{ $summary['business_location_id'] ?: '-' }}</div><div class="hm-kpi-sub">Active location scope</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Tables Ready</div><div class="hm-kpi-value">{{ $summary['tables_ok'] }}/{{ $summary['tables_total'] }}</div><div class="hm-kpi-sub">Functional testing tables</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Routes Ready</div><div class="hm-kpi-value">{{ $summary['routes_ok'] }}/{{ $summary['routes_total'] }}</div><div class="hm-kpi-sub">Page bindings</div></div></div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Functional Testing Readiness</h3></div>
        <div class="box-body">
            <div class="hm-toolbar">
                <input type="text" class="form-control hm-search-input" placeholder="Search testing table" style="max-width:260px;">
                <button type="button" class="btn hm-btn-excel" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped hm-table">
                    <thead><tr><th>Area</th><th>Table</th><th>Status</th><th>Scoped Count</th><th>Scope Used</th></tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['area'] }}</td>
                                <td>{{ $row['table'] }}</td>
                                <td><span class="hm-badge {{ $row['exists'] ? 'active' : 'maintenance' }}">{{ $row['exists'] ? 'Ready' : 'Missing' }}</span></td>
                                <td>{{ $row['count'] === null ? '-' : $row['count'] }}</td>
                                <td>{{ $row['scoped'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Route Testing Links</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered hm-table">
                        <thead><tr><th>Route</th><th>Status</th><th>Open</th></tr></thead>
                        <tbody>
                            @foreach($routes as $route)
                                <tr>
                                    <td>{{ $route['name'] }}</td>
                                    <td><span class="hm-badge {{ $route['exists'] ? 'active' : 'maintenance' }}">{{ $route['exists'] ? 'Ready' : 'Missing' }}</span></td>
                                    <td>@if($route['url']) <a class="btn btn-xs hm-btn-primary" href="{{ $route['url'] }}">Open</a> @else - @endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Testing Note</h3></div>
                <form method="POST" action="{{ route('hotel-management.testing-readiness.record') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group"><label>Check Title</label><input type="text" name="check_title" class="form-control" value="Hotel module upload verification"></div>
                        <div class="form-group"><label>Status</label><select name="check_status" class="form-control"><option value="passed">Passed</option><option value="issue_found">Issue Found</option><option value="pending">Pending</option></select></div>
                        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="5" placeholder="Enter testing observations"></textarea></div>
                    </div>
                    <div class="box-footer"><button class="btn hm-btn-primary" type="submit"><i class="fa fa-save"></i> Save Note</button></div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
