@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::compliance.title'))
@section('content')
<div class="rn-page rn-compliance-page">
    <div class="rn-page-header"><h1>{{ __('restaurantnew::compliance.title') }}</h1><p>{{ __('restaurantnew::compliance.subtitle') }}</p></div>
    <div class="rn-card"><div class="rn-card-body">@yield('compliance_body')</div></div>
</div>
@endsection
