@extends('restaurantnew::compliance.layout')
@section('compliance_body')
<div class="rn-kpi-grid">
    <div class="rn-kpi"><span>{{ __('restaurantnew::compliance.allergens') }}</span><strong>{{ $allergenCount ?? 0 }}</strong></div>
    <div class="rn-kpi"><span>{{ __('restaurantnew::compliance.dietary_tags') }}</span><strong>{{ $dietaryTagCount ?? 0 }}</strong></div>
    <div class="rn-kpi"><span>{{ __('restaurantnew::compliance.nutrition_profiles') }}</span><strong>{{ $nutritionProfileCount ?? 0 }}</strong></div>
    <div class="rn-kpi"><span>{{ __('restaurantnew::compliance.pending_checks') }}</span><strong>{{ $pendingChecks ?? 0 }}</strong></div>
</div>
@include('restaurantnew::compliance.partials.nav')
@endsection
