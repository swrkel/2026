@extends('layouts.app')
@section('title', __('distributionnew::lang.server_stabilization'))

@section('content')
<section class="content-header disnew-pos-header">
    <h1>{{ __('distributionnew::lang.server_stabilization') }}</h1>
</section>

<section class="content disnew-pos-page">
    <div class="box box-solid disnew-pos-card">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('distributionnew::lang.latest_server_checks') }}</h3>
            <div class="box-tools pull-right">
                {!! Form::open(['route' => 'distribution-new.stabilization.run-checks', 'method' => 'post']) !!}
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-refresh"></i> {{ __('distributionnew::lang.run_checks') }}</button>
                {!! Form::close() !!}
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-pos-table">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Repair Hint</th>
                        <th>Checked At</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($checks as $check)
                    <tr>
                        <td>{{ $check['check_name'] ?? '' }}</td>
                        <td><span class="label label-{{ ($check['status'] ?? '') === 'passed' ? 'success' : 'danger' }}">{{ ucfirst($check['status'] ?? '') }}</span></td>
                        <td>{{ $check['message'] ?? '' }}</td>
                        <td>{{ $check['repair_hint'] ?? '-' }}</td>
                        <td>{{ $check['checked_at'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Run checks to start server verification.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
