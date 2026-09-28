@extends('layouts.app')
@section('title', $report['title'] ?? $code)
@section('content')
<section class="content-header"><h1>{{ $report['title'] ?? $code }}</h1></section>
<section class="content expnew-page">
    <div class="box expnew-box">
        <div class="box-header with-border expnew-toolbar">
            <a href="{{ route('expensesnew.reports.export', [$code, 'csv']) }}" class="btn btn-primary btn-sm">CSV</a>
            <a href="{{ route('expensesnew.reports.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
        <div class="box-body">
            <table id="expnew_report_table" class="table table-bordered table-striped">
                <thead><tr><th>{{ __('expensesnew::lang.report') }}</th><th>{{ __('expensesnew::lang.status') }}</th></tr></thead>
                <tbody><tr><td>{{ $report['title'] ?? $code }}</td><td>Ready</td></tr></tbody>
            </table>
        </div>
    </div>
</section>
@endsection
