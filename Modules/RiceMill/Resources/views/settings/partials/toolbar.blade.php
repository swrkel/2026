@php
    $tableId = $tableId ?? 'rcm-settings-table';
    $exportName = $exportName ?? 'rice-mill-settings';
@endphp
<div class="rcm-standard-toolbar" data-rcm-table-toolbar data-table-id="{{ $tableId }}" data-export-name="{{ $exportName }}">
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
    <div class="rcm-standard-toolbar-right">
        <label class="rcm-search-wrap"><i class="fa fa-search"></i><input type="search" data-rcm-table-search placeholder="Global Search"></label>
        <label class="rcm-page-size-wrap">Rows per page
            <select data-rcm-page-size>
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">All</option>
            </select>
        </label>
    </div>
</div>
<div class="rcm-table-statusbar" data-rcm-table-status="{{ $tableId }}">
    <span data-rcm-table-info></span>
    <div class="rcm-table-pager">
        <button type="button" data-rcm-page-prev aria-label="Previous page"><i class="fa fa-chevron-left"></i></button>
        <span data-rcm-page-number>1</span>
        <button type="button" data-rcm-page-next aria-label="Next page"><i class="fa fa-chevron-right"></i></button>
    </div>
</div>
