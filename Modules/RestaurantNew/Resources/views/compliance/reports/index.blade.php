@extends('restaurantnew::compliance.layout')
@section('compliance_body')
@include('restaurantnew::compliance.partials.nav')
<h3>{{ __('restaurantnew::compliance.reports') }}</h3>
<div class="rn-report-grid">
    <div class="rn-report-card">{{ __('restaurantnew::compliance.allergen_report') }}</div>
    <div class="rn-report-card">{{ __('restaurantnew::compliance.nutrition_report') }}</div>
    <div class="rn-report-card">{{ __('restaurantnew::compliance.warning_report') }}</div>
    <div class="rn-report-card">{{ __('restaurantnew::compliance.compliance_report') }}</div>
</div>
@endsection
