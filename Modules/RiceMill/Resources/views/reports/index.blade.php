@extends('RiceMill::layout')
@section('rcm-title', 'Rice Mill Reports')
@section('rcm-subtitle', 'Operational, stock, production and profitability reports')

@section('rcm-content')
@php
    $rcmReportQuery = request()->only(['range', 'from', 'to', 'location_id', 'store_id', 'q', 'per_page']);
@endphp

<div class="rcm-card rcm-report-tabs-card">
    {{--
        IMPORTANT: use the ERP global tab classes exactly. Colors, active-state
        colors and base sizing are intentionally NOT hard-coded in Rice Mill.
    --}}
    <div class="settlement_tabs rcm-report-tabs" aria-label="Rice Mill report tabs">
        <ul class="nav nav-tabs" role="tablist">
            @foreach($tabs as $tabKey => $tabLabel)
                @php
                    $tabUrl = route('rice-mill.reports.index', array_merge($rcmReportQuery, ['tab' => $tabKey]));
                @endphp
                <li role="presentation" class="{{ $activeTab === $tabKey ? 'active' : '' }}">
                    <a href="{{ $tabUrl }}"
                       role="tab"
                       aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                       data-rcm-report-tab="{{ $tabKey }}">
                        {{ $tabLabel }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div id="rcm-report-tab-content"
     class="rcm-report-tab-content"
     data-report-url="{{ route('rice-mill.reports.index') }}"
     aria-live="polite">
    @if($reportKind === 'profitability')
        @include('RiceMill::reports.partials.profitability', array_merge($reportData, ['activeTab' => $activeTab]))
    @else
        @include('RiceMill::reports.partials.table', array_merge($reportData, ['activeTab' => $activeTab]))
    @endif
</div>
@endsection
