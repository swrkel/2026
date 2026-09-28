@extends('layouts.app')

@section('title', $title ?? 'Ran')

@section('css')
    @parent
    <link rel="stylesheet" href="{{ route('ran.assets', ['type' => 'css', 'file' => 'ran.css']) }}">
    <link rel="stylesheet" href="{{ route('ran.assets', ['type' => 'css', 'file' => 'ran-reports.css']) }}">
    @stack('ran-css')
@endsection

@section('content')
<div class="ran-app">
    @if(session('status'))
        <div class="alert alert-success ran-alert">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger ran-alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('ran-content')
</div>
@endsection

@section('javascript')
    @parent
    <script src="{{ route('ran.assets', ['type' => 'js', 'file' => 'ran.js']) }}"></script>
    @stack('ran-js')
@endsection
