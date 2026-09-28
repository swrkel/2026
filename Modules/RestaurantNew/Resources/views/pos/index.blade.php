@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::lang.restaurant_pos'))

@section('content')
<section class="content-header restaurantnew-header">
    <h1>@lang('restaurantnew::lang.restaurant_pos')</h1>
</section>

<section class="content restaurantnew-pos-screen">
    <div class="row">
        <div class="col-md-8">
            @include('restaurantnew::pos.partials.order-types')
            @include('restaurantnew::pos.partials.table-selector')
            @include('restaurantnew::pos.partials.menu-browser')
        </div>
        <div class="col-md-4">
            @include('restaurantnew::pos.partials.running-order')
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/restaurantnew/js/restaurantnew_pos.js') }}"></script>
@endsection
