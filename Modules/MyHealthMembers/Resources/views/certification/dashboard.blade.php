@extends('layouts.app')
@section('title', 'My Health Production Certification')

@section('content')
<section class="content-header">
    <h1>My Health <small>Enterprise Production Certification</small></h1>
</section>

<section class="content">
    @include('myhealthmembers::certification._nav')

    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Sections</span><span class="info-box-number">{{ $summary['total_sections'] }}</span></div></div></div>
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Ready</span><span class="info-box-number">{{ $summary['ready_sections'] }}</span></div></div></div>
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">Needs Review</span><span class="info-box-number">{{ $summary['pending_sections'] }}</span></div></div></div>
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-percent"></i></span><div class="info-box-content"><span class="info-box-text">Readiness Score</span><span class="info-box-number">{{ $summary['readiness_score'] }}%</span></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Production Readiness Summary</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Area</th><th>Description</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($summary['sections'] as $section)
                    <tr>
                        <td>{{ $section['name'] }}</td>
                        <td>{{ $section['description'] }}</td>
                        <td><span class="label label-{{ $section['status'] == 'Ready' ? 'success' : 'warning' }}">{{ $section['status'] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
