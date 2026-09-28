@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Management <small>Production Hardening</small></h1>
</section>

<section class="content">
    @include('hotelmanagement::partials.nav')

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Scoped Tables</div><div class="hm-kpi-value">{{ $audit['summary']['tables_scoped'] }}/{{ $audit['summary']['tables_total'] }}</div><div class="hm-kpi-sub">Tenant + business isolation</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Routes</div><div class="hm-kpi-value">{{ $audit['summary']['routes_ok'] }}/{{ $audit['summary']['routes_total'] }}</div><div class="hm-kpi-sub">Page binding readiness</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Indexes</div><div class="hm-kpi-value">{{ $audit['summary']['indexes_recommended'] }}</div><div class="hm-kpi-sub">Recommended optimizations</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">ERP Links</div><div class="hm-kpi-value">{{ $audit['summary']['integrations_ok'] }}/{{ $audit['summary']['integrations_total'] }}</div><div class="hm-kpi-sub">Integration tables found</div></div></div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Production Audit</h3></div>
        <div class="box-body">
            <div class="hm-toolbar">
                <input type="text" class="form-control hm-search-input" placeholder="Search audit results" style="max-width:260px;">
                <button type="button" class="btn hm-btn-export" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped hm-table">
                    <thead><tr><th>Area</th><th>Check</th><th>Status</th><th>Note</th></tr></thead>
                    <tbody>
                        @foreach(array_merge($audit['tableScope'], $audit['routes'], $audit['indexes'], $audit['integrations']) as $row)
                            <tr>
                                <td>{{ $row['area'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td><span class="hm-badge {{ $row['status'] ? 'active' : 'maintenance' }}">{{ $row['status'] ? 'OK' : 'Review' }}</span></td>
                                <td>{{ $row['note'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Safe Record Counts</h3></div>
        <div class="box-body">
            <div class="row">
                @foreach($audit['counts'] as $table => $count)
                    <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">{{ $table }}</div><div class="hm-kpi-value">{{ number_format($count) }}</div><div class="hm-kpi-sub">Current tenant database</div></div></div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
