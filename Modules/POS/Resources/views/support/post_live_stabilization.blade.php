@extends('pos::layouts.app', ['title' => 'POS Post-Live Stabilization'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-rocket"></i></div><div class="label-text">Status</div></div><div class="value">Live Test</div><div class="hint">Stabilization phase</div><div class="spark"></div></div>
    <div class="ch-kpi success"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-check"></i></div><div class="label-text">Focus</div></div><div class="value">Fix Fast</div><div class="hint">After server testing</div><div class="spark"></div></div>
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-print"></i></div><div class="label-text">Print</div></div><div class="value">Calibrate</div><div class="hint">Receipt/barcode printers</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-users"></i></div><div class="label-text">Customers</div></div><div class="value">Linked</div><div class="hint">Standalone Customers module</div><div class="spark"></div></div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-list-check text-primary"></i> Post-Live Priority Checks</h3>
            <div class="ch-card-subtitle">Use this page after uploading the latest POS package to the live server.</div>
        </div>
    </div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered pos-standard-table">
            <thead><tr><th style="width:160px;">Area</th><th>Check</th><th style="width:120px;">Priority</th></tr></thead>
            <tbody>
                @foreach($priorityChecks as $row)
                    <tr>
                        <td><strong>{{ $row['area'] }}</strong></td>
                        <td>{{ $row['check'] }}</td>
                        <td><span class="label {{ $row['priority'] == 'High' ? 'label-danger' : 'label-warning' }}">{{ $row['priority'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-bug text-danger"></i> Issue Reporting Template</h3><div class="ch-card-subtitle">Send these details for any problem found during testing.</div></div></div>
            <div class="ch-card-body">
                <ol style="line-height:2;font-size:14px;">
                    @foreach($issueTemplate as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-server text-success"></i> Deployment Reminder</h3><div class="ch-card-subtitle">Run after replacing files on the server.</div></div></div>
            <div class="ch-card-body">
                <pre style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:14px;">php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear</pre>
                <p class="text-muted" style="margin-top:10px;">Then hard refresh the browser using Ctrl + F5.</p>
            </div>
        </div>
    </div>
</div>
@endsection
