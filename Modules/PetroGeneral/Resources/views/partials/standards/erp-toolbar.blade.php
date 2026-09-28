<div class="pg-toolbar erp-records-toolbar" data-pg-toolbar="1">
    <div class="pg-toolbar-left">
        <input type="text" class="form-control pg-global-search" placeholder="Search" data-target-table="{{ $targetTable ?? '' }}">
        <input type="text" class="form-control pg-date-range" placeholder="YYYY-MM-DD ~ YYYY-MM-DD" data-target-table="{{ $targetTable ?? '' }}">
    </div>
    <div class="pg-toolbar-right">
        <button type="button" class="btn btn-default btn-sm pg-export" data-type="csv">CSV</button>
        <button type="button" class="btn btn-default btn-sm pg-export" data-type="excel">Excel</button>
        <button type="button" class="btn btn-default btn-sm pg-export" data-type="pdf">PDF</button>
        <button type="button" class="btn btn-default btn-sm pg-print">Print</button>
        <div class="btn-group pg-colvis-wrap">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                Column Visibility <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right pg-colvis-menu"></ul>
        </div>
    </div>
</div>
