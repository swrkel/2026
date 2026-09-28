@extends('layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-treasury">
<h3>{{ $title ?? 'Banking Microfinance Treasury' }}</h3>
{{ $slot ?? '' }}
@yield('treasury_content')
</div>
@endsection
