@extends('reportsother::layouts.app')

@section('title', 'Cash Receipt - Reports - Other')
@section('page-title', 'Cash Receipt')

@section('content')
<div class="reo-card">
    <nav class="reo-tabs" aria-label="Cash Receipt tabs" data-reo-tabs>
        <a data-reo-tab="receipt" class="reo-tab reo-tab-receipt {{ $activeTab === 'receipt' ? 'active' : '' }}" href="{{ route('reports-other.cash-receipt.index', ['tab' => 'receipt']) }}">Receipt</a>
        <a data-reo-tab="list" class="reo-tab reo-tab-list {{ $activeTab === 'list' ? 'active' : '' }}" href="{{ route('reports-other.cash-receipt.index', ['tab' => 'list']) }}">List Receipt</a>
        <a data-reo-tab="mapping" class="reo-tab reo-tab-mapping {{ $activeTab === 'mapping' ? 'active' : '' }}" href="{{ route('reports-other.cash-receipt.index', ['tab' => 'mapping']) }}">Map Sub Products to Source</a>
        <a data-reo-tab="numbering" class="reo-tab reo-tab-numbering {{ $activeTab === 'numbering' ? 'active' : '' }}" href="{{ route('reports-other.cash-receipt.index', ['tab' => 'numbering']) }}">Prefix &amp; Starting Nos</a>
    </nav>

    <section class="reo-card-body" data-reo-tab-content data-active-tab="{{ $activeTab }}">
        @if($activeTab === 'list')
            @include('reportsother::cash-receipt.partials.list')
        @elseif($activeTab === 'mapping')
            @include('reportsother::cash-receipt.partials.mapping')
        @elseif($activeTab === 'numbering')
            @include('reportsother::cash-receipt.partials.numbering')
        @else
            @include('reportsother::cash-receipt.partials.receipt')
        @endif
    </section>
</div>
@endsection
