@extends('layouts.app')

@section('title', $title ?? __('enterpriseframework::messages.enterprise_framework'))

@section('content')
<section class="content-header">
    <h1>{{ $title ?? __('enterpriseframework::messages.enterprise_framework') }}</h1>
</section>
<section class="content">
    @yield('efw_content')
</section>
@endsection
