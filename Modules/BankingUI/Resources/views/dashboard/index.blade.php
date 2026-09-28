@extends('layouts.app')
@section('title', __('banking-ui::messages.banking_dashboard'))
@section('content')
<section class="content-header">
    <h1>{{ __('banking-ui::messages.banking_dashboard') }}</h1>
</section>
<section class="content banking-ui-page">
    <div class="row">
        @foreach($cards as $card)
            <div class="col-md-3 col-sm-6 col-xs-12">
                <a class="banking-ui-card" href="{{ route($card['route']) }}">
                    <div class="info-box">
                        <span class="info-box-icon"><i class="fa fa-university"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ $card['title'] }}</span>
                            <span class="info-box-number">Open</span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>
@endsection
@push('css')
<link rel="stylesheet" href="{{ asset('modules/bankingui/css/banking-ui.css') }}">
@endpush
