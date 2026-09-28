@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.legacy_comparison'))
@section('productsnew_content')
<section class="productsnew-page productsnew-legacy-comparison">
    <div class="productsnew-header-card">
        <div>
            <h1>{{ __('productsnew::lang.legacy_comparison') }}</h1>
            <p>{{ __('productsnew::lang.legacy_comparison_help') }}</p>
        </div>
    </div>
    <div class="productsnew-card">
        <div class="productsnew-card-title">{{ __('productsnew::lang.parallel_module_status') }}</div>
        <div class="productsnew-two-column">
            <div class="productsnew-panel">
                <h3>{{ __('productsnew::lang.legacy_product_module') }}</h3>
                <p>{{ __('productsnew::lang.kept_active_untouched') }}</p>
                <strong>{{ number_format($summary['counts']['legacy_products']) }}</strong>
            </div>
            <div class="productsnew-panel">
                <h3>{{ __('productsnew::lang.products_new') }}</h3>
                <p>{{ __('productsnew::lang.parallel_testing_help') }}</p>
                <strong>{{ number_format($summary['counts']['products']) }}</strong>
            </div>
        </div>
    </div>
</section>
@endsection
