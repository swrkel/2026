@extends('layouts.app')

@section('title', __('restaurantnew::lang.restaurant_new'))

@section('content')
    @yield('restaurantnew_content')
@endsection

@section('javascript')
    <script src="{{ asset('modules/restaurantnew/js/restaurantnew.js') }}"></script>
    @yield('restaurantnew_js')
@endsection
