@extends('beautysaloons::layouts.app')
@section('title', 'Finance Dashboard')
@section('content')
<section class="content-header"><h1>Finance Dashboard</h1></section>
<section class="content bs-dashboard-page">
@include('beautysaloons::dashboard.partials_kpi', ['cards' => [
 ['title'=>'Collection Total','value'=>number_format($data['collection_total'] ?? 0,2)],
 ['title'=>'Cash','value'=>number_format($data['cash_total'] ?? 0,2)],
 ['title'=>'Card','value'=>number_format($data['card_total'] ?? 0,2)],
 ['title'=>'Wallet','value'=>number_format($data['wallet_total'] ?? 0,2)],
]])
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('Modules/BeautySaloons/Resources/css/dashboard/beauty-dashboard.css') }}">@endpush
