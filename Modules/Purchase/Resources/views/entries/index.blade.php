@extends('layouts.app')
@section('title', __('purchase::lang.purchase_entries'))

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>

<section class="content purchase-workspace">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-shopping-cart"></i> List Purchase Entries</h1>
            <div class="purchase-breadcrumb">Purchase (New) / List Purchase Entries</div>
        </div>
        <div class="purchase-workspace-actions">
            @if(app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canCreate())
                <a href="{{ route('purchase.entries.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Purchase Entry
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        @php($status = session('status'))
        <div class="alert {{ !empty($status['success']) ? 'alert-success' : 'alert-danger' }}">
            {{ $status['msg'] ?? $status['message'] ?? '' }}
        </div>
    @endif

    <div class="purchase-panel">
        <div class="purchase-panel-heading">
            <span><i class="fa fa-filter"></i> Filters</span>
            <a href="{{ route('purchase.entries.index') }}" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i> Reset</a>
        </div>
        <div class="purchase-panel-body">
            <form method="get" action="{{ route('purchase.entries.index') }}" id="purchase_entry_filters">
                <div class="purchase-filter-grid">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Purchase no., reference or supplier">
                    </div>
                    <div class="form-group">
                        <label>Business Location</label>
                        <select name="location_id" class="form-control select2" style="width:100%">
                            <option value="">All</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected((string)$filters['location_id'] === (string)$location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control select2" style="width:100%">
                            <option value="">All</option>
                            @foreach($suppliers as $supplier)
                                @php($supplierLabel = trim((string)($supplier->supplier_business_name ?: $supplier->name)))
                                <option value="{{ $supplier->id }}" @selected((string)$filters['supplier_id'] === (string)$supplier->id)>
                                    {{ $supplierLabel }}{{ $supplier->contact_id ? ' - '.$supplier->contact_id : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach(['received' => 'Received', 'pending' => 'Pending', 'ordered' => 'Ordered'] as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control">
                            <option value="">All</option>
                            @foreach(['paid' => 'Paid', 'partial' => 'Partial', 'due' => 'Due'] as $value => $label)
                                <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="purchase-summary-strip">
        <div class="purchase-summary-tile"><span>Records</span><strong>{{ number_format($summary['record_count']) }}</strong></div>
        <div class="purchase-summary-tile"><span>Purchase Total</span><strong>{{ number_format($summary['total_amount'], 2) }}</strong></div>
        <div class="purchase-summary-tile"><span>Paid</span><strong>{{ number_format($summary['paid_amount'], 2) }}</strong></div>
        <div class="purchase-summary-tile"><span>Due</span><strong>{{ number_format($summary['due_amount'], 2) }}</strong></div>
    </div>

    <div class="purchase-panel">
        <div class="purchase-panel-heading">
            <span><i class="fa fa-list"></i> Purchase Entries</span>
            <span>{{ number_format($rows->total()) }} record(s)</span>
        </div>
        <div class="purchase-panel-body">
            {{--
                IS2152: export, column visibility, page size and search.

                The exports are SERVER-SIDE links carrying the current query
                string, so a downloaded file covers every row matching the
                filters - not just the 25 on screen. A client-side export would
                only see the rendered page and would produce a file that looks
                complete and is not.

                Column Visibility and Search act on the rendered table, which is
                correct for them: both are ways of looking at what is already in
                front of you.
            --}}
            <div class="purchase-toolbar">
                <div class="purchase-toolbar-actions">
                    <a href="{{ route('purchase.entries.export.csv', request()->query()) }}" class="pt-btn pt-csv"><i class="fa fa-file-text-o"></i> Export to CSV</a>
                    <a href="{{ route('purchase.entries.export.excel', request()->query()) }}" class="pt-btn pt-excel"><i class="fa fa-file-excel-o"></i> Export to Excel</a>
                    <button type="button" class="pt-btn pt-columns" id="purchaseColumnToggle"><i class="fa fa-columns"></i> Column Visibility</button>
                    <a href="{{ route('purchase.entries.export.print', request()->query()) }}" target="_blank" class="pt-btn pt-pdf"><i class="fa fa-file-pdf-o"></i> Export to PDF</a>
                    <a href="{{ route('purchase.entries.export.print', request()->query()) }}" target="_blank" class="pt-btn pt-print"><i class="fa fa-print"></i> Print</a>
                </div>

                <div class="purchase-toolbar-right">
                    <label class="pt-inline">Show
                        <select id="purchasePerPage" class="form-control input-sm">
                            @foreach ([10, 25, 50, 100, 200] as $size)
                                <option value="{{ $size }}" {{ (int) request()->query('per_page', 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                        entries
                    </label>

                    <input type="text" id="purchaseTableSearch" class="form-control input-sm pt-search" placeholder="Search ...">
                </div>
            </div>

            {{-- Hidden until the button is pressed; one checkbox per column. --}}
            <div class="purchase-column-panel" id="purchaseColumnPanel" style="display:none;"></div>

            @include('purchase::entries.partials.table')
            <div class="text-right">{{ $rows->links() }}</div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<style>
    /* IS2152: toolbar. */
    .purchase-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; flex-wrap: wrap; margin-bottom: 14px;
    }
    .purchase-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .purchase-toolbar-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    .pt-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 14px; border: 0; border-radius: 10px;
        font-size: 13px; font-weight: 700; color: #fff;
        cursor: pointer; text-decoration: none;
        box-shadow: 0 6px 16px rgba(2,6,23,.10);
        transition: .18s ease;
    }
    .pt-btn:hover, .pt-btn:focus { color: #fff; text-decoration: none; transform: translateY(-1px); }

    .pt-csv    { background: linear-gradient(135deg,#0d9488,#14b8a6); }
    .pt-excel  { background: linear-gradient(135deg,#15803d,#22a355); }
    .pt-columns{ background: linear-gradient(135deg,#4f46e5,#6366f1); }
    .pt-pdf    { background: linear-gradient(135deg,#ea580c,#f97316); }
    .pt-print  { background: linear-gradient(135deg,#1e293b,#334155); }

    .pt-inline { font-size: 13px; font-weight: 600; color: #334155; margin: 0; display: flex; align-items: center; gap: 8px; }
    .pt-inline select { width: auto; display: inline-block; border-radius: 8px; }
    .pt-search { width: 220px; border-radius: 10px; }

    .purchase-column-panel {
        background: #f8fafc; border: 1px solid #dbe7f3; border-radius: 12px;
        padding: 12px 14px; margin-bottom: 14px;
        display: flex; gap: 16px; flex-wrap: wrap;
    }
    .purchase-column-panel label {
        font-size: 13px; font-weight: 600; color: #334155;
        display: inline-flex; align-items: center; gap: 6px; margin: 0;
    }
</style>

<script>
(function ($) {
    'use strict';
    if (!$) { return; }

    $(function () {
        var $table = $('.purchase-panel-body table').first();

        /*
         * IS2152: "Show N entries" reloads with per_page in the URL.
         *
         * The list is paginated on the SERVER, so the page size has to be a
         * round trip - changing it in the browser could only hide rows that were
         * already fetched, which is not the same thing.
         *
         * The existing query string is preserved so the filters survive.
         */
        $('#purchasePerPage').on('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', $(this).val());
            url.searchParams.delete('page');   // a new page size invalidates the page number
            window.location.href = url.toString();
        });

        /*
         * Search filters the rows ON THIS PAGE.
         *
         * Deliberately client-side: the panel above already has a server-side
         * Search that queries every record. This one is a quick narrowing of
         * what is in front of you, which is what the toolbar search does on the
         * other list screens.
         */
        $('#purchaseTableSearch').on('input', function () {
            var term = $.trim($(this).val()).toLowerCase();

            $table.find('tbody tr').each(function () {
                var $row = $(this);
                $row.toggle(term === '' || $row.text().toLowerCase().indexOf(term) !== -1);
            });
        });

        /*
         * Column Visibility: one checkbox per column, built from the table's own
         * headings so it cannot fall out of step when a column is added.
         */
        var $panel = $('#purchaseColumnPanel');

        $table.find('thead th').each(function (index) {
            var label = $.trim($(this).text()) || ('Column ' + (index + 1));

            $panel.append(
                $('<label>').append(
                    $('<input>', { type: 'checkbox', checked: true, 'data-col': index }),
                    $('<span>').text(label)
                )
            );
        });

        $panel.on('change', 'input[type="checkbox"]', function () {
            var index = $(this).data('col');
            var show = $(this).is(':checked');

            // nth-child is 1-based.
            $table.find('tr').each(function () {
                $(this).children().eq(index).toggle(show);
            });
        });

        $('#purchaseColumnToggle').on('click', function () {
            $panel.slideToggle(120);
        });
    });
}(window.jQuery));
</script>

<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-entry-list.js')) !!}</script>
@endsection
