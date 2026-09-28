@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Management <small>Final Readiness</small></h1>
</section>

<section class="content">
    @include('hotelmanagement::partials.nav')

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Overall Ready</div><div class="hm-kpi-value">{{ $readiness['summary']['percent'] }}%</div><div class="hm-kpi-sub">{{ $readiness['summary']['ok'] }}/{{ $readiness['summary']['total'] }} checks OK</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Routes</div><div class="hm-kpi-value">{{ $readiness['summary']['routes'] }}</div><div class="hm-kpi-sub">Page bindings</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Tables</div><div class="hm-kpi-value">{{ $readiness['summary']['tables'] }}</div><div class="hm-kpi-sub">Tenant database</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Permissions</div><div class="hm-kpi-value">{{ $readiness['summary']['permissions'] }}</div><div class="hm-kpi-sub">Access control</div></div></div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Final Production Checklist</h3></div>
        <div class="box-body">
            <div class="hm-toolbar">
                <input type="text" class="form-control hm-search-input" placeholder="Search final checks" style="max-width:260px;">
                <button type="button" class="btn hm-btn-export" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped hm-table">
                    <thead><tr><th>Area</th><th>Group</th><th>Name</th><th>Status</th><th>Note</th></tr></thead>
                    <tbody>
                        @foreach(array_merge($readiness['routes'], $readiness['tables'], $readiness['permissions'], $readiness['scopes']) as $row)
                            <tr>
                                <td>{{ $row['area'] }}</td>
                                <td>{{ $row['group'] }}</td>
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
        <div class="box-header with-border"><h3 class="box-title">Deployment Notes</h3></div>
        <div class="box-body">
            <ol class="hm-checklist">
                @foreach($readiness['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
@endsection
