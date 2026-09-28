@extends('layouts.app')

@section('title', __('airlineticketingnew::messages.module_name'))

@section('css')
    @parent
    <link rel="stylesheet" href="{{ asset('modules/airline-ticketing-new/css/airline-ticketing-new.css') }}">
    @stack('atn-css')
@endsection

@section('content')
    <section class="content-header atn-content-header">
        <h1>@yield('atn-title', __('airlineticketingnew::messages.module_name'))</h1>
    </section>

    <section class="content atn-content">
        @include('airlineticketingnew::partials.navigation')
        @yield('atn-content')
    </section>
@endsection

@section('javascript')
    @parent
    <script src="{{ asset('modules/airline-ticketing-new/js/airline-ticketing-new.js') }}"></script>
    @stack('atn-js')
@endsection
