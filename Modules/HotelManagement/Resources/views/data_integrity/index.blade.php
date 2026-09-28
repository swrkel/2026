@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Management <small>Data Integrity</small></h1>
</section>

<section class="content">
    @include('hotelmanagement::partials.nav')

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Total Checks</div><div class="hm-kpi-value">{{ $integrity['summary']['total'] }}</div><div class="hm-kpi-sub">Integrity rules</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">OK</div><div class="hm-kpi-value">{{ $integrity['summary']['ok'] }}</div><div class="hm-kpi-sub">No issue found</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Review</div><div class="hm-kpi-value">{{ $integrity['summary']['review'] }}</div><div class="hm-kpi-sub">Needs checking</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Missing</div><div class="hm-kpi-value">{{ $integrity['summary']['missing'] }}</div><div class="hm-kpi-sub">Missing tables</div></div></div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border">
            <h3 class="box-title">Tenant Data Integrity Checks</h3>
        </div>
        <div class="box-body">
            <div class="hm-toolbar">
                <input type="text" class="form-control hm-search-input" placeholder="Search checks" style="max-width:260px;">
                <form method="POST" action="{{ route('hotel-management.data-integrity.snapshot') }}" style="display:inline-block;">
                    @csrf
                    <button type="submit" class="btn hm-btn-primary"><i class="fa fa-save"></i> Save Snapshot</button>
                </form>
                <button type="button" class="btn hm-btn-export" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped hm-table">
                    <thead><tr><th>Check</th><th>Table</th><th>Status</th><th>Records</th><th>Note</th></tr></thead>
                    <tbody>
                        @foreach($integrity['rows'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['table'] }}</td>
                                <td><span class="hm-badge {{ $row['status'] === 'ok' ? 'active' : 'maintenance' }}">{{ strtoupper($row['status']) }}</span></td>
                                <td>{{ is_null($row['count']) ? '-' : number_format($row['count']) }}</td>
                                <td>{{ $row['note'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Important Notes</h3></div>
        <div class="box-body">
            <ol class="hm-checklist">
                @foreach($integrity['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
@endsection
