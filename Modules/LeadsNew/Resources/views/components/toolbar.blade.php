<div class="leads-new-toolbar">
    <div class="ln-toolbar-search-wrap">
        <i class="fa fa-search"></i>
        <input type="text" class="form-control leads-new-search" placeholder="Search displayed records...">
    </div>
    <input type="text" class="form-control leads-new-date-range" placeholder="Date Range">
    <div class="ln-toolbar-actions">
        @if(!empty($csvUrl))
            <a class="btn btn-success" href="{{ $csvUrl }}"><i class="fa fa-file-text-o"></i> CSV</a>
        @else
            <button type="button" class="btn btn-success" disabled title="CSV export is not available for this report"><i class="fa fa-file-text-o"></i> CSV</button>
        @endif
        <button type="button" class="btn btn-primary leads-new-export-excel"><i class="fa fa-file-excel-o"></i> Excel</button>
        <button type="button" class="btn btn-danger leads-new-export-pdf"><i class="fa fa-file-pdf-o"></i> PDF</button>
        <button type="button" class="btn btn-info leads-new-print"><i class="fa fa-print"></i> Print</button>
        <button type="button" class="btn btn-warning leads-new-columns"><i class="fa fa-columns"></i> Columns</button>
    </div>
</div>
