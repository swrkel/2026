@extends('layouts.app')
@section('title', __('expensesnew::lang.reporting_centre'))
@section('content')
<section class="content-header"><h1>{{ __('expensesnew::lang.reporting_centre') }}</h1></section>
<section class="content expnew-page">
    <div class="expnew-command-grid">
        @foreach($reports as $code => $report)
            <a class="expnew-card" href="{{ route('expensesnew.reports.show', $code) }}">
                <h3>{{ $report['title'] ?? $code }}</h3>
                <p>{{ __('expensesnew::lang.open_report') }}</p>
            </a>
        @endforeach
    </div>
</section>
@endsection
