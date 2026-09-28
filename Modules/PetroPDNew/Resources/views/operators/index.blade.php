@extends('petropdnew::layouts.app')

@section('title', 'PD Operators')
@section('page_title', 'PD Operators')

@section('pdnew_content')
@php
    $activeDefinition = $tabs[$activeTab];
    $reportKey = $activeDefinition['report'] ?? null;
    $rows = data_get($workspaceData, 'rows', collect());
    $operatorTabEndpoint = Route::has('petro-pd-new.operators.tab')
        ? route('petro-pd-new.operators.tab')
        : url('/' . trim((string) config('petropdnew.route_prefix', 'petro-pd-new'), '/') . '/operators/tab');
@endphp

<div class="pdn-page-head pdn-operator-page-head">
    <div>
        <h2>PD Operators</h2>
        <p>Pump operator controls and operational summaries sourced from Pumper Dashboard-New.</p>
    </div>
    <div class="pdn-actions">
        @if($activeTab === 'pump_operators')
            @can('petro_pd_new.operators.manage')
                <form method="post" action="{{ route('petro-pd-new.operators.sync') }}" data-prevent-double-submit>
                    @csrf
                    <input type="hidden" name="tab" value="pump_operators">
                    <button class="pdn-btn primary" type="submit">
                        <i class="fa fa-refresh"></i> Synchronize PD Operators
                    </button>
                </form>
            @endcan
        @endif

        @if($reportKey && Route::has('petro-pd-new.reports.index'))
            @can('petro_pd_new.reports.view')
                <a class="pdn-btn light" href="{{ route('petro-pd-new.reports.index', ['report' => $reportKey]) }}">
                    <i class="fa fa-bar-chart"></i> Open Related Report
                </a>
            @endcan
        @endif
    </div>
</div>

@if(!empty($operatorSyncWarning))
    <div class="alert alert-warning no-print" style="margin: 0 0 15px;">
        <strong>PD operator synchronization warning:</strong> {{ $operatorSyncWarning }}
    </div>
@endif

<div class="pdn-operator-tabs" role="tablist" aria-label="PD Operator pages" data-pdn-operator-tabs data-tab-endpoint="{{ $operatorTabEndpoint }}">
    @foreach($tabs as $key => $definition)
        @if($tabVisibility[$key] ?? true)
            <a
                id="pdn-operator-tab-{{ $key }}"
                class="pdn-operator-tab {{ $activeTab === $key ? 'active' : '' }}"
                href="{{ route('petro-pd-new.operators.index', array_filter(['tab' => $key, 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']])) }}"
                role="tab"
                data-pdn-operator-tab-link
                data-tab-key="{{ $key }}"
                aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
            >
                <i class="{{ $definition['icon'] }}"></i>
                <strong>{{ $definition['label'] }}</strong>
            </a>
        @endif
    @endforeach
</div>

<form method="get" action="{{ route('petro-pd-new.operators.index') }}" class="pdn-toolbar pdn-operator-filter no-print" data-pdn-operator-filter>
    <input type="hidden" name="tab" value="{{ $activeTab }}">

    <div class="pdn-field grow">
        <label>Search</label>
        <input class="pdn-input" name="search" value="{{ $filters['search'] }}" placeholder="Operator, shift, reference or document number">
    </div>

    <div class="pdn-field">
        <label>From</label>
        <input class="pdn-input" type="date" name="date_from" value="{{ $filters['date_from'] }}">
    </div>
    <div class="pdn-field">
        <label>To</label>
        <input class="pdn-input" type="date" name="date_to" value="{{ $filters['date_to'] }}">
    </div>
    <div class="pdn-field">
        <label>Pump Operator</label>
        <select class="pdn-select" name="operator_profile_id">
            <option value="">All</option>
            @foreach($operatorOptions as $operator)
                <option value="{{ $operator->id }}" @selected((int) $filters['operator_profile_id'] === (int) $operator->id)>
                    {{ $operator->display_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="pdn-field">
        <label>Status</label>
        <select class="pdn-select" name="status">
            <option value="">All</option>
            @foreach(['active','inactive','planned','assigned','open','closing','closed','confirmed','draft','finalized','void','cancelled','failed','synced'] as $status)
                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    @if($activeTab === 'payment_summary')
        <div class="pdn-field">
            <label>Payment Type</label>
            <select class="pdn-select" name="payment_type">
                <option value="">All</option>
                @foreach(['cash','card','cheque','credit','shortage','excess','other'] as $type)
                    <option value="{{ $type }}" @selected($filters['payment_type'] === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <button class="pdn-btn primary" type="submit"><i class="fa fa-filter"></i> Apply</button>
    <a class="pdn-btn light" href="{{ route('petro-pd-new.operators.index', ['tab' => $activeTab]) }}"><i class="fa fa-times"></i> Clear</a>
</form>

<div class="pdn-operator-tab-panel active" id="pdn-operator-tab-panel" data-active-tab="{{ $activeTab }}" role="tabpanel" aria-labelledby="pdn-operator-tab-{{ $activeTab }}">
    @include('petropdnew::operators.tabs.' . $activeTab)
</div>

<div id="pdn-operator-modal-host" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"></div>

<div class="pdn-note no-print">
    The page loads only the selected tab and limits the visible result set to the latest 250 records for fast operation. Use the related report for complete exportable history.
</div>
@endsection
