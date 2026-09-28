@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::production.production_center'))
@section('content')
<div class="restaurant-new-page rn-production-dashboard">
    <div class="rn-toolbar rn-toolbar--pos-standard">
        <h3>{{ __('restaurantnew::production.production_center') }}</h3>
        <div class="rn-toolbar__actions">
            <a href="{{ route('restaurantnew.production.plans.index') }}" class="btn btn-primary">{{ __('restaurantnew::production.production_plans') }}</a>
            <a href="{{ route('restaurantnew.production.batches.index') }}" class="btn btn-info">{{ __('restaurantnew::production.batch_production') }}</a>
        </div>
    </div>
    <div class="row rn-command-cards">
        @foreach($summary as $key => $value)
            <div class="col-md-3 col-sm-6">
                <div class="rn-card rn-card--metric">
                    <span>{{ __('restaurantnew::production.' . $key) }}</span>
                    <strong>{{ $value }}</strong>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
