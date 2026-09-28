@extends('RiceMill::layout')
@section('rcm-title','Rice Mill Profitability')
@section('rcm-content')
    @include('RiceMill::reports.partials.profitability', [
        'activeTab' => 'profitability',
        'reportLocations' => $reportLocations ?? [],
        'reportStores' => $reportStores ?? [],
        'selectedLocationId' => $selectedLocationId ?? 'all',
        'selectedStoreId' => $selectedStoreId ?? 'all',
    ])
@endsection
