{{--
 |------------------------------------------------------------------------------
 | The standard report toolbar - exports, column visibility, print and search.
 |------------------------------------------------------------------------------
 |
 | Matches the toolbar on Purchase / List Purchase Entries, so the two behave the
 | same way.
 |
 | WHY THE EXPORTS GO TO THE SERVER
 |
 |   Client-side export buttons work on the rows the browser holds. Several of
 |   these reports are long, and one - the trial balance - is a statement that is
 |   meaningless if it is partial. An export that quietly stopped at the visible
 |   rows while looking complete would be worse than no export at all, so CSV,
 |   Excel and PDF re-run the SAME filters on the server and return every matching
 |   row.
 |
 |   They do that by coming back to the current URL with ?export=, which means
 |   they inherit whatever period, location and account are already applied
 |   without a second route or a duplicate query to keep in step.
 |
 | WHY PRINT AND COLUMN VISIBILITY ARE NOT SERVER-SIDE
 |
 |   Printing prints what is displayed, and hiding a column is a display choice.
 |   Both genuinely belong in the browser.
 |
 | USE
 |
 |   @include('financereports::layouts.toolbar', ['table' => 'trial_balance_table'])
 |
 |   `table` is the id of the table on the page. Column Visibility and Print need
 |   it; the exports do not.
 --}}
@php
    /*
     | $table is OPTIONAL.
     |
     | None of the 49 report views gives its table an id, and adding one to each
     | would mean editing 49 files to gain nothing the toolbar cannot work out for
     | itself. When no id is passed the script finds the report table on the page
     | - see findTable() below - so a report gets the full toolbar simply by
     | including this partial.
     |
     | Pass $table only where a page has more than one table and the toolbar should
     | act on a specific one.
     */
    $table = $table ?? null;
    $printRoot = $printRoot ?? null;
    $searchPlaceholder = $searchPlaceholder ?? 'Search ...';
@endphp

<div class="fr-toolbar"
     @if($table) data-fr-toolbar-table="{{ $table }}" @endif
     @if($printRoot) data-fr-print-root="{{ $printRoot }}" @endif>
    <div class="fr-toolbar-buttons">
        <a class="btn btn-sm fr-toolbar-btn fr-toolbar-csv"
           href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
            <i class="fa fa-file-text-o"></i> Export to CSV
        </a>
        <a class="btn btn-sm fr-toolbar-btn fr-toolbar-excel"
           href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}">
            <i class="fa fa-file-excel-o"></i> Export to Excel
        </a>

        <button type="button" class="btn btn-sm fr-toolbar-btn fr-toolbar-colvis" data-fr-colvis>
            <i class="fa fa-columns"></i> Column Visibility
        </button>

        <a class="btn btn-sm fr-toolbar-btn fr-toolbar-pdf"
           target="_blank" rel="noopener"
           href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">
            <i class="fa fa-file-pdf-o"></i> Export to PDF
        </a>

        <button type="button" class="btn btn-sm fr-toolbar-btn fr-toolbar-print" data-fr-print>
            <i class="fa fa-print"></i> Print
        </button>

        {{-- Built from the table's own headers, so a column added later appears
             here without this partial being edited. --}}
        <div class="fr-colvis-menu" data-fr-colvis-menu hidden></div>
    </div>

    <div class="fr-toolbar-controls">
            <label class="fr-toolbar-label">
                Show
                <select class="form-control input-sm" data-fr-page-length>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="-1">All</option>
                </select>
                entries
            </label>

            <label class="fr-toolbar-label">
                <input type="text" class="form-control input-sm" data-fr-search
                       placeholder="{{ $searchPlaceholder }}">
            </label>
    </div>
</div>

@once
<style>
    .fr-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .fr-toolbar-buttons { position: relative; display: flex; flex-wrap: wrap; gap: 8px; }
    .fr-toolbar-controls { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }

    .fr-toolbar-label { font-weight: 500; color: #475569; display: flex; align-items: center; gap: 6px; margin: 0; }
    .fr-toolbar-label select { width: auto; display: inline-block; }
    .fr-toolbar-label input[type=text] { min-width: 210px; }

    .fr-toolbar-btn { color: #fff !important; border: 0; font-weight: 600; }
    .fr-toolbar-btn:hover, .fr-toolbar-btn:focus { color: #fff !important; opacity: .92; }
    .fr-toolbar-csv    { background: #0d9488; }
    .fr-toolbar-excel  { background: #16a34a; }
    .fr-toolbar-colvis { background: #6d28d9; }
    .fr-toolbar-pdf    { background: #ea580c; }
    .fr-toolbar-print  { background: #1e293b; }

    .fr-colvis-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        margin-top: 4px;
        min-width: 230px;
        max-height: 320px;
        overflow-y: auto;
        padding: 8px;
        background: #fff;
        border: 1px solid #dfe6e9;
        border-radius: 10px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .18);
    }

    .fr-colvis-menu label {
        display: block;
        font-weight: 400;
        padding: 5px 8px;
        margin: 0;
        border-radius: 6px;
        cursor: pointer;
    }

    .fr-colvis-menu label:hover { background: #f4f7fb; }
    .fr-colvis-menu input { margin-right: 8px; }

    .fr-print-shell { display: none; }

    @media print {
        body.fr-printing > *:not(.fr-print-root) { display: none !important; }
        body.fr-printing .fr-print-root { display: block !important; }
        body.fr-printing .fr-print-shell { display: block !important; }
        body.fr-printing .fr-toolbar,
        body.fr-printing .no-print { display: none !important; }
        body.fr-printing .table-responsive { overflow: visible !important; }
    }
</style>

<script>
(function ($) {
    'use strict';

    if (!$ || window.__frToolbarLoaded) { return; }
    window.__frToolbarLoaded = true;

    /*
     * The table this toolbar acts on.
     *
     * An explicit id wins. Otherwise the first table on the page that has a
     * tbody with rows is used - the report itself. Tables without rows are
     * skipped so an empty filter panel or a layout table cannot be picked by
     * mistake, and the search is limited to the toolbar's own container first so
     * a page with two reports keeps them separate.
     */
    function tableFor($toolbar) {
        var id = $toolbar.data('fr-toolbar-table');

        if (id) {
            return $('#' + id);
        }

        var $cached = $toolbar.data('frResolvedTable');

        if ($cached && $cached.length && $.contains(document, $cached[0])) {
            return $cached;
        }

        var $candidates = $toolbar.closest('.box, .card, .panel, form, section, body')
            .find('table')
            .filter(function () {
                return $(this).find('tbody tr').length > 0;
            });

        if (!$candidates.length) {
            $candidates = $('table').filter(function () {
                return $(this).find('tbody tr').length > 0;
            });
        }

        var $table = $candidates.first();

        $toolbar.data('frResolvedTable', $table);

        return $table;
    }

    /*
     * Column visibility, built from the table's own headers. A report that gains
     * a column later needs no change here.
     */
    $(function () {
        $('.fr-toolbar').each(function () {
            var $toolbar = $(this);
            var $table = tableFor($toolbar);
            var $menu = $toolbar.find('[data-fr-colvis-menu]');

            if (!$table.length || !$menu.length) { return; }

            $table.find('thead th').each(function (index) {
                var label = $.trim($(this).text()) || ('Column ' + (index + 1));

                $('<label>')
                    .append($('<input>', { type: 'checkbox', checked: true, 'data-col': index }))
                    .append(document.createTextNode(label))
                    .appendTo($menu);
            });

            $menu.on('change', 'input[data-col]', function () {
                var index = parseInt($(this).data('col'), 10);
                var show = $(this).is(':checked');

                // Profit Breakdown has a normal one-cell-per-column footer, so
                // keep the footer aligned with the hidden/shown body columns.
                $table.find('thead tr, tbody tr, tfoot tr').each(function () {
                    var $cells = $(this).children();
                    if (index < $cells.length) {
                        $cells.eq(index).toggle(show);
                    }
                });
            });
        });
    });

    $(document).on('click', '[data-fr-colvis]', function (e) {
        e.stopPropagation();

        var $menu = $(this).closest('.fr-toolbar').find('[data-fr-colvis-menu]');

        $menu.prop('hidden', !$menu.prop('hidden'));
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.fr-toolbar-buttons').length) {
            $('[data-fr-colvis-menu]').prop('hidden', true);
        }
    });

    /*
     * Search and page length work on the rendered table. These reports render
     * their rows server-side in full - there is no pagination to fight with - so
     * filtering here is honest: it hides rows that are already present rather
     * than pretending to search a larger set.
     */
    $(document).on('input', '[data-fr-search]', function () {
        var $table = tableFor($(this).closest('.fr-toolbar'));
        var needle = $.trim($(this).val()).toLowerCase();

        $table.find('tbody tr').each(function () {
            var $row = $(this);

            $row.toggle(needle === '' || $row.text().toLowerCase().indexOf(needle) > -1);
        });
    });

    $(document).on('change', '[data-fr-page-length]', function () {
        var $table = tableFor($(this).closest('.fr-toolbar'));
        var limit = parseInt($(this).val(), 10);

        $table.find('tbody tr').each(function (index) {
            $(this).toggle(limit === -1 || index < limit);
        });
    });

    $(document).on('click', '[data-fr-print]', function () {
        var $toolbar = $(this).closest('.fr-toolbar');
        var $table = tableFor($toolbar);
        var rootId = $toolbar.data('fr-print-root');
        var $root = rootId ? $('#' + rootId) : $();

        if (!$root.length) {
            $root = $table.closest('.box, .card, .panel, .table-responsive').first();
        }
        if (!$root.length) {
            $root = $table;
        }
        if (!$root.length) {
            return;
        }

        /*
         * The report lives inside <section>, not directly under <body>. The old
         * print CSS hid that parent section and therefore hid the report itself.
         * Clone the requested report into a direct body child so print isolation
         * is deterministic on every layout.
         */
        $('.fr-print-shell').remove();

        var $shell = $('<div class="fr-print-root fr-print-shell">');
        $shell.append($root.clone(true, true));
        $('body').append($shell).addClass('fr-printing');

        window.print();

        window.setTimeout(function () {
            $('body').removeClass('fr-printing');
            $('.fr-print-shell').remove();
        }, 800);
    });
})(window.jQuery);
</script>
@endonce
