@extends('layouts.app')

@section('title', 'My Health Standalone Audit')

@section('content')
<section class="content-header">
    <h1>My Health Standalone Audit</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Passed</span>
                    <span class="info-box-number">{{ $audit['passed'] }} / {{ $audit['total'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-percent"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Completion</span>
                    <span class="info-box-number">{{ $audit['percentage'] }}%</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-heartbeat"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Status</span>
                    <span class="info-box-number">{{ $audit['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Module Independence Checklist</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 70%;">Area</th>
                        <th style="width: 30%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($audit['checks'] as $label => $passed)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                @if($passed)
                                    <span class="label label-success">Available</span>
                                @else
                                    <span class="label label-danger">Missing</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
