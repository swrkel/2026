@extends('communicationhub::layout')
@section('communicationhub_title', 'Security & Monitoring Review')
@section('communicationhub_content')
<div class="row">
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">Security Checklist</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    @foreach($security as $item)
                        <tr><td>{{ $item['item'] }}</td><td>{!! $item['status'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Missing</span>' !!}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Monitoring Checklist</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    @foreach($monitoring as $item)
                        <tr><td>{{ $item['item'] }}</td><td>{!! $item['status'] ? '<span class="label label-success">OK</span>' : '<span class="label label-warning">Review</span>' !!}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
