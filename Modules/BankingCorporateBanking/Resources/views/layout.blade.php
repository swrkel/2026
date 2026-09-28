@extends('layouts.app')
@section('title', $pageTitle ?? $moduleTitle ?? 'Corporate Banking')
@section('content')
<section class="content-header">
    <h1>{{ $pageTitle ?? $moduleTitle ?? 'Corporate Banking' }}</h1>
</section>
<section class="content banking-corporate-module">
    @yield('banking_corporate_content')
</section>
@endsection
