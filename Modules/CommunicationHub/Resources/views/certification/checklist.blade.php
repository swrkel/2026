@extends('communicationhub::layout')
@section('communicationhub_title', $title)
@section('communicationhub_content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('communicationhub.certification.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead><tr><th>Area</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
                @foreach($checks as $check)
                    <tr>
                        <td>{{ $check['area'] }}</td>
                        <td>{!! $check['status'] ? '<span class="label label-success">Passed</span>' : '<span class="label label-warning">Needs Review</span>' !!}</td>
                        <td>{{ $check['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
