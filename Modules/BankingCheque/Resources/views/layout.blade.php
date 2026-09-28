@extends('layouts.app')
@section('content')
<div class="container-fluid banking-cheque">
    <h3>{{ $title ?? 'Cheque Management' }}</h3>
    <div class="card"><div class="card-body">{{ $slot ?? '' }} @yield('banking_cheque_content')</div></div>
</div>
@endsection
