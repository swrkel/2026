@extends('layouts.app')
@section('title', __('distributionnew::lang.role_dashboard'))
@section('content')
<section class="content-header pos-style-header"><h1>{{ __('distributionnew::lang.role_dashboard') }}</h1></section>
<section class="content disnew-pos-dashboard">
    <div class="row">
        @foreach($cards as $key => $value)
            @if($key !== 'role')
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="small-box bg-white disnew-dashboard-card">
                    <div class="inner"><h3>{{ $value }}</h3><p>{{ __('distributionnew::lang.' . $key) }}</p></div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
</section>
@endsection
