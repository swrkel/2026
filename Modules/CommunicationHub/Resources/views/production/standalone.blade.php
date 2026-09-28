@extends('communicationhub::layout')
@section('communicationhub_title', 'Standalone Dependency Audit')
@section('communicationhub_content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">CommunicationHub Standalone Checklist</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Area</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
            @foreach($checks as $check)
                <tr>
                    <td>{{ $check['area'] }}</td>
                    <td>{!! $check['status'] ? '<span class="label label-success">Independent</span>' : '<span class="label label-warning">Review</span>' !!}</td>
                    <td>{{ $check['note'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
