@extends('layouts.app')
@section('title', 'EzyLaw')
@section('css')
<link rel="stylesheet" href="{{ asset('modules/ezylaw/css/ezylaw.css') }}?v=4.0.0">
@stack('ezylaw_styles')
@endsection
@section('content')
<section class="content-header ezylaw-header"><div><h1>@yield('ezylaw_title','EzyLaw')</h1><small>Lawyer Management System</small></div></section>
<section class="content ezylaw-module">
@include('ezylaw::partials.nav')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('ezylaw_content')
</section>
@endsection
@push('javascript')<script src="{{ asset('modules/ezylaw/js/ezylaw.js') }}?v=4.0.0"></script>@endpush
