@extends('enterpriseframework::layout')

@section('content')
<div class="efw-page">
    <h1>Enterprise Reporting Portal</h1>
    <p>Central report catalogue for all connected standalone reporting modules.</p>

    <div class="efw-cards">
        <div class="efw-card">
            <strong>{{ $summary['total_reports'] ?? 0 }}</strong>
            <span>Total Reports</span>
        </div>
        <div class="efw-card">
            <strong>{{ count($summary['modules'] ?? []) }}</strong>
            <span>Connected Modules</span>
        </div>
    </div>

    @foreach($menu as $module => $categories)
        <h3>{{ $module }}</h3>
        @foreach($categories as $category => $reports)
            <h4>{{ $category }}</h4>
            <ul>
                @foreach($reports as $report)
                    <li>{{ $report['name'] }}</li>
                @endforeach
            </ul>
        @endforeach
    @endforeach
</div>
@endsection
