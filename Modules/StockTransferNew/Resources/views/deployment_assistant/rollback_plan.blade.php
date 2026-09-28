@extends('layouts.app')
@section('title', 'Stock Transfer New Rollback Plan')
@section('content')
<section class="content-header stn-pos-header"><h1>Rollback Readiness Plan</h1></section>
<section class="content stn-deployment-page">
    <div class="box stn-box"><div class="box-header with-border"><h3 class="box-title">Safe Rollback Steps</h3></div>
        <div class="box-body">
            @foreach($steps as $step)
                <div class="stn-step"><strong>{{ $step['step'] }}. {{ $step['title'] }}</strong><p>{{ $step['details'] }}</p></div>
            @endforeach
        </div>
    </div>
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_041.css') }}">@endpush
