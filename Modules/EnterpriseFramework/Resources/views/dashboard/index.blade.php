@extends('enterpriseframework::layout', ['title' => 'Enterprise Framework'])

@section('efw_content')
@include('enterpriseframework::components.toolbar')
<div class="row">
    @foreach($summary as $label => $value)
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-cubes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                    <span class="info-box-number">{{ $value }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Framework Status</h3></div>
    <div class="box-body">
        <p>This module provides shared reporting, dashboard, filter, export, print, schedule, notification and registry services for standalone ERP modules.</p>
        <p><strong>Mode:</strong> Read-only framework. It does not post, edit, delete, or replace operational module data.</p>
    </div>
</div>
@endsection
