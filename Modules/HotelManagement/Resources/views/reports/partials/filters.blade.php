<form method="GET" class="hm-report-filter hm-toolbar">
    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? request('date_from') }}" style="max-width:170px;">
    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? request('date_to') }}" style="max-width:170px;">
    <button type="submit" class="btn hm-btn-add">Filter</button>
    <button type="submit" name="export" value="csv" class="btn hm-btn-csv">CSV</button>
    <button type="submit" name="export" value="excel" class="btn hm-btn-excel">Excel</button>
    <button type="button" class="btn hm-btn-print" onclick="window.print()">Print</button>
    <button type="button" class="btn hm-btn-col">Column Visibility</button>
</form>
