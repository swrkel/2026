@php
    $m = $mill ?? null;
    $locationOptions = $locations ?? [];
    $defaultLocationId = !empty($locationOptions) ? (int) $locationOptions[0]['id'] : null;
    $selectedLocationId = old('mill_location_id', optional($m)->location_id ?: $defaultLocationId);
@endphp

<div class="rcm-form-grid">
    <div class="rcm-field">
        <label>Code</label>
        <input name="code" value="{{ old('code', optional($m)->code) }}">
    </div>
    <div class="rcm-field">
        <label>Name</label>
        <input name="name" value="{{ old('name', optional($m)->name) }}" required>
    </div>
    <div class="rcm-field">
        <label>Location</label>
        <select class="rcm-searchable" name="mill_location_id" required>
            <option value="">Select location</option>
            @foreach($locationOptions as $location)
                <option value="{{ $location['id'] }}" {{ (string) $selectedLocationId === (string) $location['id'] ? 'selected' : '' }}>
                    {{ $location['name'] }}
                </option>
            @endforeach
        </select>
        <small>All active locations linked to the current Tenant UID and Business UID are shown.</small>
        @error('mill_location_id')<div class="rcm-field-error">{{ $message }}</div>@enderror
    </div>
    <div class="rcm-field">
        <label>Capacity / Hour</label>
        <input type="number" step="{{ $rcmQuantityStep }}" min="0" name="capacity_per_hour" value="{{ old('capacity_per_hour', optional($m)->capacity_per_hour) }}">
    </div>
</div>

@if(empty($locationOptions))
    <div class="rcm-alert rcm-alert-info" style="margin-top:12px">
        <i class="fa fa-map-marker"></i>
        No active Business Location is linked to the current Tenant UID and Business UID. Add/activate a Business Location before saving a Mill.
    </div>
@endif
