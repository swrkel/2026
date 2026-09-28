<div class="audit-filter-row audit-main-filter-row">
    <div class="audit-filter-control audit-filter-search-wrap">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search..." class="audit-input audit-filter-search">
    </div>

    <div class="audit-filter-control audit-filter-preset-wrap">
        <select name="preset" class="audit-input audit-date-preset audit-filter-preset">
            @foreach(['this_year'=>'This Year','last_year'=>'Last Year','this_fy'=>'This FY','last_fy'=>'Last FY','custom'=>'Custom'] as $k=>$v)
                <option value="{{ $k }}" {{ request('preset', config('audit.default_date_preset')) === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>

    <div class="audit-filter-control audit-filter-date-wrap">
        <input type="date" name="from" value="{{ request('from') }}" class="audit-input audit-custom-date audit-filter-date">
    </div>
    <div class="audit-filter-control audit-filter-date-wrap">
        <input type="date" name="to" value="{{ request('to') }}" class="audit-input audit-custom-date audit-filter-date">
    </div>

    @isset($businesses)
        <div class="audit-filter-control audit-filter-business-wrap">
            <select name="business_id" class="audit-input audit-filter-business">
                <option value="">All Businesses</option>
                @foreach($businesses as $id=>$name)
                    <option value="{{ $id }}" {{ (string)request('business_id') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endisset

    @isset($locations)
        <div class="audit-filter-control audit-filter-location-wrap">
            <select name="location_id" class="audit-input audit-filter-location">
                <option value="">All Locations</option>
                @foreach($locations as $id=>$name)
                    <option value="{{ $id }}" {{ (string)request('location_id') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endisset

    <div class="audit-filter-control audit-filter-apply-wrap">
        <button class="audit-btn primary audit-filter-apply" type="submit">Apply</button>
    </div>
</div>
