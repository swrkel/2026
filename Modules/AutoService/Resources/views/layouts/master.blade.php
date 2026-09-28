@extends('layouts.app')
@section('content')
<section class="content-header"><h1>@yield('title','Auto Service')</h1></section>
<section class="content">
@if(empty($autoservice_hide_nav))
    @include('autoservice::layouts.nav')
@endif
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@yield('autoservice_content')
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('modules/autoservice/css/autoservice.css') }}">@endpush
@push('javascript')<script src="{{ asset('modules/autoservice/js/autoservice.js') }}"></script>@endpush
