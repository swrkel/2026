@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::reservation.reservation_dashboard'))
@section('content')
<section class="content-header restaurantnew-page-header">
    <h1>@lang('restaurantnew::reservation.reservation_dashboard')</h1>
</section>
<section class="content restaurantnew-command-center">
    <div class="row rn-stat-row">
        @foreach($summary as $key => $value)
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="rn-stat-card">
                    <span class="rn-stat-label">{{ __('restaurantnew::reservation.' . $key) }}</span>
                    <strong>{{ number_format((float) $value, 2) }}</strong>
                </div>
            </div>
        @endforeach
    </div>
    <div class="box box-solid rn-box">
        <div class="box-header with-border"><h3 class="box-title">@lang('restaurantnew::reservation.live_floor_status')</h3></div>
        <div class="box-body">
            <div id="restaurant-new-floor-board" class="rn-floor-board" data-feed-url="{{ route('restaurantnew.reservations.dashboard') }}">
                <p>@lang('restaurantnew::reservation.floor_board_hint')</p>
            </div>
        </div>
    </div>
</section>
@endsection
@push('javascript')
<script src="{{ asset('modules/restaurantnew/js/reservation-floor.js') }}"></script>
@endpush
