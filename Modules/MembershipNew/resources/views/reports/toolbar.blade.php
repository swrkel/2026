@php
    $mnTableId = $tableId ?? 'mn-report-table';
    $mnReportTitle = $reportTitle ?? ($title ?? 'Membership Report');
    $mnPerPage = (int) request('per_page', 50);
@endphp
<div class="mn-report-toolbar" data-mn-report-toolbar data-table-id="{{ $mnTableId }}" data-report-title="{{ $mnReportTitle }}">
    <div class="mn-report-toolbar-left">
        <div class="mn-report-search-wrap"><i class="fa fa-search"></i><input type="search" value="{{ request('q') }}" data-mn-report-search placeholder="Global Search" aria-label="Global Search"></div>
        <label class="mn-report-page-size">Rows per page
            <select data-mn-page-size>
                @foreach([10,25,50,100,200] as $size)<option value="{{ $size }}" {{ $mnPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>@endforeach
            </select>
        </label>
        <span class="mn-report-count" data-mn-report-count></span>
    </div>
    <div class="mn-report-toolbar-right">
        <button type="button" class="mn-btn mn-btn-success mn-btn-sm" data-mn-action="excel"><i class="fa fa-file-excel-o"></i> Excel</button>
        <button type="button" class="mn-btn mn-btn-primary mn-btn-sm" data-mn-action="csv"><i class="fa fa-file-text-o"></i> CSV</button>
        <div class="mn-column-menu">
            <button type="button" class="mn-btn mn-btn-purple mn-btn-sm" data-mn-columns><i class="fa fa-columns"></i> Columns</button>
            <div class="mn-column-menu-panel" data-mn-column-panel></div>
        </div>
        <button type="button" class="mn-btn mn-btn-info mn-btn-sm" data-mn-action="print"><i class="fa fa-print"></i> Print</button>
        <button type="button" class="mn-btn mn-btn-danger mn-btn-sm" data-mn-action="pdf"><i class="fa fa-file-pdf-o"></i> PDF</button>
        <button type="button" class="mn-btn mn-btn-warning mn-btn-sm" data-mn-action="email"><i class="fa fa-envelope"></i> Email</button>
        <button type="button" class="mn-btn mn-btn-success mn-btn-sm" data-mn-action="whatsapp"><i class="fa fa-whatsapp"></i> WhatsApp</button>
    </div>
</div>