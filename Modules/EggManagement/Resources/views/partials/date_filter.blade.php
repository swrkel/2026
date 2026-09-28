<form method="get"
      class="egg-filter-card egg-dashboard-filter no-print"
      data-egg-date-filter
      data-fy-month="{{ config('egg.fiscal_year_start_month',4) }}">

    <div class="egg-filter-card-head">
        <div class="egg-filter-card-title">
            <span class="egg-filter-title-icon"><i class="fa fa-filter"></i></span>
            <div>
                <strong>Dashboard Filters</strong>
                <small>Select the period, location and store.</small>
            </div>
        </div>
        <span class="egg-filter-hint"><i class="fa fa-calendar"></i> System Date Range</span>
    </div>

    <div class="egg-filter-grid egg-filter-grid-clean">
        <label class="egg-filter-field egg-filter-date-range">
            <span class="egg-filter-label">Date Range</span>
            <span class="egg-date-range-control">
                <i class="fa fa-calendar egg-date-range-leading"></i>
                <input type="text"
                       class="egg-date-range-input"
                       value="{{ request('from', isset($from)?$from->format('Y-m-d'):'') }} ~ {{ request('to', isset($to)?$to->format('Y-m-d'):'') }}"
                       data-egg-date-range
                       autocomplete="off"
                       spellcheck="false"
                       aria-label="Date Range">
                <i class="fa fa-angle-down egg-date-range-trailing"></i>
            </span>
        </label>

        <input type="hidden" name="range" value="{{ request('range','this_year') }}" data-egg-range-value>
        <input type="hidden" name="from" value="{{ request('from', isset($from)?$from->format('Y-m-d'):'') }}" data-egg-from>
        <input type="hidden" name="to" value="{{ request('to', isset($to)?$to->format('Y-m-d'):'') }}" data-egg-to>

        @isset($locations)
        <label class="egg-filter-field egg-filter-location">
            <span class="egg-filter-label">Location</span>
            <select name="location_id" class="egg-system-select" data-egg-location>
                <option value="all">All Locations</option>
                @foreach($locations as $x)
                    <option value="{{ $x->id }}" @selected((string)($selectedLocation ?? request('location_id'))===(string)$x->id)>{{ $x->name }}</option>
                @endforeach
            </select>
        </label>
        @endisset

        @isset($stores)
        <label class="egg-filter-field egg-filter-store">
            <span class="egg-filter-label">Store</span>
            <select name="store_id" class="egg-system-select" data-egg-store>
                <option value="all">All Stores</option>
                @foreach($stores as $x)
                    <option value="{{ $x->id }}" @selected((string)($selectedStore ?? request('store_id'))===(string)$x->id)>{{ $x->name }}</option>
                @endforeach
            </select>
        </label>
        @endisset

        <div class="egg-filter-actions">
            <button type="submit" class="egg-btn egg-btn-green egg-filter-apply">
                <i class="fa fa-check"></i><span>Apply Filters</span>
            </button>
            <a href="{{ route('egg.dashboard') }}" class="egg-btn egg-btn-orange egg-filter-reset">
                <i class="fa fa-refresh"></i><span>Reset</span>
            </a>
        </div>
    </div>
</form>
