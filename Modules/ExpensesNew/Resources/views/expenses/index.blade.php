@extends('expensesnew::layouts.app', ['heading'=>'List Expenses'])
@section('module_content')
@include('expensesnew::components.toolbar', [
    'createRoute' => route('expensesnew.expenses.create'),
    'createLabel' => '+ Add',
])

<div class="expnew-card expnew-filter-card">
    <div class="row">
        <div class="col-md-4">
            <label>Date Range</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                <input type="text" id="filter_date_range" class="form-control" placeholder="Date Range" autocomplete="off">
            </div>
        </div>
        <div class="col-md-2"><label>Location</label><select id="filter_location_id" class="form-control"><option value="">All</option>@foreach($locations as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label>Category</label><select id="filter_category_id" class="form-control"><option value="">All</option>@foreach($categories as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label>Payee</label><select id="filter_payee_id" class="form-control"><option value="">All</option>@foreach($payees as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label>Status</label><select id="filter_payment_status" class="form-control"><option value="">All</option><option value="paid">Paid</option><option value="partial">Partial</option><option value="due">Due</option></select></div>
    </div>
</div>

<div class="expnew-card">
    <div class="table-responsive">
        <table id="expnew_expenses_table"
               class="table table-bordered table-striped expnew-expenses-list-table"
               data-url="{{ route('expensesnew.expenses.data') }}"
               style="width:100%">
            <thead>
                <tr>
                    {{-- LA-1186: Action moved to the first column. --}}
                    <th class="expnew-action-column">Action</th>
                    <th>Date</th>
                    <th>Expense No</th>
                    <th>Category</th>
                    <th>Payee</th>
                    <th>Expense Account</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($initialExpenses as $e)
                    <tr data-expense-row
                        data-date="{{ $e->expense_date }}"
                        data-location="{{ $e->location_id }}"
                        data-category="{{ $e->category_id }}"
                        data-payee="{{ $e->payee_id }}"
                        data-status="{{ strtolower((string) ($e->payment_status ?: 'due')) }}">
                        <td class="expnew-action-column">@include('expensesnew::expenses.partials.actions', ['e' => $e])</td>
                        <td>{{ $e->expense_date }}</td>
                        <td>{{ $e->expense_no }}</td>
                        <td>{{ $e->category_name ?: '—' }}</td>
                        <td>{{ $e->payee_name ?: '—' }}</td>
                        <td>{{ $e->account_name ?: '—' }}</td>
                        <td class="text-right">{{ number_format((float) $e->total_amount, $currencyPrecision) }}</td>
                        <td class="text-right">{{ number_format((float) $e->paid_amount, $currencyPrecision) }}</td>
                        <td class="text-right">{{ number_format((float) $e->due_amount, $currencyPrecision) }}</td>
                        <td>{{ ucfirst((string) ($e->payment_status ?: 'due')) }}</td>
                    </tr>
                @empty
                    <tr class="expnew-empty-row">
                        <td colspan="10" class="text-center text-muted">No expenses found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    {{-- LA-1186: one more leading cell now that Action is first,
                         so Total still sits under Expense Account and each figure
                         stays beneath its own column. --}}
                    <th colspan="6" class="text-right">Total</th>
                    <th class="text-right">{{ number_format((float) $initialExpenses->sum('total_amount'), $currencyPrecision) }}</th>
                    <th class="text-right">{{ number_format((float) $initialExpenses->sum('paid_amount'), $currencyPrecision) }}</th>
                    <th class="text-right">{{ number_format((float) $initialExpenses->sum('due_amount'), $currencyPrecision) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{--
    LA-1186: the Action menu, and why Delete was missing.

    Delete is already in expenses/partials/actions.blade.php - it has always
    been generated. It could not be SEEN: the menu is positioned inside the
    table, and both .table-responsive and the theme's .box carry an overflow
    that clips anything reaching past their edges. Delete is the LAST entry, so
    it was the first to be cut off - the same reason only "Delete" survived on
    the customer statement list, where the menu opened the other way.

    While a menu is open it is moved to <body> with fixed positioning, so no
    ancestor can clip it, and returned to its exact place on close.
--}}
<style>
    /* Action column: only a small button, so it needs no width. */
    #expnew_expenses_table th.expnew-action-column,
    #expnew_expenses_table td.expnew-action-column {
        width: 1% !important;
        white-space: nowrap !important;
    }

    body > .expnew-action-menu-detached {
        position: fixed !important;
        z-index: 2147483000 !important;
        display: block !important;
        float: none !important;
        margin: 0 !important;
        min-width: 180px !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        background: #fff !important;
        border: 1px solid #dfe6e9 !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .22) !important;
        padding: 6px !important;
        list-style: none !important;
    }

    body > .expnew-action-menu-detached > li { display: block !important; float: none !important; }

    body > .expnew-action-menu-detached > li > a {
        display: block !important;
        padding: 8px 14px !important;
        border-radius: 8px !important;
        color: #34495e !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }

    body > .expnew-action-menu-detached > li > a:hover { background: #f4f7fb !important; }

    body > .expnew-action-menu-detached > li.divider {
        height: 1px !important;
        margin: 6px 4px !important;
        background: #edf2f7 !important;
    }
</style>

<script>
(function ($) {
    'use strict';

    if (!$ || window.__expnewActionMenuLoaded) { return; }
    window.__expnewActionMenuLoaded = true;

    var DETACHED = 'expnew-action-menu-detached';
    var TABLE = '#expnew_expenses_table';

    function restoreAll() {
        $('body').children('.' + DETACHED).each(function () {
            var $menu = $(this);
            var marker = $menu.data('expnewPlaceholder');

            $menu.removeClass(DETACHED).removeAttr('style');

            if (marker && marker.parentNode) {
                marker.parentNode.insertBefore($menu[0], marker);
                marker.parentNode.removeChild(marker);
            }

            $menu.removeData('expnewPlaceholder');
        });
    }

    function place($toggle, $menu) {
        var rect = $toggle[0].getBoundingClientRect();
        var w = $menu.outerWidth();
        var h = $menu.outerHeight();
        var margin = 8;
        var vh = window.innerHeight || document.documentElement.clientHeight;
        var vw = window.innerWidth || document.documentElement.clientWidth;
        var top;

        if (rect.bottom + 4 + h <= vh - margin) {
            top = rect.bottom + 4;
        } else if (rect.top - h - 4 >= margin) {
            top = rect.top - h - 4;
        } else {
            top = Math.max(margin, vh - h - margin);
        }

        var left = rect.left;

        if (left + w > vw - margin) { left = Math.max(margin, vw - w - margin); }
        if (left < margin) { left = margin; }

        $menu.css({ top: Math.round(top) + 'px', left: Math.round(left) + 'px' });
    }

    $(document)
        .off('.expnewActionMenu')
        .on('shown.bs.dropdown.expnewActionMenu', function (e) {
            var $group = $(e.target);

            if (!$group.closest(TABLE).length) { return; }

            restoreAll();

            var $toggle = $group.find('[data-toggle="dropdown"]').first();
            var $menu = $group.children('.dropdown-menu').first();

            if (!$toggle.length || !$menu.length || $menu.parent().is('body')) { return; }

            var marker = document.createComment('expnew-menu');
            $menu[0].parentNode.insertBefore(marker, $menu[0]);
            $menu.data('expnewPlaceholder', marker);
            $menu.addClass(DETACHED).appendTo(document.body);

            place($toggle, $menu);

            // Measured again once laid out, so the detached padding and
            // min-width are included in the height.
            var again = function () { place($toggle, $menu); };

            window.requestAnimationFrame ? window.requestAnimationFrame(again) : window.setTimeout(again, 0);
        })
        .on('hidden.bs.dropdown.expnewActionMenu', function (e) {
            if ($(e.target).closest(TABLE).length) { restoreAll(); }
        });

    // A detached menu has no parent left to scroll with.
    $(window).off('.expnewActionMenu').on('scroll.expnewActionMenu resize.expnewActionMenu', function () {
        if ($('body').children('.' + DETACHED).length) {
            restoreAll();
            $(TABLE).find('.btn-group.open, .dropdown.open').removeClass('open');
        }
    });

    // The delete link is bound by delegation, so it keeps working while detached.
    $(document).on('draw.dt.expnewActionMenu destroy.dt.expnewActionMenu', TABLE, restoreAll);
})(window.jQuery);
</script>

<script>
(function () {
    function boot(attempt) {
        if (!window.jQuery) {
            if (attempt < 60) {
                window.setTimeout(function () { boot(attempt + 1); }, 50);
            }
            return;
        }

        jQuery(function ($) {
            var selector = '#expnew_expenses_table';
            var $table = $(selector);
            var dataTable = null;
            var filterDateFrom = '';
            var filterDateTo = '';
            var currencyPrecision = @json((int) ($currencyPrecision ?? 2));

            function initialiseDateRangeFilter() {
                var $dateRange = $('#filter_date_range');

                if (!$dateRange.length || !$.fn.daterangepicker || typeof dateRangeSettings === 'undefined') {
                    return;
                }

                // Use the application's shared date-range configuration/presets,
                // but keep the expense list unfiltered until the user chooses a range.
                var settings = $.extend(true, {}, dateRangeSettings, {
                    autoUpdateInput: false
                });

                $dateRange.daterangepicker(settings, function (start, end) {
                    filterDateFrom = start.format('YYYY-MM-DD');
                    filterDateTo = end.format('YYYY-MM-DD');
                    $dateRange.val(
                        start.format(typeof moment_date_format !== 'undefined' ? moment_date_format : 'MM/DD/YYYY')
                        + ' - ' +
                        end.format(typeof moment_date_format !== 'undefined' ? moment_date_format : 'MM/DD/YYYY')
                    );
                    $dateRange.trigger('change');
                });

                $dateRange.on('cancel.daterangepicker.expnewExpenses', function () {
                    filterDateFrom = '';
                    filterDateTo = '';
                    $(this).val('').trigger('change');
                });
            }

            function numberValue(value) {
                return typeof value === 'string'
                    ? (parseFloat(value.replace(/,/g, '')) || 0)
                    : (value || 0);
            }

            function initialiseDataTable() {
                if (!$.fn.DataTable || !$table.length) {
                    initialiseFallbackFilters();
                    return;
                }

                $table.find('tbody .expnew-empty-row').remove();

                if ($.fn.DataTable.isDataTable(selector)) {
                    $table.DataTable().destroy();
                }

                dataTable = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    deferRender: true,
                    pageLength: 25,
                    ajax: {
                        url: $table.data('url'),
                        data: function (data) {
                            data.date_from = filterDateFrom;
                            data.date_to = filterDateTo;
                            data.location_id = $('#filter_location_id').val();
                            data.category_id = $('#filter_category_id').val();
                            data.payee_id = $('#filter_payee_id').val();
                            data.payment_status = $('#filter_payment_status').val();
                        }
                    },
                    columns: [
                        {data: 'action', orderable: false, searchable: false},
                        {data: 'date'},
                        {data: 'expense_no'},
                        {data: 'category'},
                        {data: 'payee'},
                        {data: 'account'},
                        {data: 'total_amount', className: 'text-right'},
                        {data: 'paid_amount', className: 'text-right'},
                        {data: 'due_amount', className: 'text-right'},
                        {data: 'payment_status'}
                    ],
                    order: [[1, 'desc'], [2, 'desc']],
                    scrollX: true,
                    footerCallback: function () {
                        var api = this.api();
                        [6, 7, 8].forEach(function (index) {
                            var total = api.column(index, {page: 'current'}).data().reduce(function (a, b) {
                                return numberValue(a) + numberValue(b);
                            }, 0);
                            $(api.column(index).footer()).html(total.toLocaleString('en-US', {
                                minimumFractionDigits: currencyPrecision,
                                maximumFractionDigits: currencyPrecision
                            }));
                        });
                    }
                });

                $('.expnew-filter-card select, .expnew-filter-card input')
                    .off('change.expnewExpenses')
                    .on('change.expnewExpenses', function () {
                        dataTable.ajax.reload(null, true);
                    });

                $('.expnew-search')
                    .off('keyup.expnewExpenses')
                    .on('keyup.expnewExpenses', function () {
                        dataTable.search(this.value).draw();
                    });
            }

            function initialiseFallbackFilters() {
                function applyFilters() {
                    var search = String($('.expnew-search').val() || '').toLowerCase();
                    var dateFrom = filterDateFrom;
                    var dateTo = filterDateTo;
                    var location = String($('#filter_location_id').val() || '');
                    var category = String($('#filter_category_id').val() || '');
                    var payee = String($('#filter_payee_id').val() || '');
                    var status = String($('#filter_payment_status').val() || '');
                    var visibleCount = 0;

                    $table.find('tbody tr[data-expense-row]').each(function () {
                        var $row = $(this);
                        var rowDate = String($row.data('date') || '');
                        var visible = (!search || $row.text().toLowerCase().indexOf(search) !== -1)
                            && (!dateFrom || rowDate >= dateFrom)
                            && (!dateTo || rowDate <= dateTo)
                            && (!location || String($row.data('location') || '') === location)
                            && (!category || String($row.data('category') || '') === category)
                            && (!payee || String($row.data('payee') || '') === payee)
                            && (!status || String($row.data('status') || '') === status);
                        $row.toggle(visible);
                        if (visible) {
                            visibleCount++;
                        }
                    });

                    $table.find('.expnew-empty-row').toggle(visibleCount === 0);
                }

                $('.expnew-filter-card select, .expnew-filter-card input')
                    .off('change.expnewExpensesFallback')
                    .on('change.expnewExpensesFallback', applyFilters);
                $('.expnew-search')
                    .off('keyup.expnewExpensesFallback')
                    .on('keyup.expnewExpensesFallback', applyFilters);
            }

            $(document)
                .off('click.expnewExpenseDelete', '.expnew-delete')
                .on('click.expnewExpenseDelete', '.expnew-delete', function (event) {
                    event.preventDefault();
                    var $button = $(this);
                    if (!window.confirm('Delete this expense?')) {
                        return;
                    }

                    $button.addClass('disabled').attr('aria-disabled', 'true');
                    $.ajax({
                        url: $button.data('url'),
                        type: 'DELETE',
                        data: {_token: $('meta[name="csrf-token"]').attr('content')}
                    }).done(function () {
                        if (dataTable) {
                            dataTable.ajax.reload(null, false);
                        } else {
                            $button.closest('tr').remove();
                        }
                    }).fail(function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Unable to delete the expense.';
                        window.alert(message);
                        $button.removeClass('disabled').removeAttr('aria-disabled');
                    });
                });

            initialiseDateRangeFilter();
            initialiseDataTable();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { boot(0); });
    } else {
        boot(0);
    }
})();
</script>
@endsection
