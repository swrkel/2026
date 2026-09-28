@extends('leadsnew::layouts.app')
@section('title', 'Executive Lead Report')
@section('leadsnew_subtitle', 'A concise management view of lead volume, outcomes and campaign activity.')
@section('leadsnew_content')
<div class="ch-kpi-grid ln-four-kpis">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-users"></i></div><div class="label-text">Total Leads</div></div><div class="value">{{ number_format($stats['total'] ?? 0) }}</div><div class="hint">All lead records</div><div class="spark"></div></div>
    <div class="ch-kpi success"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-check"></i></div><div class="label-text">Converted</div></div><div class="value">{{ number_format($stats['converted'] ?? 0) }}</div><div class="hint">Successful outcomes</div><div class="spark"></div></div>
    <div class="ch-kpi danger"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-times"></i></div><div class="label-text">Lost</div></div><div class="value">{{ number_format($stats['lost'] ?? 0) }}</div><div class="hint">Closed without conversion</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-bullhorn"></i></div><div class="label-text">Campaigns</div></div><div class="value">{{ number_format($stats['campaigns'] ?? 0) }}</div><div class="hint">Campaign records</div><div class="spark"></div></div>
</div>
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-pie-chart"></i> Outcome Overview</h3><div class="ch-card-subtitle">Conversion and loss share calculated from the current lead total.</div></div></div><div class="ln-panel-body">
@php($total = max((int)($stats['total'] ?? 0), 1))
<div class="ln-progress-row"><span>Converted</span><div class="progress"><div class="progress-bar progress-bar-success" style="width:{{ round((($stats['converted'] ?? 0) / $total) * 100, 1) }}%"></div></div><strong>{{ round((($stats['converted'] ?? 0) / $total) * 100, 1) }}%</strong></div>
<div class="ln-progress-row"><span>Lost</span><div class="progress"><div class="progress-bar progress-bar-danger" style="width:{{ round((($stats['lost'] ?? 0) / $total) * 100, 1) }}%"></div></div><strong>{{ round((($stats['lost'] ?? 0) / $total) * 100, 1) }}%</strong></div>
</div></div>
@endsection
