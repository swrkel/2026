@extends('layouts.app')
@section('title', __('distributionnew::lang.audit_health_check'))

@section('content')
<section class="content-header disnew-pos-header">
    <h1>{{ __('distributionnew::lang.audit_health_check') }}</h1>
</section>
<section class="content disnew-pos-page">
    <div class="box disnew-pos-card">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('distributionnew::lang.latest_checks') }}</h3>
            <div class="box-tools">
                {!! Form::open(['route' => 'distributionnew.audit.run', 'method' => 'post']) !!}
                <button type="submit" class="btn btn-primary btn-sm">{{ __('distributionnew::lang.run_health_check') }}</button>
                {!! Form::close() !!}
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-datatable">
                <thead>
                    <tr>
                        <th>{{ __('distributionnew::lang.group') }}</th>
                        <th>{{ __('distributionnew::lang.check') }}</th>
                        <th>{{ __('distributionnew::lang.status') }}</th>
                        <th>{{ __('distributionnew::lang.message') }}</th>
                        <th>{{ __('distributionnew::lang.checked_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checks as $check)
                        <tr>
                            <td>{{ $check->check_group }}</td>
                            <td>{{ $check->check_key }}</td>
                            <td><span class="label label-{{ $check->status == 'pass' ? 'success' : ($check->status == 'fail' ? 'danger' : 'warning') }}">{{ ucfirst($check->status) }}</span></td>
                            <td>{{ $check->message }}</td>
                            <td>{{ optional($check->checked_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">{{ __('distributionnew::lang.no_records_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
