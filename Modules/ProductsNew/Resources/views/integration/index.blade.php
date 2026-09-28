@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.integration_bridge'))
@section('productsnew_content')
<div class="productsnew-page productsnew-integration-page">
    <div class="productsnew-card">
        <div class="productsnew-card-header">
            <div>
                <h3>{{ __('productsnew::lang.integration_bridge') }}</h3>
                <p class="text-muted">Other ERP modules should consume Products New through this bridge instead of directly depending on internal controllers or views.</p>
            </div>
        </div>
        <div class="productsnew-grid three">
            @foreach($consumers as $consumer)
                <div class="productsnew-feature-card">
                    <strong>{{ $consumer }}</strong>
                    <p>Use lookup, stock and price bridge endpoints/services.</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
