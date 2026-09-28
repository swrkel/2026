@extends('layouts.app')
@section('title', 'My Health Reports')
@section('content')
<section class="content-header">
    <h1>My Health Reports</h1>
</section>
<section class="content">
    @include('myhealthmembers::reports._filters')
    <div class="row">
        @foreach([
            ['Members', $summary['members'] ?? 0, 'fa-users', 'bg-aqua'],
            ['Prescriptions', $summary['prescriptions'] ?? 0, 'fa-file-text-o', 'bg-green'],
            ['Labs', $summary['labs'] ?? 0, 'fa-flask', 'bg-yellow'],
            ['Dispenses', $summary['dispenses'] ?? 0, 'fa-medkit', 'bg-purple'],
            ['Claims', $summary['claims'] ?? 0, 'fa-shield', 'bg-red'],
            ['Telemedicine', $summary['telemedicine'] ?? 0, 'fa-video-camera', 'bg-teal'],
            ['Revenue', number_format($summary['revenue'] ?? 0, 4), 'fa-money', 'bg-maroon']
        ] as $box)
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon {{ $box[3] }}"><i class="fa {{ $box[2] }}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $box[0] }}</span>
                        <span class="info-box-number">{{ $box[1] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Module Reports</h3></div>
        <div class="box-body">
            <a class="btn btn-app" href="{{ route('myhealth.reports.patient_history') }}"><i class="fa fa-history"></i>Patient History</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.prescriptions') }}"><i class="fa fa-file-text-o"></i>Prescriptions</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.labs') }}"><i class="fa fa-flask"></i>Lab Report</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.dispenses') }}"><i class="fa fa-medkit"></i>Dispenses</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.claims') }}"><i class="fa fa-shield"></i>Claims</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.telemedicine') }}"><i class="fa fa-video-camera"></i>Telemedicine</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.revenue') }}"><i class="fa fa-money"></i>Revenue</a>
            <a class="btn btn-app" href="{{ route('myhealth.reports.doctor_performance') }}"><i class="fa fa-user-md"></i>Doctor Performance</a>
        </div>
    </div>
</section>
@endsection
