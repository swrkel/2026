<style>
.erp-ajax-grid-toolbar {
    background: #ffffff;
    border-radius: 20px;
    padding: 12px;
    margin-bottom: 16px;
    box-shadow: 0 10px 28px rgba(15, 76, 129, 0.08);
    border: 1px solid #edf1f5;
    position: relative;
    z-index: 1;
}

.erp-ajax-grid-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.erp-ajax-grid-left,
.erp-ajax-grid-actions,
.erp-ajax-grid-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.erp-ajax-grid-right {
    flex: 1;
    justify-content: flex-end;
}

.erp-ajax-grid-label {
    font-weight: 700;
    color: #2c3e50;
    white-space: nowrap;
}

.erp-ajax-grid-control {
    height: 38px;
    border-radius: 9px;
    border: 1px solid #dfe6e9;
    padding: 7px 10px;
    font-size: 15px;
    background: #ffffff;
}

.erp-ajax-per-page {
    width: 78px;
}

.erp-ajax-grid-search {
    width: 260px;
}

.erp-ajax-date-wrapper {
    width: 285px;
}

.erp-ajax-date-wrapper .input-group-addon {
    border-radius: 9px 0 0 9px;
    background: #f8fafc;
    border: 1px solid #dfe6e9;
    border-right: none;
}

.erp-ajax-date-wrapper .erp-ajax-date-range {
    height: 38px;
    border-radius: 0 9px 9px 0;
    background: #ffffff;
    cursor: pointer;
}

.erp-ajax-grid-btn,
.erp-ajax-actions-btn {
    border: none;
    background: linear-gradient(135deg, #0b5ed7 0%, #06b6d4 100%);
    color: #ffffff;
    border-radius: 9px;
    padding: 8px 11px;
    font-size: 14px;
    font-weight: 700;
    height: 38px;
    white-space: nowrap;
}

.erp-ajax-grid-btn:hover,
.erp-ajax-grid-btn:focus,
.erp-ajax-actions-btn:hover,
.erp-ajax-actions-btn:focus {
    background: linear-gradient(135deg, #084bb0 0%, #0891b2 100%);
    color: #ffffff;
}

.erp-ajax-desktop-actions {
    display: flex;
    gap: 8px;
}

.erp-ajax-mobile-actions {
    display: none;
}

.erp-ajax-actions-dropdown .dropdown-menu {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    padding: 8px;
    min-width: 220px;
    z-index: 999999;
}

.erp-ajax-actions-dropdown .dropdown-menu > li > a {
    border-radius: 8px;
    padding: 9px 12px;
    font-weight: 600;
    color: #34495e;
}

.erp-ajax-custom-colvis-menu {
    background: #ffffff !important;
    border: 1px solid #dfe6e9 !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25) !important;
    padding: 8px !important;
    z-index: 99999999 !important;
    min-width: 240px !important;
}

.erp-ajax-custom-colvis-menu label {
    display: block;
    padding: 8px 12px;
    cursor: pointer;
    color: #34495e;
    border-radius: 6px;
    margin-bottom: 4px;
    font-weight: 600;
}

.erp-ajax-custom-colvis-menu label:hover {
    background: #f4f6f9;
}

.erp-ajax-custom-colvis-menu input {
    margin-right: 8px;
}

@media(max-width: 1199px) {
    .erp-ajax-grid-row {
        flex-wrap: wrap;
    }

    .erp-ajax-grid-right {
        justify-content: flex-start;
    }
}

@media(max-width: 767px) {
    .erp-ajax-grid-row,
    .erp-ajax-grid-actions,
    .erp-ajax-grid-right {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        gap: 8px;
    }

    .erp-ajax-grid-left {
        flex-wrap: wrap;
        width: 100%;
    }

    .erp-ajax-per-page {
        width: 90px;
    }

    .erp-ajax-date-wrapper,
    .erp-ajax-grid-search,
    .erp-ajax-grid-control {
        width: 100%;
        min-width: 100%;
    }

    .erp-ajax-desktop-actions {
        display: none !important;
    }

    .erp-ajax-mobile-actions {
        display: block !important;
        width: 100%;
    }

    .erp-ajax-actions-dropdown,
    .erp-ajax-actions-btn {
        width: 100%;
    }

    .erp-ajax-actions-btn {
        height: 42px;
        font-size: 15px;
    }

    .erp-ajax-actions-dropdown .dropdown-menu {
        width: 100%;
    }
}

.table-responsive {
    overflow-x: auto !important;
    overflow-y: visible !important;
}

.erp-hidden-dt-button,
.dt-buttons .erp-hidden-dt-button {
    display: none !important;
}
</style>

<div class="erp-ajax-grid-toolbar"
     data-table-id="{{ $table_id ?? 'erp_ajax_table' }}"
     data-default-per-page="{{ $default_per_page ?? 10 }}">

    <div class="erp-ajax-grid-row">

        <div class="erp-ajax-grid-left">
            <label class="erp-ajax-grid-label">Show</label>

            <select class="erp-ajax-grid-control erp-ajax-per-page">
                @foreach([10, 25, 50, 100, 200, 500] as $size)
                    <option value="{{ $size }}" {{ ($default_per_page ?? 10) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>

            <span class="erp-ajax-grid-label">records</span>

            <div class="input-group erp-ajax-date-wrapper">
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>

                <input type="text"
                       class="form-control erp-ajax-date-range"
                       readonly
                       placeholder="YYYY-MM-DD ~ YYYY-MM-DD">
            </div>
        </div>

        <div class="erp-ajax-grid-actions">

            <div class="erp-ajax-desktop-actions">
                <button type="button" class="erp-ajax-grid-btn erp-ajax-export-csv">
                    <i class="fa fa-file-text-o"></i> CSV
                </button>

                <button type="button" class="erp-ajax-grid-btn erp-ajax-export-excel">
                    <i class="fa fa-file-excel-o"></i> Excel
                </button>

                <button type="button" class="erp-ajax-grid-btn erp-ajax-column-visibility">
                    <i class="fa fa-columns"></i> Columns
                </button>

                <button type="button" class="erp-ajax-grid-btn erp-ajax-export-pdf">
                    <i class="fa fa-file-pdf-o"></i> PDF
                </button>

                <button type="button" class="erp-ajax-grid-btn erp-ajax-print">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>

            <div class="dropdown erp-ajax-actions-dropdown erp-ajax-mobile-actions">
                <button type="button"
                        class="erp-ajax-actions-btn dropdown-toggle"
                        data-toggle="dropdown">
                    <i class="fa fa-cogs"></i> Actions <span class="caret"></span>
                </button>

                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="#" class="erp-ajax-export-csv"><i class="fa fa-file-text-o"></i> Export to CSV</a></li>
                    <li><a href="#" class="erp-ajax-export-excel"><i class="fa fa-file-excel-o"></i> Export to Excel</a></li>
                    <li><a href="#" class="erp-ajax-column-visibility"><i class="fa fa-columns"></i> Column Visibility</a></li>
                    <li><a href="#" class="erp-ajax-export-pdf"><i class="fa fa-file-pdf-o"></i> Export to PDF</a></li>
                    <li><a href="#" class="erp-ajax-print"><i class="fa fa-print"></i> Print</a></li>
                </ul>
            </div>

        </div>

        <div class="erp-ajax-grid-right">
            <input type="text"
                   class="erp-ajax-grid-control erp-ajax-grid-search"
                   placeholder="Search records..."
                   autocomplete="off">
        </div>

    </div>
</div>

<script>
(function () {

    function initialiseErpAjaxToolbar(toolbar) {
        let tableId = toolbar.data('table-id');
        let table = $('#' + tableId);

        if (!table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#' + tableId)) {
            return false;
        }

        if (toolbar.data('erp-toolbar-ready') === 1) {
            return true;
        }

        toolbar.data('erp-toolbar-ready', 1);

        let dataTable = table.DataTable();
        let searchTimer = null;

        function clickNativeButton(buttonClass) {
            let wrapper = table.closest('.dataTables_wrapper');
            let button = wrapper.find(buttonClass).first();

            if (button.length) {
                button.trigger('click');
            }
        }

        toolbar.find('.erp-ajax-per-page').on('change', function () {
            dataTable.page.len($(this).val()).draw(false);
        });

toolbar.find('.erp-ajax-grid-search').on('keyup input', function () {

    dataTable.search($(this).val()).draw(false);

});

        if ($.fn.daterangepicker && typeof dateRangeSettings !== 'undefined') {

            toolbar.find('.erp-ajax-date-range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    toolbar.find('.erp-ajax-date-range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );

                    if (dataTable.ajax) {
                        dataTable.ajax.reload(null, false);
                    } else {
                        dataTable.draw(false);
                    }
                }
            );

            toolbar.find('.erp-ajax-date-range').on('apply.daterangepicker', function(ev, picker) {

                if (picker.chosenLabel === 'Custom Date Range') {
                    $('#target_custom_date_input').val($(this).attr('id'));
                    $('.custom_date_typing_modal').modal('show');
                    return;
                }

                if (dataTable.ajax) {
                    dataTable.ajax.reload(null, false);
                } else {
                    dataTable.draw(false);
                }
            });

            toolbar.find('.erp-ajax-date-range').on('cancel.daterangepicker', function() {
                $(this).val('');

                if (dataTable.ajax) {
                    dataTable.ajax.reload(null, false);
                } else {
                    dataTable.draw(false);
                }
            });

        } else if ($.fn.daterangepicker) {

            let erpToday = moment();

            toolbar.find('.erp-ajax-date-range').daterangepicker({
                startDate: erpToday,
                endDate: erpToday,
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' ~ ',
                    applyLabel: 'Apply',
                    cancelLabel: 'Clear',
                    customRangeLabel: 'Custom Date Range'
                }
            });
        }

        toolbar.find('.erp-ajax-export-csv').on('click', function (e) {
            e.preventDefault();
            clickNativeButton('.buttons-csv');
        });

        toolbar.find('.erp-ajax-export-excel').on('click', function (e) {
            e.preventDefault();
            clickNativeButton('.buttons-excel');
        });

        toolbar.find('.erp-ajax-export-pdf').on('click', function (e) {
            e.preventDefault();
            clickNativeButton('.buttons-pdf');
        });

        toolbar.find('.erp-ajax-print').on('click', function (e) {
            e.preventDefault();
            clickNativeButton('.buttons-print');
        });

toolbar.find('.erp-ajax-column-visibility').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            $('.erp-ajax-custom-colvis-menu').remove();

            let menu = $('<div class="erp-ajax-custom-colvis-menu"></div>');
            menu.append('<div style="font-weight:800;color:#0f4c81;padding:8px 12px;border-bottom:1px solid #edf2f7;margin-bottom:6px;"><i class="fa fa-columns"></i> Column Visibility</div>');

            dataTable.columns().every(function (index) {
                let column = this;
                let title = $(column.header()).text().replace(/\s+/g, ' ').trim();

                if (title === '') {
                    title = 'Column ' + (index + 1);
                }

                let checked = column.visible() ? 'checked' : '';

                menu.append(
                    '<label>' +
                        '<input type="checkbox" class="erp-ajax-colvis-toggle" data-table-id="' + tableId + '" data-column="' + index + '" ' + checked + '> ' +
                        $('<div>').text(title).html() +
                    '</label>'
                );
            });

            $('body').append(menu);

            let offset = $(this).offset();
            let left = offset.left;
            let top = offset.top + $(this).outerHeight() + 8;
            let menuWidth = menu.outerWidth();
            let windowWidth = $(window).width();

            if (left + menuWidth > windowWidth - 20) {
                left = Math.max(12, windowWidth - menuWidth - 20);
            }

            menu.css({
                position: 'absolute',
                top: top,
                left: left
            });
        });

return true;
    }

    function bootErpAjaxToolbars() {
        $('.erp-ajax-grid-toolbar').each(function () {
            let toolbar = $(this);
            let attempts = 0;

            let timer = setInterval(function () {
                attempts++;

                if (initialiseErpAjaxToolbar(toolbar) || attempts >= 40) {
                    clearInterval(timer);
                }
            }, 100);
        });
    }

    $(document).ready(function () {
        bootErpAjaxToolbars();
    });

    $(document).on('change', '.erp-ajax-colvis-toggle', function () {
        let tableId = $(this).data('table-id');
        let columnIndex = $(this).data('column');
        let table = $('#' + tableId);

        if (!table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#' + tableId)) {
            return;
        }

        table.DataTable().column(columnIndex).visible($(this).is(':checked'));
    });

    $(document).on('click', function (e) {
        if (
            !$(e.target).closest('.erp-ajax-column-visibility').length &&
            !$(e.target).closest('.erp-ajax-custom-colvis-menu').length &&
            !$(e.target).closest('.erp-ajax-actions-dropdown').length
        ) {
            $('.erp-ajax-custom-colvis-menu').remove();
        }
    });

})();
</script>