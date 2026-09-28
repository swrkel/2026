@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.migration_readiness'))
@section('productsnew_content')
<section class="productsnew-page productsnew-migration-readiness">
    <div class="productsnew-header-card">
        <div>
            <h1>{{ __('productsnew::lang.migration_readiness') }}</h1>
            <p>{{ __('productsnew::lang.migration_readiness_help') }}</p>
        </div>
        <span class="productsnew-badge productsnew-badge-warning">{{ __('productsnew::lang.parallel_testing') }}</span>
    </div>

    <div class="productsnew-kpi-grid productsnew-kpi-grid-4">
        <div class="productsnew-kpi-card"><span>{{ __('productsnew::lang.legacy_products') }}</span><strong>{{ number_format($summary['counts']['legacy_products']) }}</strong></div>
        <div class="productsnew-kpi-card"><span>{{ __('productsnew::lang.products') }}</span><strong>{{ number_format($summary['counts']['products']) }}</strong></div>
        <div class="productsnew-kpi-card"><span>{{ __('productsnew::lang.categories') }}</span><strong>{{ number_format($summary['counts']['products_new_categories']) }}</strong></div>
        <div class="productsnew-kpi-card"><span>{{ __('productsnew::lang.brands') }}</span><strong>{{ number_format($summary['counts']['products_new_brands']) }}</strong></div>
    </div>

    <div class="productsnew-card">
        <div class="productsnew-card-title">{{ __('productsnew::lang.readiness_checks') }}</div>
        <div class="table-responsive">
            <table class="table productsnew-table">
                <thead><tr><th>{{ __('productsnew::lang.check') }}</th><th>{{ __('productsnew::lang.status') }}</th><th>{{ __('productsnew::lang.note') }}</th></tr></thead>
                <tbody>
                @foreach($summary['checks'] as $check)
                    <tr>
                        <td>{{ $check['name'] }}</td>
                        <td><span class="productsnew-status productsnew-status-{{ $check['status'] }}">{{ strtoupper($check['status']) }}</span></td>
                        <td>{{ $check['note'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
