@extends('layouts.app')
@section('title', __('bankingmicrofinance::lang.microfinance'))
@section('content')
<section class="content-header"><h1>@yield('page-title', __('bankingmicrofinance::lang.microfinance'))</h1></section>
<section class="content banking-microfinance-module">
@include('bankingmicrofinance::partials.nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@yield('module-content')
</section>
@endsection
