@extends('layouts.app')
@section('title', __('stocktransfernew::uat.snapshot'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-uat.css') }}">
<div class="stn-uat-wrap">
    <div class="stn-uat-hero"><h1>{{ __('stocktransfernew::uat.snapshot') }}</h1><p>Current tenant data counts for quick testing validation.</p></div>
    <div class="stn-snapshot-grid">
        @foreach($snapshot as $table => $count)
            <div class="stn-snapshot-box"><strong>{{ $count }}</strong><span>{{ $table }}</span></div>
        @endforeach
    </div>
</div>
@endsection
