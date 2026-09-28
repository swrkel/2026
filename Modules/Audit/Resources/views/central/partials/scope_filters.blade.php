@php
    $selectedSources = (array) request('source_keys', []);
    $selectedBusinesses = (array) request('business_keys', []);
    $selectedLocations = (array) request('location_keys', []);
    $showDate = isset($showDate) ? $showDate : true;
    $showSearch = isset($showSearch) ? $showSearch : true;
@endphp
<div class="audit-central-scope" data-scope-options-url="{{ url('/audit/scope-options') }}">
    <div class="audit-central-scope-head">
        <div>
            <div class="audit-card-title">Audit Scope</div>
            <div class="audit-note">Leave Tenant / Data Source blank for <strong>All</strong>. To choose specific businesses or locations, select one or more data sources first.</div>
        </div>
        <div class="audit-scope-counts">
            <span><strong data-source-count>{{ count($selectedSources) ?: 'All' }}</strong> Sources</span>
            <span><strong data-business-count>{{ count($selectedBusinesses) ?: 'All' }}</strong> Businesses</span>
            <span><strong data-location-count>{{ count($selectedLocations) ?: 'All' }}</strong> Locations</span>
        </div>
    </div>

    <div class="audit-central-scope-grid">
        <div class="audit-scope-picker">
            <label>Tenant / Data Source</label>
            <input type="text" class="audit-input audit-scope-search" data-filter-select="central-source-select" placeholder="Type to filter tenants">
            <select id="central-source-select" class="audit-input audit-multi-select audit-source-select" name="source_keys[]" multiple size="7">
                @foreach($source_options as $source)
                    <option value="{{ $source['key'] }}" {{ in_array($source['key'],$selectedSources,true) ? 'selected' : '' }}>{{ $source['label'] }}</option>
                @endforeach
            </select>
            <div class="audit-scope-picker-actions"><button type="button" class="audit-mini-link" data-clear-select="central-source-select">All / Clear</button><span>Blank = all central + tenant databases</span></div>
        </div>

        <div class="audit-scope-picker">
            <label>Business</label>
            <input type="text" class="audit-input audit-scope-search" data-filter-select="central-business-select" placeholder="Type to filter businesses">
            <select id="central-business-select" class="audit-input audit-multi-select audit-business-select" name="business_keys[]" multiple size="7" {{ !$selectedSources ? 'disabled' : '' }}>
                @foreach($business_options as $business)
                    <option value="{{ $business['key'] }}" {{ in_array($business['key'],$selectedBusinesses,true) ? 'selected' : '' }}>{{ $business['label'] }}</option>
                @endforeach
            </select>
            <div class="audit-scope-picker-actions"><button type="button" class="audit-mini-link" data-clear-select="central-business-select">All / Clear</button><span class="audit-business-help">{{ $selectedSources ? 'Blank = all businesses in selected sources' : 'Select source(s) first' }}</span></div>
        </div>

        <div class="audit-scope-picker">
            <label>Location</label>
            <input type="text" class="audit-input audit-scope-search" data-filter-select="central-location-select" placeholder="Type to filter locations">
            <select id="central-location-select" class="audit-input audit-multi-select audit-location-select" name="location_keys[]" multiple size="7" {{ !$selectedSources ? 'disabled' : '' }}>
                @foreach($location_options as $location)
                    <option value="{{ $location['key'] }}" {{ in_array($location['key'],$selectedLocations,true) ? 'selected' : '' }}>{{ $location['label'] }}</option>
                @endforeach
            </select>
            <div class="audit-scope-picker-actions"><button type="button" class="audit-mini-link" data-clear-select="central-location-select">All / Clear</button><span class="audit-location-help">{{ $selectedSources ? 'Blank = all locations in selected businesses/sources' : 'Select source(s) first' }}</span></div>
        </div>
    </div>

    @if($showDate || $showSearch)
    <div class="audit-central-filter-strip">
        @if($showSearch)
            <input type="text" name="search" value="{{ request('search') }}" class="audit-input audit-central-search" placeholder="Search findings / rule / issue">
        @endif
        @if($showDate)
            <select name="preset" class="audit-input audit-date-preset audit-central-preset">
                @foreach(['this_year'=>'This Year','last_year'=>'Last Year','this_fy'=>'This FY','last_fy'=>'Last FY','custom'=>'Custom'] as $key=>$label)
                    <option value="{{ $key }}" {{ request('preset',config('audit.default_date_preset','this_year'))===$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="audit-input audit-custom-date audit-central-date">
            <input type="date" name="to" value="{{ request('to') }}" class="audit-input audit-custom-date audit-central-date">
        @endif
        <button class="audit-btn primary" type="submit">Apply Scope</button>
    </div>
    @endif
</div>
