@extends('expensesnew::layouts.app', ['heading'=>'Expense Summary Report'])
@section('module_content')
<style>
    .expnew-summary-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 14px;
    }
    .expnew-summary-toolbar-left,
    .expnew-summary-toolbar-right {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .expnew-summary-toolbar label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin: 0;
        white-space: nowrap;
        font-weight: 400;
    }
    .expnew-summary-toolbar .form-control {
        height: 34px;
    }
    #expnew_summary_page_length {
        width: 78px;
    }
    #expnew_summary_global_search {
        width: 230px;
    }
    #expnew_summary_export_buttons .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    #expnew_summary_export_buttons .dt-button {
        margin: 0;
    }
    .expnew-summary-table-area {
        width: 78%;
        max-width: 1380px;
        min-width: 720px;
        margin: 0 auto;
    }
    @media (max-width: 991px) {
        .expnew-summary-table-area {
            width: 100%;
            min-width: 0;
        }
        #expnew_summary_global_search {
            width: 180px;
        }
    }
</style>

<div class="expnew-card">
    <div class="expnew-summary-toolbar">
        <div class="expnew-summary-toolbar-left">
            <label>
                Show
                <select id="expnew_summary_page_length" class="form-control input-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="-1">All</option>
                </select>
                entries
            </label>
            <label>
                Search:
                <input id="expnew_summary_global_search"
                       type="search"
                       class="form-control input-sm"
                       autocomplete="off"
                       placeholder="Global search">
            </label>
        </div>
        <div id="expnew_summary_export_buttons" class="expnew-summary-toolbar-right"></div>
    </div>

    <div class="expnew-summary-table-area">
        <div class="table-responsive">
            <table id="expnew_expense_summary_table"
                   class="table table-bordered table-striped"
                   data-url="{{ route('expensesnew.reports.expense_summary.data') }}"
                   style="width:100%">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Count</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    function initialise(attempt) {
        if (!window.jQuery) {
            if (attempt < 40) {
                window.setTimeout(function () { initialise(attempt + 1); }, 50);
            }
            return;
        }

        jQuery(function ($) {
        var selector = '#expnew_expense_summary_table';
        if (!$.fn.DataTable) {
            if (attempt < 40) {
                window.setTimeout(function () { initialise(attempt + 1); }, 50);
            }
            return;
        }
        if (!$(selector).length) {
            return;
        }

        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().destroy();
        }

        var table = $(selector).DataTable({
            ajax: $(selector).data('url'),
            autoWidth: false,
            deferRender: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            dom: 'rtip',
            scrollX: true,
            columns: [
                {data: 'category', width: '30%'},
                {data: 'count', width: '12%', className: 'text-center'},
                {data: 'total', width: '20%', className: 'text-right'},
                {data: 'paid', width: '19%', className: 'text-right'},
                {data: 'due', width: '19%', className: 'text-right'}
            ],
            order: [[0, 'asc']]
        });

        $('#expnew_summary_page_length').on('change.expnewSummary', function () {
            table.page.len(parseInt(this.value, 10)).draw();
        });

        $('#expnew_summary_global_search').on('input.expnewSummary keyup.expnewSummary', function () {
            table.search(this.value).draw();
        });

        if ($.fn.dataTable && $.fn.dataTable.Buttons) {
            new $.fn.dataTable.Buttons(table, {
                buttons: [
                    {extend: 'csvHtml5', text: 'CSV', className: 'btn btn-info btn-sm'},
                    {extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm'},
                    {extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-danger btn-sm'},
                    {extend: 'print', text: 'Print', className: 'btn btn-primary btn-sm'},
                    {extend: 'colvis', text: 'Columns', className: 'btn btn-default btn-sm'}
                ]
            }).container().appendTo('#expnew_summary_export_buttons');
        }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initialise(0); });
    } else {
        initialise(0);
    }
})();
</script>
@endsection
