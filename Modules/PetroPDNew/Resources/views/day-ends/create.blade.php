@extends('petropdnew::layouts.app')
@section('title', 'Prepare Day End')
@section('page_title', 'Prepare Petro PD-New Day End')
@section('pdnew_content')
@php($selectedLocation = (string) old('location_id', $currentLocationId ?: ''))
<div class="pdn-page-head">
    <div>
        <h2>Prepare Day End</h2>
        <p>Includes only finalized Petro PD-New settlements not already assigned to another Day End.</p>
    </div>
    <a class="pdn-btn light" href="{{ route('petro-pd-new.day-ends.index') }}">Back</a>
</div>
<div class="pdn-card">
    <form method="post" action="{{ route('petro-pd-new.day-ends.store') }}" class="pdn-form-grid" data-prevent-double-submit>
        @csrf
        <div class="pdn-field">
            <label>Day End Date</label>
            <input class="pdn-input" type="date" name="day_end_date" value="{{ old('day_end_date', now()->toDateString()) }}" required>
        </div>
        <div class="pdn-field">
            <label>Location Search</label>
            <input class="pdn-input" type="search" autocomplete="off" placeholder="Type to filter locations" data-pdn-filter-select="pdn-day-end-location">
        </div>
        <div class="pdn-field">
            <label>Location</label>
            <select class="pdn-select" id="pdn-day-end-location" name="location_id">
                <option value="">Use logged-in location / consolidated scope</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected($selectedLocation === (string) $location->id)>{{ $location->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="pdn-field full">
            <label>Note</label>
            <textarea class="pdn-textarea" name="note">{{ old('note') }}</textarea>
        </div>
        <div class="full pdn-actions"><button class="pdn-btn success">Prepare Day End</button></div>
    </form>
</div>
@endsection
