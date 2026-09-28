@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header">
    <h1>{{ $title }}</h1>
</section>
<section class="content banking-ui-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
        </div>
        <div class="box-body">
            <p>This page is registered for UI testing and module navigation.</p>
            <p>Connect this route to the related standalone Banking module controller when deploying the full module.</p>
        </div>
    </div>
</section>
@endsection
@push('css')
<link rel="stylesheet" href="{{ asset('modules/bankingui/css/banking-ui.css') }}">
@endpush
