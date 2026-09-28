@extends('managementreport::layouts.master', [
    'pageTitle' => 'Daily Management Report',
    'pageSubtitle' => 'Choose the scope and only the sections management needs.'
])

@section('page_actions')
<a href="{{ route('managementreport.dashboard') }}" class="mgmt-btn mgmt-btn-default"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('managementreport.saved.index') }}" class="mgmt-btn mgmt-btn-success"><i class="fa fa-archive"></i> Saved Reports</a>
<a href="{{ route('managementreport.shares.index') }}" class="mgmt-btn mgmt-btn-info"><i class="fa fa-paper-plane"></i> Delivery History</a>
@endsection

@section('managementreport_content')
<form id="mgmt-report-form" method="POST" action="{{ route('managementreport.daily.generate') }}" data-preview-url="{{ route('managementreport.daily.preview') }}">
    @csrf
    <input type="hidden" name="business_id" value="{{ session('user.business_id') }}">
    @php
        $managementReportStartDate = old('start_date', now()->toDateString());
        $managementReportEndDate = old('end_date', now()->toDateString());
    @endphp
    <input type="hidden" id="mgmt-report-start-date" name="start_date" value="{{ $managementReportStartDate }}">
    <input type="hidden" id="mgmt-report-end-date" name="end_date" value="{{ $managementReportEndDate }}">

    <div class="mgmt-panel mgmt-filter-panel">
        <div class="mgmt-panel-header"><h3><i class="fa fa-filter"></i> Report Scope</h3></div>
        <div class="mgmt-filter-grid mgmt-daily-filter-grid">
            <div class="mgmt-field mgmt-date-range-field">
                <label for="mgmt-report-date-range">Date Range</label>
                <div class="mgmt-date-range-control">
                    <input
                        type="text"
                        id="mgmt-report-date-range"
                        class="mgmt-date-range-input"
                        data-start-date="{{ $managementReportStartDate }}"
                        data-end-date="{{ $managementReportEndDate }}"
                        placeholder="Select date range"
                        autocomplete="off"
                        readonly
                    >
                    <i class="fa fa-calendar mgmt-date-range-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="mgmt-field">
                <label>Location</label>
                <select name="location_id" class="mgmt-native-all-filter" data-default-all="1">
                    <option value="0" {{ in_array((string) old('location_id', '0'), ['', '0'], true) ? 'selected' : '' }}>All</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ (string) old('location_id', '') === (string) $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mgmt-field">
                <label>Store</label>
                <select name="store_id" class="mgmt-native-all-filter" data-default-all="1">
                    <option value="0" {{ in_array((string) old('store_id', '0'), ['', '0'], true) ? 'selected' : '' }}>All</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ (string) old('store_id', '') === (string) $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mgmt-field">
                <label>Shift</label>
                <select name="shift_id" class="mgmt-native-all-filter" data-default-all="1">
                    <option value="0" {{ in_array((string) old('shift_id', '0'), ['', '0'], true) ? 'selected' : '' }}>All</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ (string) old('shift_id', '') === (string) $shift->id ? 'selected' : '' }}>{{ $shift->shift_name ?? $shift->name ?? $shift->shift_number ?? ('Shift '.$shift->id) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @include('managementreport::daily.partials.section-selector', ['sections' => $sections])

    <div class="mgmt-action-bar">
        <button type="button" class="mgmt-btn" data-mgmt-select-all="1"><i class="fa fa-check-square-o"></i> Select All</button>
        <button type="button" class="mgmt-btn" data-mgmt-clear-all="1"><i class="fa fa-square-o"></i> Clear All</button>
        <span class="mgmt-live-indicator"><i class="fa fa-refresh"></i> Live report updates automatically</span>
        <span class="mgmt-spacer"></span>
        <button type="submit" class="mgmt-btn mgmt-btn-primary" title="Preserve an optional audit copy of the report currently shown below."><i class="fa fa-archive"></i> Save Snapshot</button>
    </div>
</form>

<div id="mgmt-preview-loading" class="mgmt-loading" hidden><i class="fa fa-spinner fa-spin"></i> Refreshing live report...</div>
<div id="mgmt-report-preview" class="mgmt-preview-host" aria-live="polite" @if(!empty($initialReport)) data-initial-preview="1" @endif>
    @if(!empty($initialReport))
        @include('managementreport::daily.preview', ['report' => $initialReport])
    @elseif(!empty($initialPreviewError))
        <div class="alert alert-danger mgmt-alert"><strong>Unable to prepare the live report.</strong><br>{{ $initialPreviewError }}</div>
    @else
        <div class="mgmt-empty-state">
            <i class="fa fa-spinner fa-spin"></i>
            <h3>Preparing live report</h3>
            <p>The report will appear automatically.</p>
        </div>
    @endif
</div>
@endsection
