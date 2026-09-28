@extends('leadsnew::layouts.app')
@section('title', 'Lead Ageing Report')
@section('leadsnew_subtitle', 'Ageing analysis workspace for reviewing how long leads remain open in the pipeline.')
@section('leadsnew_content')
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-clock-o"></i> Lead Ageing</h3><div class="ch-card-subtitle">This report is isolated to Leads-New tables and ready for tenant data analysis.</div></div></div><div class="ln-panel-body"><div class="ln-empty"><i class="fa fa-bar-chart"></i>No ageing dataset is available for the selected period.</div></div></div>
@endsection
