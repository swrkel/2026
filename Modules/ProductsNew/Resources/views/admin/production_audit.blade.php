@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.production_audit'))
@section('productsnew_content')
<div class="productsnew-page productsnew-audit-page">
    <div class="productsnew-card productsnew-mb-16">
        <div class="productsnew-card-header">
            <div>
                <h3>{{ __('productsnew::lang.production_audit') }}</h3>
                <p class="text-muted">Final standalone, security, performance and UI readiness checklist.</p>
            </div>
            <a href="{{ route('products-new.dashboard') }}" class="btn btn-secondary btn-sm">{{ __('productsnew::lang.back_to_dashboard') }}</a>
        </div>
        <div class="productsnew-kpi-grid four">
            <div class="productsnew-kpi"><span>Standalone status</span><strong>{{ strtoupper($audit['status'] ?? 'unknown') }}</strong></div>
            <div class="productsnew-kpi"><span>Checklist items</span><strong>{{ count($checklist ?? []) }}</strong></div>
            <div class="productsnew-kpi"><span>Security checks</span><strong>{{ count($security ?? []) }}</strong></div>
            <div class="productsnew-kpi"><span>Performance checks</span><strong>{{ count($performance ?? []) }}</strong></div>
        </div>
    </div>

    <div class="productsnew-card productsnew-mb-16">
        <div class="productsnew-card-header"><h4>Standalone Checklist</h4></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped productsnew-table">
                <thead><tr><th>Area</th><th>Requirement</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($checklist as $row)
                    <tr><td>{{ $row['key'] }}</td><td>{{ $row['label'] }}</td><td><span class="badge badge-success">{{ $row['status'] }}</span></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(!empty($audit['items']))
    <div class="productsnew-card productsnew-mb-16">
        <div class="productsnew-card-header"><h4>Items Requiring Review</h4></div>
        <div class="table-responsive">
            <table class="table table-bordered productsnew-table">
                <thead><tr><th>File</th><th>Needle</th><th>Message</th></tr></thead>
                <tbody>
                @foreach($audit['items'] as $item)
                    <tr><td>{{ $item['file'] }}</td><td>{{ $item['needle'] }}</td><td>{{ $item['message'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="productsnew-grid two">
        <div class="productsnew-card">
            <div class="productsnew-card-header"><h4>Security Review</h4></div>
            @foreach($security as $row)
                <div class="productsnew-list-row"><strong>{{ $row['area'] }}</strong><span>{{ $row['note'] }}</span></div>
            @endforeach
        </div>
        <div class="productsnew-card">
            <div class="productsnew-card-header"><h4>Performance Review</h4></div>
            @foreach($performance as $row)
                <div class="productsnew-list-row"><strong>{{ $row['item'] }}</strong><span>{{ $row['action'] }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="productsnew-card productsnew-mt-16">
        <div class="productsnew-card-header"><h4>UI Standard</h4></div>
        @foreach($ui as $key => $value)
            <div class="productsnew-list-row"><strong>{{ ucfirst($key) }}</strong><span>{{ $value }}</span></div>
        @endforeach
    </div>
</div>
@endsection
