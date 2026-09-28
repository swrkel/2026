@extends('petropdnew::layouts.app')
@section('title','Settlement '.$settlement->settlement_number)
@section('page_title','PD Settlement '.$settlement->settlement_number)
@section('pdnew_content')
@php($editable = in_array($settlement->status,['draft','reopened'],true))
<div class="pdn-page-head">
    <div>
        <h2>{{ $settlement->settlement_number }}</h2>
        <p>PONE Shift {{ $settlement->pone_shift_number }} • {{ $settlement->operator_name }} • {{ optional($settlement->settlement_date)->format('d M Y') }}</p>
    </div>
    <div class="pdn-actions">
        <a class="pdn-btn light" href="{{ route('petro-pd-new.settlements.index') }}">Back</a>
        @can('petro_pd_new.print')<a class="pdn-btn purple" target="_blank" href="{{ route('petro-pd-new.settlements.print',$settlement->id) }}">Print</a>@endcan
        @if($editable)
            @can('petro_pd_new.settlements.edit')<a class="pdn-btn light" href="{{ route('petro-pd-new.settlements.edit',$settlement->id) }}">Edit</a>@endcan
            @can('petro_pd_new.sources.refresh')
            <form method="post" action="{{ route('petro-pd-new.settlements.refresh',$settlement->id) }}" data-confirm="Refresh this settlement from the latest closed Pumper Dashboard-New source? Manual payments will be retained." data-prevent-double-submit>@csrf
                <button class="pdn-btn warning">Refresh PONE Source</button>
            </form>
            @endcan
        @endif
    </div>
</div>

@if(!$sourceMatches)
<div class="pdn-alert error"><strong>Source integrity warning:</strong> the closed Pumper Dashboard-New source has changed since this settlement was imported. Refresh and reconcile before approval or finalization.</div>
@endif

@include('petropdnew::settlements.partials.summary')

@php
    $tabAccess = $tabAccess ?? [
        'pumps' => true,
        'payments' => true,
        'credit' => true,
        'other' => true,
        'adjustments' => true,
        'reconciliation' => true,
        'documents' => true,
        'history' => true,
    ];
    $activeTab = array_key_first(array_filter($tabAccess));
@endphp

<div class="pdn-card" style="margin-top:14px">
    @if($activeTab)
        <div class="pdn-tabs" data-pdn-tabs role="tablist" aria-label="Settlement sections">
            @if($tabAccess['pumps'] ?? false)<button type="button" id="pdn-tab-pumps" class="pdn-tab {{ $activeTab === 'pumps' ? 'active' : '' }}" data-pdn-tab="pumps" role="tab" aria-controls="pdn-pumps" aria-selected="{{ $activeTab === 'pumps' ? 'true' : 'false' }}">Pump & Meter Sales</button>@endif
            @if($tabAccess['payments'] ?? false)<button type="button" id="pdn-tab-payments" class="pdn-tab {{ $activeTab === 'payments' ? 'active' : '' }}" data-pdn-tab="payments" role="tab" aria-controls="pdn-payments" aria-selected="{{ $activeTab === 'payments' ? 'true' : 'false' }}">Payments</button>@endif
            @if($tabAccess['credit'] ?? false)<button type="button" id="pdn-tab-credit" class="pdn-tab {{ $activeTab === 'credit' ? 'active' : '' }}" data-pdn-tab="credit" role="tab" aria-controls="pdn-credit" aria-selected="{{ $activeTab === 'credit' ? 'true' : 'false' }}">Credit Sales</button>@endif
            @if($tabAccess['other'] ?? false)<button type="button" id="pdn-tab-other" class="pdn-tab {{ $activeTab === 'other' ? 'active' : '' }}" data-pdn-tab="other" role="tab" aria-controls="pdn-other" aria-selected="{{ $activeTab === 'other' ? 'true' : 'false' }}">Other Operations</button>@endif
            @if($tabAccess['adjustments'] ?? false)<button type="button" id="pdn-tab-adjustments" class="pdn-tab {{ $activeTab === 'adjustments' ? 'active' : '' }}" data-pdn-tab="adjustments" role="tab" aria-controls="pdn-adjustments" aria-selected="{{ $activeTab === 'adjustments' ? 'true' : 'false' }}">Adjust Amounts</button>@endif
            @if($tabAccess['reconciliation'] ?? false)<button type="button" id="pdn-tab-reconciliation" class="pdn-tab {{ $activeTab === 'reconciliation' ? 'active' : '' }}" data-pdn-tab="reconciliation" role="tab" aria-controls="pdn-reconciliation" aria-selected="{{ $activeTab === 'reconciliation' ? 'true' : 'false' }}">Reconciliation</button>@endif
            @if($tabAccess['documents'] ?? false)<button type="button" id="pdn-tab-documents" class="pdn-tab {{ $activeTab === 'documents' ? 'active' : '' }}" data-pdn-tab="documents" role="tab" aria-controls="pdn-documents" aria-selected="{{ $activeTab === 'documents' ? 'true' : 'false' }}">Documents</button>@endif
            @if($tabAccess['history'] ?? false)<button type="button" id="pdn-tab-history" class="pdn-tab {{ $activeTab === 'history' ? 'active' : '' }}" data-pdn-tab="history" role="tab" aria-controls="pdn-history" aria-selected="{{ $activeTab === 'history' ? 'true' : 'false' }}">History</button>@endif
        </div>

        @if($tabAccess['pumps'] ?? false)<section id="pdn-pumps" class="pdn-tab-panel {{ $activeTab === 'pumps' ? 'active' : '' }}" data-pdn-panel="pumps" role="tabpanel" aria-labelledby="pdn-tab-pumps" @if($activeTab !== 'pumps') hidden @endif>@include('petropdnew::settlements.partials.pumps')</section>@endif
        @if($tabAccess['payments'] ?? false)<section id="pdn-payments" class="pdn-tab-panel {{ $activeTab === 'payments' ? 'active' : '' }}" data-pdn-panel="payments" role="tabpanel" aria-labelledby="pdn-tab-payments" @if($activeTab !== 'payments') hidden @endif>@include('petropdnew::settlements.partials.payments')</section>@endif
        @if($tabAccess['credit'] ?? false)<section id="pdn-credit" class="pdn-tab-panel {{ $activeTab === 'credit' ? 'active' : '' }}" data-pdn-panel="credit" role="tabpanel" aria-labelledby="pdn-tab-credit" @if($activeTab !== 'credit') hidden @endif>@include('petropdnew::settlements.partials.credit-sales')</section>@endif
        @if($tabAccess['other'] ?? false)<section id="pdn-other" class="pdn-tab-panel {{ $activeTab === 'other' ? 'active' : '' }}" data-pdn-panel="other" role="tabpanel" aria-labelledby="pdn-tab-other" @if($activeTab !== 'other') hidden @endif>@include('petropdnew::settlements.partials.other-operations')</section>@endif
        @if($tabAccess['adjustments'] ?? false)<section id="pdn-adjustments" class="pdn-tab-panel {{ $activeTab === 'adjustments' ? 'active' : '' }}" data-pdn-panel="adjustments" role="tabpanel" aria-labelledby="pdn-tab-adjustments" @if($activeTab !== 'adjustments') hidden @endif>@include('petropdnew::settlements.partials.adjustments')</section>@endif
        @if($tabAccess['reconciliation'] ?? false)<section id="pdn-reconciliation" class="pdn-tab-panel {{ $activeTab === 'reconciliation' ? 'active' : '' }}" data-pdn-panel="reconciliation" role="tabpanel" aria-labelledby="pdn-tab-reconciliation" @if($activeTab !== 'reconciliation') hidden @endif>@include('petropdnew::settlements.partials.reconciliation')</section>@endif
        @if($tabAccess['documents'] ?? false)<section id="pdn-documents" class="pdn-tab-panel {{ $activeTab === 'documents' ? 'active' : '' }}" data-pdn-panel="documents" role="tabpanel" aria-labelledby="pdn-tab-documents" @if($activeTab !== 'documents') hidden @endif>@include('petropdnew::settlements.partials.documents')</section>@endif
        @if($tabAccess['history'] ?? false)<section id="pdn-history" class="pdn-tab-panel {{ $activeTab === 'history' ? 'active' : '' }}" data-pdn-panel="history" role="tabpanel" aria-labelledby="pdn-tab-history" @if($activeTab !== 'history') hidden @endif>@include('petropdnew::settlements.partials.history')</section>@endif
    @else
        <div class="pdn-empty">All settlement detail tabs are disabled for this business.</div>
    @endif
</div>

@include('petropdnew::settlements.partials.workflow')
@endsection
