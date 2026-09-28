@extends('RiceMill::layout')
@section('rcm-title',$title ?? 'Rice Mill Report')
@section('rcm-content')
    @include('RiceMill::reports.partials.table', [
        'activeTab' => $activeTab ?? request('tab','report'),
        'reportLocations' => $reportLocations ?? [],
        'reportStores' => $reportStores ?? [],
        'selectedLocationId' => $selectedLocationId ?? 'all',
        'selectedStoreId' => $selectedStoreId ?? 'all',
    ])
@endsection
