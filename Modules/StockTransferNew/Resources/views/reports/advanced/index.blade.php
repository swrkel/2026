@extends('stocktransfernew::layouts.app')
@section('content')
<div class="stnew-page">
    <div class="stnew-page-header">
        <h1>{{ __('stocktransfernew::lang.advanced_reports') }}</h1>
        <p>{{ __('stocktransfernew::lang.advanced_reports_help') }}</p>
    </div>
    <div class="stnew-command-grid">
        @foreach([
            ['route'=>'stock-transfer-new.advanced-reports.product-wise','title'=>'Product-wise Analysis','text'=>'Requested, dispatched, received and variance by product.'],
            ['route'=>'stock-transfer-new.advanced-reports.location-wise','title'=>'Location-wise Analysis','text'=>'Movement between business locations.'],
            ['route'=>'stock-transfer-new.advanced-reports.store-wise','title'=>'Store-wise Analysis','text'=>'Store-to-store movement summary.'],
            ['route'=>'stock-transfer-new.advanced-reports.vehicle-wise','title'=>'Vehicle / Driver Analysis','text'=>'Dispatch performance by vehicle and driver.'],
            ['route'=>'stock-transfer-new.advanced-reports.user-wise','title'=>'User-wise Analysis','text'=>'Created, approved, dispatched and received accountability.'],
            ['route'=>'stock-transfer-new.advanced-reports.monthly-trend','title'=>'Monthly Trend','text'=>'Monthly transfer volume and quantity trends.'],
            ['route'=>'stock-transfer-new.advanced-reports.exceptions','title'=>'Exception Report','text'=>'Delayed, rejected, returned and variance transfers.'],
        ] as $card)
            <a class="stnew-command-card" href="{{ route($card['route']) }}">
                <span>{{ $card['title'] }}</span>
                <small>{{ $card['text'] }}</small>
            </a>
        @endforeach
    </div>
</div>
@endsection
