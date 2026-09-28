@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::messages.feature_management'))
@section('content')
<div class="rn-page">
    <div class="rn-toolbar"><h3>{{ __('restaurantnew::messages.feature_management') }}</h3></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="row">
        @foreach($items as $item)
        <div class="col-md-4">
            <form method="POST" action="{{ route('restaurantnew.admin.features.save') }}" class="rn-card rn-feature-card">
                @csrf
                <input type="hidden" name="business_id" value="{{ $businessId }}">
                <input type="hidden" name="location_id" value="{{ $locationId }}">
                <input type="hidden" name="feature_key" value="{{ $item['feature_key'] }}">
                <h4>{{ $item['label'] }}</h4>
                <select name="is_enabled" class="form-control">
                    <option value="1" @selected($item['is_enabled'])>{{ __('restaurantnew::messages.enabled') }}</option>
                    <option value="0" @selected(!$item['is_enabled'])>{{ __('restaurantnew::messages.disabled') }}</option>
                </select>
                <button class="btn btn-primary rn-btn mt-2">{{ __('restaurantnew::messages.save') }}</button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endsection
