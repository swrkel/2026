<style>
.erp-records-toolbar {
    background: #ffffff;
    border-radius: 20px;
    padding: 16px;
    margin-bottom: 22px;
    box-shadow: 0 10px 28px rgba(15, 76, 129, 0.08);
    border: 1px solid #edf1f5;
}

.erp-toolbar-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.erp-toolbar-left,
.erp-toolbar-right,
.erp-toolbar-buttons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.erp-toolbar-label {
    font-weight: 700;
    color: #2c3e50;
}

.erp-toolbar-control {
    height: 40px;
    border-radius: 10px;
    border: 1px solid #dfe6e9;
    padding: 7px 12px;
    font-size: 15px;
    background: #ffffff;
    box-shadow: none;
}

.erp-toolbar-search {
    min-width: 260px;
}

.erp-date-range-wrapper {
    min-width: 280px;
}

.erp-date-range-wrapper .input-group-addon {
    border-radius: 10px 0 0 10px;
    background: #f8fafc;
    border: 1px solid #dfe6e9;
    border-right: none;
}

.erp-date-range-wrapper .erp-date-range-picker {
    height: 40px;
    border-radius: 0 10px 10px 0;
    background: #ffffff;
    cursor: pointer;
}

.erp-toolbar-btn {
    border: none;
    background: linear-gradient(135deg, #0b5ed7 0%, #06b6d4 100%);
    color: #ffffff;
    border-radius: 9px;
    padding: 8px 12px;
    font-size: 15px;
    font-weight: 700;
}

.erp-toolbar-btn:hover {
    background: linear-gradient(135deg, #084bb0 0%, #0891b2 100%);
    color: #ffffff;
}

.erp-custom-colvis-menu {
    background: #f39c12 !important;
    color: #ffffff !important;
    border: 1px solid #e67e22 !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25) !important;
    padding: 8px !important;
    z-index: 999999 !important;
    min-width: 240px !important;
}

.erp-custom-colvis-menu label {
    display: block;
    padding: 8px 12px;
    cursor: pointer;
    color: #ffffff;
    border-radius: 6px;
    margin-bottom: 4px;
    font-weight: 600;
}

.erp-custom-colvis-menu label:hover {
    background: #e67e22;
}

.erp-custom-colvis-menu input {
    margin-right: 8px;
}

@media(max-width: 767px) {
    .erp-toolbar-top,
    .erp-toolbar-left,
    .erp-toolbar-right,
    .erp-toolbar-buttons,
    .erp-date-range-wrapper {
        width: 100%;
    }

    .erp-toolbar-control,
    .erp-toolbar-search {
        width: 100%;
        min-width: 100%;
    }
}
</style>

<div class="erp-records-toolbar">

    <div class="erp-toolbar-top">

        <div class="erp-toolbar-left">

            <label class="erp-toolbar-label">Show</label>

            <select id="erp_records_per_page"
                    class="erp-toolbar-control erp-ajax-filter">
                @foreach([10, 25, 50, 100, 200, 500] as $size)
                    <option value="{{ $size }}" {{ $size == 10 ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>

            <span class="erp-toolbar-label">records</span>

            <div class="input-group erp-date-range-wrapper">
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>

                <input type="text"
                       id="erp_date_range_filter"
                       class="form-control erp-date-range-picker"
                       readonly
                       placeholder="YYYY-MM-DD ~ YYYY-MM-DD">
            </div>

        </div>

        <div class="erp-toolbar-right">
            <input type="text"
                   id="erp_global_search"
                   class="erp-toolbar-control erp-toolbar-search"
                   placeholder="Search records..."
                   autocomplete="off">
        </div>

    </div>

    <div class="erp-toolbar-buttons">

        <button type="button" class="erp-toolbar-btn erp-export-csv">
            <i class="fa fa-file-text-o"></i> Export to CSV
        </button>

        <button type="button" class="erp-toolbar-btn erp-export-excel">
            <i class="fa fa-file-excel-o"></i> Export to Excel
        </button>

        <button type="button" class="erp-toolbar-btn erp-column-visibility">
            <i class="fa fa-columns"></i> Column Visibility
        </button>

        <button type="button" class="erp-toolbar-btn erp-export-pdf">
            <i class="fa fa-file-pdf-o"></i> Export to PDF
        </button>

        <button type="button" class="erp-toolbar-btn erp-print">
            <i class="fa fa-print"></i> Print
        </button>

    </div>

</div>

<script>
$(document).ready(function () {

    let erpSearchTimer = null;

    function getErpDataTable() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#erp_ajax_table')) {
            return $('#erp_ajax_table').DataTable();
        }

        return null;
    }

    function redrawErpTable() {
        let table = getErpDataTable();

        if (table) {
            table.draw(false);
        }
    }

    $('#erp_records_per_page').on('change', function () {
        let table = getErpDataTable();

        if (table) {
            table.page.len($(this).val()).draw(false);
        }
    });

    $('#erp_global_search').on('keyup', function () {
        let searchValue = $(this).val();

        clearTimeout(erpSearchTimer);

        erpSearchTimer = setTimeout(function () {
            let table = getErpDataTable();

            if (table) {
                table.search(searchValue).draw(false);
            }
        }, 150);
    });

    if ($.fn.daterangepicker) {

        let erpToday = moment();

        $('#erp_date_range_filter').daterangepicker({
            startDate: erpToday,
            endDate: erpToday,
            autoUpdateInput: true,
            alwaysShowCalendars: true,
            showDropdowns: true,

            locale: {
                format: 'YYYY-MM-DD',
                separator: ' ~ ',
                applyLabel: 'Apply',
                cancelLabel: 'Clear',
                customRangeLabel: 'Custom Date Range'
            },

            ranges: {
                'Today': [
                    moment(),
                    moment()
                ],
                'Yesterday': [
                    moment().subtract(1, 'days'),
                    moment().subtract(1, 'days')
                ],
                'Last 7 Days': [
                    moment().subtract(6, 'days'),
                    moment()
                ],
                'Last 30 Days': [
                    moment().subtract(29, 'days'),
                    moment()
                ],
                'This Month': [
                    moment().startOf('month'),
                    moment().endOf('month')
                ],
                'Last Month': [
                    moment().subtract(1, 'month').startOf('month'),
                    moment().subtract(1, 'month').endOf('month')
                ],
                'This month last year': [
                    moment().subtract(1, 'year').startOf('month'),
                    moment().subtract(1, 'year').endOf('month')
                ],
                'This Year': [
                    moment().startOf('year'),
                    moment().endOf('year')
                ],
                'Last Year': [
                    moment().subtract(1, 'year').startOf('year'),
                    moment().subtract(1, 'year').endOf('year')
                ],
                'Current financial year': [
                    moment().month(3).startOf('month'),
                    moment().month(2).endOf('month').add(1, 'year')
                ],
                'Last financial year': [
                    moment().month(3).startOf('month').subtract(1, 'year'),
                    moment().month(2).endOf('month')
                ]
            }

        }, function(start, end) {
            $('#erp_date_range_filter').val(
                start.format('YYYY-MM-DD') +
                ' ~ ' +
                end.format('YYYY-MM-DD')
            );
        });

        $('#erp_date_range_filter').val(
            erpToday.format('YYYY-MM-DD') +
            ' ~ ' +
            erpToday.format('YYYY-MM-DD')
        );

        $('#erp_date_range_filter').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(
                picker.startDate.format('YYYY-MM-DD') +
                ' ~ ' +
                picker.endDate.format('YYYY-MM-DD')
            );

            redrawErpTable();
        });

        $('#erp_date_range_filter').on('cancel.daterangepicker', function() {
            $(this).val('');

            redrawErpTable();
        });
    }

    $('.erp-export-csv').on('click', function () {
        $('.buttons-csv').click();
    });

    $('.erp-export-excel').on('click', function () {
        $('.buttons-excel').click();
    });

    $('.erp-column-visibility').on('click', function () {

        let table = getErpDataTable();

        if (!table) {
            return;
        }

        $('.erp-custom-colvis-menu').remove();

        let menu = $('<div class="erp-custom-colvis-menu"></div>');

        table.columns().every(function (index) {

            let column = this;
            let title = $(column.header()).text().trim();

            if (title === '') {
                title = 'Column ' + (index + 1);
            }

            let checked = column.visible() ? 'checked' : '';

            let item = $(
                '<label>' +
                    '<input type="checkbox" class="erp-colvis-toggle" data-column="' + index + '" ' + checked + '> ' +
                    title +
                '</label>'
            );

            menu.append(item);
        });

        $('body').append(menu);

        let offset = $('.erp-column-visibility').offset();

        menu.css({
            position: 'absolute',
            top: offset.top + $('.erp-column-visibility').outerHeight() + 6,
            left: offset.left
        });

    });

    $(document).on('change', '.erp-colvis-toggle', function () {

        let table = getErpDataTable();

        if (!table) {
            return;
        }

        let columnIndex = $(this).data('column');

        table.column(columnIndex).visible($(this).is(':checked'));

    });

    $(document).on('click', function (e) {

        if (
            !$(e.target).closest('.erp-column-visibility').length &&
            !$(e.target).closest('.erp-custom-colvis-menu').length
        ) {
            $('.erp-custom-colvis-menu').remove();
        }

    });

    $('.erp-export-pdf').on('click', function () {
        $('.buttons-pdf').click();
    });

    $('.erp-print').on('click', function () {
        $('.buttons-print').click();
    });

});
</script>