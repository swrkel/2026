@extends('bankingui::layouts.master')
@section('banking_content')
@include('bankingui::test_manager._nav')
<div class="bkg-header"><h3>Banking Test Manager & Coverage Dashboard</h3><p>Central workspace for Banking Suite UI/UAT tracking.</p></div>
<div class="row bkg-kpi-row">
    <div class="col-md-3"><div class="bkg-kpi"><span>Modules Ready</span><strong>{{ $data['modulesReady'] }}</strong></div></div>
    <div class="col-md-3"><div class="bkg-kpi"><span>Open Issues</span><strong>{{ $data['openIssues'] }}</strong></div></div>
    <div class="col-md-3"><div class="bkg-kpi"><span>Critical Issues</span><strong>{{ $data['criticalIssues'] }}</strong></div></div>
    <div class="col-md-3"><div class="bkg-kpi"><span>Overall UI %</span><strong>{{ $data['overallUi'] }}%</strong></div></div>
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Module Progress</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Module</th><th>Status</th><th>Development</th><th>UI Tested</th><th>UAT</th><th>Production</th></tr></thead><tbody>
@foreach($data['statuses'] as $row)
<tr><td>{{ $row->module_name }}</td><td><span class="label label-info">{{ $row->status }}</span></td><td>{{ $row->development_percent }}%</td><td>{{ $row->ui_tested_percent }}%</td><td>{{ $row->uat_percent }}%</td><td>{{ $row->production_ready_percent }}%</td></tr>
@endforeach
</tbody></table></div></div>
<div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">Open Issues by Priority</h3></div><div class="box-body">
@foreach(['Critical','High','Medium','Low'] as $p)<span class="bkg-priority bkg-priority-{{ strtolower($p) }}">{{ $p }}: {{ $prioritySummary[$p] ?? 0 }}</span>@endforeach
</div></div>
@endsection
