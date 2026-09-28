@extends('layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="disnew-shell">
    <div class="disnew-header">
        <div><h3>@yield('title', __('distributionnew::messages.module_name'))</h3><p>@yield('subtitle', __('distributionnew::messages.module_subtitle'))</p></div>
        <div class="disnew-actions">@yield('page_actions')</div>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @yield('module_content')
</div>
@endsection
