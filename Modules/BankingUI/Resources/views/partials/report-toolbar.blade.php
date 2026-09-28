<div class="bkg-report-toolbar" data-report-toolbar>
    <div class="bkg-toolbar-left">
        <input type="text" name="search" class="form-control bkg-search" placeholder="Search" value="{{ request('search') }}">
    </div>
    <div class="bkg-toolbar-right">
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
        <button type="button" class="btn btn-primary bkg-export" data-export="csv">CSV</button>
        <button type="button" class="btn btn-success bkg-export" data-export="excel">Excel</button>
        <button type="button" class="btn btn-danger bkg-export" data-export="pdf">PDF</button>
        <button type="button" class="btn btn-info bkg-export" data-export="print">Print</button>
        <button type="button" class="btn btn-secondary bkg-columns">Column Visibility</button>
    </div>
</div>
