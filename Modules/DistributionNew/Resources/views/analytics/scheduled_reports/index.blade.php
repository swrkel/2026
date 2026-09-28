@extends('layouts.app')
@section('title', __('distributionnew::lang.scheduled_reports'))
@section('content')
@include('distributionnew::layouts.partials.header')
<section class="content disnew-pos-page">
    <div class="box box-primary disnew-pos-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('distributionnew::lang.scheduled_reports') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-datatable">
                <thead><tr><th>Report</th><th>Frequency</th><th>Channel</th><th>Next Run</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td>{{ $report->report_name }}</td><td>{{ $report->frequency }}</td><td>{{ $report->delivery_channel }}</td><td>{{ $report->next_run_at }}</td><td>{{ $report->status }}</td>
                        <td><form method="post" action="{{ route('distribution-new.analytics.scheduled-reports.queue', $report->id) }}">@csrf<button class="btn btn-primary btn-sm">Queue</button></form></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $reports->links() }}
        </div>
    </div>
</section>
@endsection
