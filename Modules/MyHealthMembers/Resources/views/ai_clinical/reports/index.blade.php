@extends('layouts.app')
@section('title', 'AI Clinical Reports')
@section('content')
<section class="content-header"><h1>My Health <small>AI Clinical Reports</small></h1></section>
<section class="content">
    @include('myhealthmembers::ai_clinical._filters')
    @include('myhealthmembers::ai_clinical._kpi_cards')
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Available Reports</h3></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4"><a class="btn btn-block btn-primary" href="{{ route('myhealth.ai_clinical.alerts.index') }}"><i class="fa fa-exclamation-triangle"></i> Clinical Alert Report</a></div>
                <div class="col-md-4"><a class="btn btn-block btn-warning" href="{{ route('myhealth.ai_clinical.reports.medication_safety') }}"><i class="fa fa-medkit"></i> Medication Safety Report</a></div>
                <div class="col-md-4"><a class="btn btn-block btn-success" href="{{ route('myhealth.ai_clinical.reports.preventive_care') }}"><i class="fa fa-heartbeat"></i> Preventive Care Report</a></div>
            </div>
        </div>
    </div>
</section>
@endsection
