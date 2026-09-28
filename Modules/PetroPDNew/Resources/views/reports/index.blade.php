@extends('petropdnew::layouts.app')
@section('title', $report->title())
@section('page_title', 'Petro PD-New Reports')
@section('pdnew_content')
@php
    $reportQuery = request()->except(['page']);
    $selectedLocation = (string) ($filters['location_id'] ?? '');
@endphp
<div class="pdn-page-head">
    <div>
        <h2>{{ $report->title() }}</h2>
        <p>Petro PD-New report generated from Pumper Dashboard-New source snapshots and Petro PD-New records.</p>
    </div>
    <div class="pdn-actions no-print">
        @can('petro_pd_new.reports.export')
            <a class="pdn-btn success" href="{{ route('petro-pd-new.reports.export', array_merge(['report' => $reportKey, 'format' => 'csv'], $reportQuery)) }}">CSV</a>
            <a class="pdn-btn primary" href="{{ route('petro-pd-new.reports.export', array_merge(['report' => $reportKey, 'format' => 'excel'], $reportQuery)) }}">Excel</a>
            <a class="pdn-btn danger" href="{{ route('petro-pd-new.reports.export', array_merge(['report' => $reportKey, 'format' => 'pdf'], $reportQuery)) }}">PDF</a>
        @endcan
        @can('petro_pd_new.reports.print')
            <a class="pdn-btn purple" target="_blank" rel="noopener" href="{{ route('petro-pd-new.reports.print', array_merge(['report' => $reportKey], $reportQuery)) }}">Print</a>
        @endcan
        <details class="pdn-column-menu">
            <summary class="pdn-btn light">Column Visibility</summary>
            <div class="pdn-popover pdn-column-options">
                @foreach($columns as $column => $label)
                    <label class="pdn-check">
                        <input type="checkbox" checked data-pdn-column-toggle="pdn-report-table" data-column-index="{{ $loop->index }}">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </details>
    </div>
</div>

<div class="pdn-card" style="margin-bottom:14px">
    <div class="pdn-tabs report-tabs">
        @foreach($reports as $key => $item)
            @can($item['permission'])
                <a id="pdn-report-{{ str_replace('_', '-', $key) }}-tab" class="pdn-tab {{ $reportKey === $key ? 'active' : '' }}" href="{{ route('petro-pd-new.reports.index', ['report' => $key]) }}">{{ $item['title'] }}</a>
            @endcan
        @endforeach
    </div>

    <form method="get" action="{{ route('petro-pd-new.reports.index', ['report' => $reportKey]) }}" class="pdn-toolbar" style="margin:0">
        <div class="pdn-field">
            <label>From</label>
            <input class="pdn-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="pdn-field">
            <label>To</label>
            <input class="pdn-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="pdn-field grow">
            <label>Location Search</label>
            <input class="pdn-input" type="search" autocomplete="off" placeholder="Type to filter locations" data-pdn-filter-select="pdn-report-location">
        </div>
        <div class="pdn-field grow">
            <label>Location</label>
            <select class="pdn-select" id="pdn-report-location" name="location_id">
                <option value="">All permitted locations</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected($selectedLocation === (string) $location->id)>{{ $location->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="pdn-field">
            <label>Status</label>
            <input class="pdn-input" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="All statuses">
        </div>
        <label class="pdn-check">
            <input type="checkbox" name="only_variance" value="1" @checked(!empty($filters['only_variance']))>
            Variance only
        </label>
        <button class="pdn-btn primary">Apply</button>
        <a class="pdn-btn light" href="{{ route('petro-pd-new.reports.index', ['report' => $reportKey]) }}">Reset</a>
    </form>
</div>

<div class="pdn-card">
    <div class="pdn-table-wrap">
        <table class="pdn-table" id="pdn-report-table">
            <thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach(array_keys($columns) as $column)
                        @php($value = data_get($row, $column))
                        <td class="{{ is_numeric($value) ? 'amount' : '' }}">
                            {{ is_numeric($value) && (str_contains($column, 'amount') || str_contains($column, 'total') || str_contains($column, 'variance')) ? number_format((float) $value, 4) : $value }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="pdn-empty">No report data for the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pdn-pagination">{{ $rows->links() }}</div>
</div>
@endsection
