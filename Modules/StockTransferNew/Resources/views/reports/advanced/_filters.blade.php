<div class="stnew-toolbar no-print">
    <form method="GET" class="stnew-filter-form">
        <input type="date" name="from_date" value="{{ request('from_date') }}">
        <input type="date" name="to_date" value="{{ request('to_date') }}">
        <select name="status">
            <option value="">{{ __('stocktransfernew::lang.all_status') }}</option>
            @foreach(['draft','pending','approved','in_transit','received','completed','rejected','returned_for_correction'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <input type="number" name="from_location_id" placeholder="From location" value="{{ request('from_location_id') }}">
        <input type="number" name="to_location_id" placeholder="To location" value="{{ request('to_location_id') }}">
        <input type="number" name="from_store_id" placeholder="From store" value="{{ request('from_store_id') }}">
        <input type="number" name="to_store_id" placeholder="To store" value="{{ request('to_store_id') }}">
        <button class="btn btn-primary" type="submit">{{ __('stocktransfernew::lang.search') }}</button>
        <a class="btn btn-secondary" href="{{ url()->current() }}">{{ __('stocktransfernew::lang.reset') }}</a>
        @isset($exportType)
            <a class="btn btn-success" href="{{ route('stock-transfer-new.advanced-reports.export', $exportType) }}?{{ http_build_query(request()->query()) }}">CSV</a>
        @endisset
        <button type="button" class="btn btn-default" onclick="window.print()">Print</button>
    </form>
</div>
