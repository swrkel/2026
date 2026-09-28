@extends('communicationhub::layout')
@section('communicationhub_title', 'CommunicationHub Production Hardening')
@section('communicationhub_content')
<div class="row">
    @foreach([
        ['Standalone Score', $standalone_score, 'fa-cubes'],
        ['Security Score', $security_score, 'fa-shield'],
        ['Queue Score', $queue_score, 'fa-tasks'],
        ['Provider Score', $provider_score, 'fa-plug'],
    ] as $card)
        <div class="col-md-3 col-sm-6">
            <div class="box box-primary">
                <div class="box-body text-center">
                    <i class="fa {{ $card[2] }} fa-2x"></i>
                    <h3>{{ $card[1] }}%</h3>
                    <strong>{{ $card[0] }}</strong>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border"><h3 class="box-title">Queue Health</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                    @foreach($queue as $status => $count)
                        <tr><th>{{ ucfirst($status) }}</th><td class="text-right">{{ number_format($count) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Provider Health</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                    @foreach($providers as $status => $count)
                        <tr><th>{{ ucfirst($status) }}</th><td class="text-right">{{ number_format($count) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="box box-warning">
    <div class="box-header with-border"><h3 class="box-title">Production Checklist</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead><tr><th>Area</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
            @foreach($checks as $check)
                <tr>
                    <td>{{ $check['area'] }}</td>
                    <td>{!! $check['status'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Needs Attention</span>' !!}</td>
                    <td>{{ $check['note'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
