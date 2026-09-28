@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::crm.crm_dashboard'))
@section('content')
<div class="rn-page rn-crm-dashboard">
    <div class="rn-toolbar"><h3>{{ __('restaurantnew::crm.restaurant_crm') }}</h3></div>
    <div class="rn-kpi-grid">
        @foreach($summary as $key => $value)
            <div class="rn-kpi-card"><span>{{ __('restaurantnew::crm.'.$key) }}</span><strong>{{ is_numeric($value) ? number_format($value, 2) : $value }}</strong></div>
        @endforeach
    </div>
    <div class="rn-card mt-3">
        <h4>{{ __('restaurantnew::crm.customer_segments') }}</h4>
        <div class="rn-segment-grid">
            @foreach($segments as $key => $value)
                <div><span>{{ __('restaurantnew::crm.'.$key) }}</span><b>{{ $value }}</b></div>
            @endforeach
        </div>
    </div>
</div>
@endsection
