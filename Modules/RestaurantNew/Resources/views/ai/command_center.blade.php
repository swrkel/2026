@extends('restaurantnew::layouts.master')

@section('title', __('restaurantnew::ai.ai_operations_center'))

@section('content')
<section class="rn-page rn-ai-page">
    <div class="rn-page-header">
        <div>
            <h1>{{ __('restaurantnew::ai.ai_operations_center') }}</h1>
            <p>{{ __('restaurantnew::ai.subtitle') }}</p>
        </div>
        <div class="rn-toolbar">
            <button type="button" class="btn btn-primary" id="rn-generate-forecast">{{ __('restaurantnew::ai.generate_forecast') }}</button>
            <button type="button" class="btn btn-secondary" id="rn-scan-menu">{{ __('restaurantnew::ai.scan_menu_profit') }}</button>
        </div>
    </div>

    <div class="rn-kpi-grid">
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::ai.open_recommendations') }}</span><strong>{{ $open_recommendations->count() }}</strong></div>
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::ai.critical_inventory') }}</span><strong>{{ $critical_inventory->count() }}</strong></div>
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::ai.recent_anomalies') }}</span><strong>{{ $recent_anomalies->count() }}</strong></div>
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::ai.latest_forecast') }}</span><strong>{{ optional($latest_forecast)->forecast_date ? optional($latest_forecast->forecast_date)->format('Y-m-d') : '-' }}</strong></div>
    </div>

    <div class="rn-two-column">
        <div class="rn-card">
            <h3>{{ __('restaurantnew::ai.recommendations') }}</h3>
            <table class="table table-sm rn-table">
                <thead><tr><th>{{ __('restaurantnew::ai.priority') }}</th><th>{{ __('restaurantnew::ai.type') }}</th><th>{{ __('restaurantnew::ai.title') }}</th><th>{{ __('restaurantnew::ai.status') }}</th></tr></thead>
                <tbody>
                @forelse($open_recommendations as $row)
                    <tr><td>{{ $row->priority }}</td><td>{{ $row->recommendation_type }}</td><td>{{ $row->title }}</td><td>{{ $row->status }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">{{ __('restaurantnew::ai.no_data') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="rn-card">
            <h3>{{ __('restaurantnew::ai.anomalies') }}</h3>
            <table class="table table-sm rn-table">
                <thead><tr><th>{{ __('restaurantnew::ai.severity') }}</th><th>{{ __('restaurantnew::ai.area') }}</th><th>{{ __('restaurantnew::ai.title') }}</th><th>{{ __('restaurantnew::ai.status') }}</th></tr></thead>
                <tbody>
                @forelse($recent_anomalies as $row)
                    <tr><td>{{ $row->severity }}</td><td>{{ $row->anomaly_area }}</td><td>{{ $row->title }}</td><td>{{ $row->status }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">{{ __('restaurantnew::ai.no_data') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
