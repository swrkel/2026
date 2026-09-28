@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/beautysaloons/css/beautysaloons.css') }}">
<div class="bs-module-wrap">
    @yield('beauty_content')
</div>
<script src="{{ asset('modules/beautysaloons/js/beautysaloons.js') }}"></script>
@endsection
