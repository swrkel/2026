<div class="rcm-location-store-group rcm-report-location-store-group">
    <select class="form-control rcm-searchable rcm-location-select"
            name="location_id"
            aria-label="Location">
        <option value="all" {{ ($selectedLocationId ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
        @foreach(($reportLocations ?? []) as $location)
            <option value="{{ $location['id'] }}"
                {{ (string)($selectedLocationId ?? '') === (string)$location['id'] ? 'selected' : '' }}>
                {{ $location['name'] }}
            </option>
        @endforeach
    </select>

    <select class="form-control rcm-searchable rcm-store-select"
            name="store_id"
            aria-label="Store">
        <option value="all" {{ ($selectedStoreId ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
        @foreach(($reportStores ?? []) as $store)
            <option value="{{ $store['id'] }}"
                    data-location-id="{{ $store['location_id'] ?? '' }}"
                    {{ (string)($selectedStoreId ?? '') === (string)$store['id'] ? 'selected' : '' }}>
                {{ $store['name'] }}
            </option>
        @endforeach
    </select>
</div>
