@props([
    'tableId',
    'title' => 'Report',
    'searchId' => 'reo-report-search',
    'dateFrom' => null,
    'dateTo' => null,
    'pageSize' => 25,
    'filterUrl' => null,
    'shareUrl' => null,
    'financialYearStartMonth' => 1,
])

@php
    $displayFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '';
    $displayTo = $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '';
    $displayRange = ($displayFrom && $displayTo) ? $displayFrom.' ~ '.$displayTo : '';
    $dateControlId = preg_replace('/[^A-Za-z0-9_-]/', '-', $searchId).'-date-range';
@endphp

<div class="reo-report-toolbar reo-standard-toolbar"
     data-reo-toolbar
     data-table-id="{{ $tableId }}"
     data-report-title="{{ $title }}"
     data-fy-start-month="{{ max(1, min(12, (int) $financialYearStartMonth)) }}"
     @if($filterUrl) data-filter-url="{{ $filterUrl }}" @endif
     @if($shareUrl) data-share-url="{{ $shareUrl }}" @endif>
    <div class="reo-toolbar-field reo-toolbar-search-field">
        <label for="{{ $searchId }}">Universal Search</label>
        <input class="reo-input" type="search" id="{{ $searchId }}" data-reo-report-search placeholder="Search all columns..." autocomplete="off">
    </div>

    <div class="reo-toolbar-field reo-system-date-range-field" data-reo-date-range-control>
        <label for="{{ $dateControlId }}">Date Range</label>
        <div class="reo-system-date-input-group">
            <button type="button" class="reo-system-date-calendar" data-reo-date-range-toggle aria-label="Open date range selector" title="Select date range">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm12 8H5v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9ZM6 6a1 1 0 0 0-1 1v1h14V7a1 1 0 0 0-1-1H6Z"/></svg>
            </button>
            <input class="reo-input reo-system-date-range-input"
                   id="{{ $dateControlId }}"
                   type="text"
                   value="{{ $displayRange }}"
                   placeholder="DD/MM/YYYY ~ DD/MM/YYYY"
                   readonly
                   data-reo-date-range-display
                   data-reo-date-range-toggle
                   autocomplete="off">
        </div>

        <div class="reo-system-date-popover" data-reo-date-range-popover hidden>
            <div class="reo-system-date-popover-title">Date Range</div>
            <button type="button" data-reo-range-preset="this_year">This Year</button>
            <button type="button" data-reo-range-preset="last_year">Last Year</button>
            <button type="button" data-reo-range-preset="this_fy">This FY</button>
            <button type="button" data-reo-range-preset="last_fy">Last FY</button>
            <button type="button" data-reo-range-preset="custom">Custom Date Range</button>
        </div>

        <input type="hidden" name="date_from" value="{{ $dateFrom }}" data-reo-date-from>
        <input type="hidden" name="date_to" value="{{ $dateTo }}" data-reo-date-to>

        <div class="reo-date-typing-modal" data-reo-date-typing-modal hidden>
            <div class="reo-date-typing-backdrop" data-reo-date-typing-close></div>
            <div class="reo-date-typing-card" role="dialog" aria-modal="true" aria-labelledby="{{ $dateControlId }}-modal-title">
                <div class="reo-date-typing-head">
                    <h3 id="{{ $dateControlId }}-modal-title">Select Custom Date Range: Date / Month / Year</h3>
                    <button type="button" class="reo-modal-close" data-reo-date-typing-close aria-label="Close">×</button>
                </div>
                <div class="reo-date-typing-body">
                    @foreach(['from' => 'From', 'to' => 'To'] as $side => $sideLabel)
                        <div class="reo-date-typing-section" data-reo-date-side="{{ $side }}">
                            <div class="reo-date-side-title">{{ $sideLabel }}</div>
                            <div class="reo-date-digit-groups">
                                <div class="reo-date-digit-group">
                                    <span>Date</span>
                                    <div>
                                        @for($i = 0; $i < 2; $i++)
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" data-reo-date-digit data-side="{{ $side }}" data-part="day" data-index="{{ $i }}" aria-label="{{ $sideLabel }} date digit {{ $i + 1 }}">
                                        @endfor
                                    </div>
                                </div>
                                <div class="reo-date-digit-group">
                                    <span>Month</span>
                                    <div>
                                        @for($i = 0; $i < 2; $i++)
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" data-reo-date-digit data-side="{{ $side }}" data-part="month" data-index="{{ $i }}" aria-label="{{ $sideLabel }} month digit {{ $i + 1 }}">
                                        @endfor
                                    </div>
                                </div>
                                <div class="reo-date-digit-group reo-date-digit-year">
                                    <span>Year</span>
                                    <div>
                                        @for($i = 0; $i < 4; $i++)
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" data-reo-date-digit data-side="{{ $side }}" data-part="year" data-index="{{ $i }}" aria-label="{{ $sideLabel }} year digit {{ $i + 1 }}">
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="reo-date-typing-error" data-reo-date-typing-error hidden></div>
                </div>
                <div class="reo-date-typing-actions">
                    <button type="button" class="reo-btn reo-btn-light" data-reo-date-typing-close>Close</button>
                    <button type="button" class="reo-btn reo-btn-primary" data-reo-date-typing-apply>Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="reo-toolbar-field reo-page-size-field">
        <label>Transactions</label>
        <select class="reo-input" name="per_page" data-reo-page-size aria-label="Transactions per page">
            @foreach([10,25,50,100,250,500] as $size)<option value="{{ $size }}" @selected((int)$pageSize === $size)>{{ $size }} / page</option>@endforeach
        </select>
    </div>

    <div class="reo-toolbar-actions" aria-label="Report actions">
        <button type="button" class="reo-btn reo-btn-csv" data-reo-action="csv">CSV</button>
        <button type="button" class="reo-btn reo-btn-excel" data-reo-action="excel">Excel</button>
        <button type="button" class="reo-btn reo-btn-pdf" data-reo-action="pdf">PDF</button>
        <button type="button" class="reo-btn reo-btn-print" data-reo-action="print">Print</button>
        <button type="button" class="reo-btn reo-btn-columns" data-reo-action="columns">Column Visibility</button>
        <button type="button" class="reo-btn reo-btn-email" data-reo-action="email" @disabled(!$shareUrl)>Email</button>
        <button type="button" class="reo-btn reo-btn-sms" data-reo-action="sms" @disabled(!$shareUrl)>SMS</button>
        <button type="button" class="reo-btn reo-btn-whatsapp" data-reo-action="whatsapp" @disabled(!$shareUrl)>WhatsApp</button>
    </div>

    <div class="reo-toolbar-status">
        <span class="reo-toolbar-count" data-reo-count></span>
        <span class="reo-toolbar-pages" data-reo-pages>
            <button type="button" class="reo-btn reo-btn-light reo-btn-sm" data-reo-prev>Previous</button>
            <span data-reo-page-label>1 / 1</span>
            <button type="button" class="reo-btn reo-btn-light reo-btn-sm" data-reo-next>Next</button>
        </span>
    </div>
</div>
