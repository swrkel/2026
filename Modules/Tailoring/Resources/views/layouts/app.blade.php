@extends('layouts.app')
@section('title', __('tailoring::lang.tailoring'))
@section('content')
<section class="content-header">
    <h1>@yield('page_title', __('tailoring::lang.tailoring'))</h1>
</section>
<section class="content tailoring-module">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @yield('tailoring_content')
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/Tailoring/Resources/assets/js/tailoring.js') }}"></script>
@endsection
