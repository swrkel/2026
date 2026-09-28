@extends('petropdnew::layouts.app')
@section('title', 'Petro PD-New Settings')
@section('page_title', 'Petro PD-New Settings')
@section('pdnew_content')
@php($selectedLocation = (string) old('location_id', $settings['location_id'] ?? $currentLocationId ?? ''))
<div class="pdn-page-head">
    <div>
        <h2>Module Settings</h2>
        <p>Settings apply only to Petro PD-New; Pumper Dashboard-New remains the exclusive operational source.</p>
    </div>
</div>
<div class="pdn-card">
    <form method="post" action="{{ route('petro-pd-new.settings.update') }}" class="pdn-form-grid" data-prevent-double-submit>
        @csrf
        @method('PUT')
        <div class="pdn-field">
            <label>Location Search</label>
            <input class="pdn-input" type="search" autocomplete="off" placeholder="Type to filter locations" data-pdn-filter-select="pdn-settings-location">
        </div>
        <div class="pdn-field">
            <label>Location</label>
            <select class="pdn-select" id="pdn-settings-location" name="location_id">
                <option value="">Current location / business-wide defaults</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected($selectedLocation === (string) $location->id)>{{ $location->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="pdn-field">
            <label>Settlement Prefix</label>
            <input class="pdn-input" name="settlement_prefix" value="{{ old('settlement_prefix', $settings['settlement_prefix']) }}" required>
        </div>
        <div class="pdn-field">
            <label>Day End Prefix</label>
            <input class="pdn-input" name="day_end_prefix" value="{{ old('day_end_prefix', $settings['day_end_prefix']) }}" required>
        </div>
        <div class="pdn-field">
            <label>Amount Decimals</label>
            <input class="pdn-input" type="number" min="0" max="8" name="amount_decimals" value="{{ old('amount_decimals', $settings['amount_decimals']) }}" required>
        </div>
        <div class="pdn-field">
            <label>Quantity Decimals</label>
            <input class="pdn-input" type="number" min="0" max="8" name="quantity_decimals" value="{{ old('quantity_decimals', $settings['quantity_decimals']) }}" required>
        </div>
        <div class="full pdn-check-grid">
            @foreach(['require_review' => 'Require review', 'require_approval' => 'Require approval', 'require_zero_variance' => 'Require zero variance', 'allow_reopen' => 'Allow authorized reopen', 'auto_import_closed_shifts' => 'Auto-import closed PONE shifts', 'is_active' => 'Module active'] as $key => $label)
                <label class="pdn-check">
                    <input type="hidden" name="{{ $key }}" value="0">
                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key] ?? false))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="full pdn-alert warning">
            <strong>Fixed integration boundary:</strong> this module reads only Pumper Dashboard-New <code>pone_</code> operational records and writes only the final settlement reference to <code>pone_shift_settlement_references</code>. Legacy Petro PD and legacy Pumper Dashboard are not supported sources.
        </div>
        <div class="full pdn-actions"><button class="pdn-btn success">Save Settings</button></div>
    </form>
</div>
@endsection
