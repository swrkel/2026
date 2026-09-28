@extends('communicationhub::layout')
@section('communicationhub_title', 'CommunicationHub Enterprise Certification')
@section('communicationhub_content')
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="box box-success">
            <div class="box-body text-center">
                <i class="fa fa-certificate fa-2x"></i>
                <h3>{{ $overall_score }}%</h3>
                <strong>Overall Readiness</strong>
            </div>
        </div>
    </div>
    <div class="col-md-9 col-sm-6">
        <div class="box box-primary">
            <div class="box-body">
                <h4>{{ $version }}</h4>
                <p>This page consolidates the final production certification checks for the standalone CommunicationHub platform.</p>
                <a href="{{ route('communicationhub.certification.standalone') }}" class="btn btn-primary btn-sm"><i class="fa fa-cubes"></i> Standalone</a>
                <a href="{{ route('communicationhub.certification.security') }}" class="btn btn-warning btn-sm"><i class="fa fa-shield"></i> Security</a>
                <a href="{{ route('communicationhub.certification.api') }}" class="btn btn-info btn-sm"><i class="fa fa-code"></i> API Gateway</a>
                <a href="{{ route('communicationhub.certification.release_notes') }}" class="btn btn-default btn-sm"><i class="fa fa-file-text-o"></i> Release Notes</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach($summary_cards as $card)
        <div class="col-md-3 col-sm-6">
            <div class="box box-solid {{ $card['score'] >= 80 ? 'box-success' : 'box-warning' }}">
                <div class="box-header with-border"><h3 class="box-title">{{ $card['title'] }}</h3></div>
                <div class="box-body text-center">
                    <h3>{{ $card['score'] }}%</h3>
                    <p>{{ $card['passed'] }} / {{ $card['total'] }} checks passed</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Certification Details</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 180px;">Section</th>
                    <th>Area</th>
                    <th style="width: 120px;">Status</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sections as $section => $checks)
                @foreach($checks as $check)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $section)) }}</td>
                        <td>{{ $check['area'] }}</td>
                        <td>{!! $check['status'] ? '<span class="label label-success">Passed</span>' : '<span class="label label-danger">Review</span>' !!}</td>
                        <td>{{ $check['note'] }}</td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
