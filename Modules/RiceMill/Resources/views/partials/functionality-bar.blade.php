@php
    $tableId = $tableId ?? 'rcm-data-table';
    $exportName = $exportName ?? 'rice-mill-data';
    $serverPaged = $serverPaged ?? false;
    $dateEnabled = $dateEnabled ?? true;
    $rowsLabel = $rowsLabel ?? 'rows';
    $paginator = $paginator ?? null;
    $standard = app(\Modules\RiceMill\Services\StandardListService::class);
    $businessId = app(\Modules\RiceMill\Services\TenantContext::class)->businessId();
    $dateState = $standard->dateState(request(), $businessId);
    $pageSize = $standard->requestedPageSize(request(), 25);
    $searchValue = $standard->searchTerm(request());
    $preserve = request()->except(['q','per_page','range','from','to','date_range','page','partial']);
    $dateDisplay = ($dateState['from'] && $dateState['to'])
        ? \Illuminate\Support\Carbon::parse($dateState['from'])->format('m/d/Y') . ' - ' . \Illuminate\Support\Carbon::parse($dateState['to'])->format('m/d/Y')
        : '';
@endphp

<div class="rcm-standard-toolbar"
     data-rcm-table-toolbar
     data-table-id="{{ $tableId }}"
     data-export-name="{{ $exportName }}"
     data-server-paged="{{ $serverPaged ? '1' : '0' }}">
    <div class="rcm-standard-toolbar-left">
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="columns"><i class="fa fa-columns"></i> Column Visibility</button>
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="csv"><i class="fa fa-file-text-o"></i> Export to CSV</button>
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="excel"><i class="fa fa-file-excel-o"></i> Export to Excel</button>
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="print"><i class="fa fa-print"></i> Print</button>
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="pdf"><i class="fa fa-file-pdf-o"></i> PDF</button>
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="whatsapp"><i class="fa fa-whatsapp"></i> WhatsApp</button>
        <button type="button" class="rcm-tool-btn" data-rcm-table-action="email"><i class="fa fa-envelope-o"></i> E Mail</button>
        <div class="rcm-column-panel" data-rcm-column-panel hidden></div>
    </div>

    <form method="get" class="rcm-standard-toolbar-right" data-rcm-standard-filter-form>
        @foreach($preserve as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <label class="rcm-search-wrap" title="Global search across this page">
            <i class="fa fa-search"></i>
            <input type="search" name="q" value="{{ $searchValue }}" data-rcm-table-search placeholder="Global Search">
        </label>

        @if($dateEnabled)
            <div class="rcm-system-date-filter">
                <label class="rcm-system-date-label">Date Range:</label>
                <div class="input-group rcm-system-date-range-wrapper">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    <input type="text"
                           class="form-control rcm-system-date-range-picker"
                           name="date_range"
                           value="{{ $dateDisplay }}"
                           data-rcm-system-date-range
                           data-fy-start-month="{{ (int) $dateState['fy_start_month'] }}"
                           readonly
                           autocomplete="off"
                           placeholder="MM/DD/YYYY - MM/DD/YYYY">
                </div>
                <input type="hidden" name="range" value="{{ $dateState['range'] }}" data-rcm-date-range-key>
                <input type="hidden" name="from" value="{{ $dateState['from'] }}" data-rcm-date-from>
                <input type="hidden" name="to" value="{{ $dateState['to'] }}" data-rcm-date-to>
                <button type="button" class="rcm-date-all-btn" data-rcm-date-all title="Show all dates">All</button>
            </div>
        @endif

        <label class="rcm-page-size-wrap">Rows per page
            <select name="per_page" data-rcm-page-size>
                @foreach(['10','25','50','100','250','500','all'] as $size)
                    <option value="{{ $size }}" {{ $pageSize===$size?'selected':'' }}>{{ $size==='all'?'All':$size }}</option>
                @endforeach
            </select>
        </label>
        <button class="rcm-filter-apply" type="submit"><i class="fa fa-filter"></i> Apply</button>
    </form>
</div>

@if($serverPaged && $paginator)
    <div class="rcm-table-statusbar" data-rcm-table-status="{{ $tableId }}" data-server-status="1">
        <span>
            Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} {{ $rowsLabel }}
        </span>
        <span class="rcm-muted">Filtered within the active Tenant UID + Business UID</span>
    </div>
@else
    <div class="rcm-table-statusbar" data-rcm-table-status="{{ $tableId }}">
        <span data-rcm-table-info></span>
        <div class="rcm-table-pager">
            <button type="button" data-rcm-page-prev aria-label="Previous page"><i class="fa fa-chevron-left"></i></button>
            <span data-rcm-page-number>1</span>
            <button type="button" data-rcm-page-next aria-label="Next page"><i class="fa fa-chevron-right"></i></button>
        </div>
    </div>
@endif
