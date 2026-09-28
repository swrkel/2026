@extends('layouts.app')

@section('content')
<section class="content-header">
    <h1>@yield('digitalwallet-title', 'Digital Wallet')</h1>
</section>
<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @yield('digitalwallet-content')
</section>
@endsection
