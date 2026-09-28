{{--
    Customers Module local DataTable toolbar.
    Supports register/server-side grids and report/client-side grids.
--}}
@php
    $tableId = $table_id ?? 'customers_table';
    $requestedDefault = $default_per_page ?? 25;
    $defaultPerPage = (is_string($requestedDefault) && strtolower($requestedDefault) === 'all')
        ? -1
        : (int) $requestedDefault;
    $lengthOptions = $length_options ?? [10, 25, 50, 100, -1];
    $searchPlaceholder = $search_placeholder ?? 'Search customers...';
@endphp

<style>
    [data-customers-grid-toolbar="{{ $tableId }}"] {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin: 0 0 14px !important;
        width: 100% !important;
        padding: 14px 16px !important;
        background: #fff !important;
        border: 1px solid #e7edf5 !important;
        border-radius: 18px !important;
        box-shadow: 0 10px 28px rgba(15, 76, 129, .08) !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-left,
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-right {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 10px !important;
        align-items: center !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-left {
        justify-content: flex-start !important;
        flex: 1 1 390px !important;
        min-width: 280px !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-right {
        justify-content: flex-end !important;
        margin-left: auto !important;
        flex: 1 1 560px !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-length-wrap {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        color: #475569 !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-search {
        min-width: 300px !important;
        max-width: 480px !important;
        height: 42px !important;
        border-radius: 14px !important;
        border: 1px solid #d9e3ef !important;
        padding: 8px 14px !important;
        box-shadow: 0 6px 18px rgba(15, 76, 129, .06) !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-length {
        width: 88px !important;
        height: 42px !important;
        border-radius: 14px !important;
        border: 1px solid #d9e3ef !important;
        box-shadow: 0 6px 18px rgba(15, 76, 129, .06) !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] .btn {
        border: 0 !important;
        border-radius: 12px !important;
        color: #fff !important;
        padding: 10px 15px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        white-space: nowrap !important;
        box-shadow: 0 8px 18px rgba(11, 94, 215, .18) !important;
    }
    [data-customers-grid-toolbar="{{ $tableId }}"] [data-customers-grid-export="copy"],
    [data-customers-grid-toolbar="{{ $tableId }}"] [data-customers-grid-export="csv"] { background: linear-gradient(135deg,#0f766e,#06b6d4) !important; }
    [data-customers-grid-toolbar="{{ $tableId }}"] [data-customers-grid-export="excel"] { background: linear-gradient(135deg,#15803d,#22c55e) !important; }
    [data-customers-grid-toolbar="{{ $tableId }}"] [data-customers-grid-export="pdf"] { background: linear-gradient(135deg,#dc2626,#f97316) !important; }
    [data-customers-grid-toolbar="{{ $tableId }}"] [data-customers-grid-export="print"] { background: linear-gradient(135deg,#334155,#0f172a) !important; }
    [data-customers-grid-toolbar="{{ $tableId }}"] [data-customers-grid-colvis] { background: linear-gradient(135deg,#7c3aed,#2563eb) !important; }

    #{{ $tableId }}_wrapper > .dt-buttons,
    #{{ $tableId }}_wrapper .dt-buttons:not(.customers-grid-right),
    #{{ $tableId }}_wrapper .dt-button,
    #{{ $tableId }}_wrapper button.dt-button,
    #{{ $tableId }}_wrapper a.dt-button {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    @media (max-width: 991px) {
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-left,
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-right {
            flex: 1 1 100% !important;
            justify-content: flex-start !important;
            margin-left: 0 !important;
        }
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-search {
            min-width: 220px !important;
            max-width: 100% !important;
            flex: 1 1 220px !important;
        }
    }
    @media (max-width: 767px) {
        [data-customers-grid-toolbar="{{ $tableId }}"],
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-left,
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-right {
            display: block !important;
        }
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-length-wrap,
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-search,
        [data-customers-grid-toolbar="{{ $tableId }}"] .btn {
            width: 100% !important;
            margin-bottom: 8px !important;
        }
        [data-customers-grid-toolbar="{{ $tableId }}"] .customers-grid-length {
            flex: 1 1 auto !important;
            width: auto !important;
        }
    }
</style>

<div class="customers-grid-toolbar" data-customers-grid-toolbar="{{ $tableId }}">
    <div class="customers-grid-left">
        <label class="customers-grid-length-wrap">
            <span>Show</span>
            <select class="form-control input-sm customers-grid-length" data-customers-grid-length="{{ $tableId }}">
                @foreach($lengthOptions as $length)
                    @php $numericLength = (int) $length; @endphp
                    <option value="{{ $numericLength }}" {{ $numericLength === $defaultPerPage ? 'selected' : '' }}>
                        {{ $numericLength === -1 ? 'All' : $numericLength }}
                    </option>
                @endforeach
            </select>
            <span>entries</span>
        </label>

        <input type="text"
               class="form-control input-sm customers-grid-search"
               placeholder="{{ $searchPlaceholder }}"
               aria-label="{{ $searchPlaceholder }}"
               autocomplete="off"
               value=""
               data-customers-grid-search="{{ $tableId }}">
    </div>

    <div class="customers-grid-right">
        <button type="button" class="btn btn-default btn-sm" data-customers-grid-export="copy" data-table="{{ $tableId }}"><i class="fa fa-copy"></i> Copy</button>
        <button type="button" class="btn btn-default btn-sm" data-customers-grid-export="csv" data-table="{{ $tableId }}"><i class="fa fa-file-text-o"></i> CSV</button>
        <button type="button" class="btn btn-default btn-sm" data-customers-grid-export="excel" data-table="{{ $tableId }}"><i class="fa fa-file-excel-o"></i> Excel</button>
        <button type="button" class="btn btn-default btn-sm" data-customers-grid-export="pdf" data-table="{{ $tableId }}"><i class="fa fa-file-pdf-o"></i> PDF</button>
        <button type="button" class="btn btn-default btn-sm" data-customers-grid-export="print" data-table="{{ $tableId }}"><i class="fa fa-print"></i> Print</button>
        <button type="button" class="btn btn-default btn-sm" data-customers-grid-colvis="{{ $tableId }}"><i class="fa fa-columns"></i> Column Visibility</button>
    </div>
</div>

@push('javascript')
<script>
(function($) {
    'use strict';

    function tableApi(tableId) {
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#' + tableId)) { return null; }
        return $('#' + tableId).DataTable();
    }

    function visibleColumnIndexes(table) {
        var indexes = [];
        table.columns().every(function(index) {
            if (this.visible()) { indexes.push(index); }
        });
        return indexes;
    }

    function plainText(value) {
        return $('<div>').html(value == null ? '' : String(value)).text().replace(/\s+/g, ' ').trim();
    }

    function exportMatrix(table) {
        var indexes = visibleColumnIndexes(table);
        var matrix = [];
        matrix.push(indexes.map(function(index) {
            return plainText($(table.column(index).header()).text());
        }));
        table.rows({search: 'applied'}).every(function() {
            var row = this.data();
            matrix.push(indexes.map(function(index) {
                var value = Array.isArray(row) ? row[index] : row[table.column(index).dataSrc()];
                return plainText(value);
            }));
        });
        return matrix;
    }

    function downloadBlob(content, mime, filename) {
        var blob = new Blob([content], {type: mime});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function(){ URL.revokeObjectURL(url); }, 1000);
    }

    function csvCell(value) {
        return '"' + String(value == null ? '' : value).replace(/"/g, '""') + '"';
    }

    function fallbackExport(table, action, tableId) {
        var matrix = exportMatrix(table);
        var baseName = tableId.replace(/_/g, '-') + '-' + new Date().toISOString().slice(0, 10);

        if (action === 'copy') {
            var text = matrix.map(function(row){ return row.join('\t'); }).join('\n');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            } else {
                var area = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                area.remove();
            }
            if (typeof toastr !== 'undefined') { toastr.success('Copied to clipboard.'); }
            return;
        }

        if (action === 'csv') {
            downloadBlob('\ufeff' + matrix.map(function(row){ return row.map(csvCell).join(','); }).join('\r\n'), 'text/csv;charset=utf-8', baseName + '.csv');
            return;
        }

        if (action === 'excel') {
            var html = '<html><head><meta charset="utf-8"></head><body><table border="1">' +
                matrix.map(function(row, rowIndex){
                    var tag = rowIndex === 0 ? 'th' : 'td';
                    return '<tr>' + row.map(function(cell){ return '<' + tag + '>' + $('<div>').text(cell).html() + '</' + tag + '>'; }).join('') + '</tr>';
                }).join('') + '</table></body></html>';
            downloadBlob('\ufeff' + html, 'application/vnd.ms-excel;charset=utf-8', baseName + '.xls');
            return;
        }

        // PDF falls back to the same clean printable report when pdfmake is not loaded.
        window.print();
    }

    $(document).on('input change', '[data-customers-grid-search]', function() {
        var table = tableApi($(this).data('customers-grid-search'));
        if (table) { table.search(this.value).draw(); }
    });

    $(document).on('change', '[data-customers-grid-length]', function() {
        var table = tableApi($(this).data('customers-grid-length'));
        if (table) { table.page.len(parseInt(this.value, 10)).draw(); }
    });

    $(document).on('click', '[data-customers-grid-export]', function() {
        var tableId = $(this).data('table');
        var action = $(this).data('customers-grid-export');
        var table = tableApi(tableId);
        if (!table) { return; }

        var selectorMap = {
            copy: '.buttons-copy, .buttons-copyHtml5',
            csv: '.buttons-csv, .buttons-csvHtml5',
            excel: '.buttons-excel, .buttons-excelHtml5',
            pdf: '.buttons-pdf, .buttons-pdfHtml5',
            print: '.buttons-print'
        };

        try {
            if (table.buttons && table.buttons(selectorMap[action]).count() > 0) {
                table.button(selectorMap[action]).trigger();
                return;
            }
        } catch (ignore) {}

        fallbackExport(table, action, tableId);
    });

    $(document).on('click', '[data-customers-grid-colvis]', function(event) {
        event.stopPropagation();
        var tableId = $(this).data('customers-grid-colvis');
        var table = tableApi(tableId);
        if (!table) { return; }

        try {
            if (table.buttons && table.buttons('.buttons-colvis').count() > 0) {
                table.button('.buttons-colvis').trigger();
                return;
            }
        } catch (ignore) {}

        var menuId = tableId + '_manual_colvis_menu';
        $('#' + menuId).remove();
        var $menu = $('<div/>', {id: menuId, class: 'customers-manual-colvis-menu'}).css({
            position:'absolute', zIndex:999999, background:'#fff', border:'1px solid #d7e2ef',
            borderRadius:'10px', padding:'10px', boxShadow:'0 12px 30px rgba(0,0,0,.18)'
        });
        table.columns().every(function(index) {
            var title = plainText($(this.header()).text()) || ('Column ' + (index + 1));
            $menu.append('<label style="display:block;font-weight:600;margin:5px 0;"><input type="checkbox" data-column="' + index + '" ' + (this.visible() ? 'checked' : '') + '> ' + $('<div>').text(title).html() + '</label>');
        });
        $('body').append($menu);
        var offset = $(this).offset();
        $menu.css({top: offset.top + $(this).outerHeight() + 5, left: Math.max(10, offset.left - 100)});
        $menu.on('change', 'input[type=checkbox]', function(){ table.column($(this).data('column')).visible(this.checked); });
        $(document).one('click.customersManualColvis', function(e){
            if (!$(e.target).closest('#' + menuId + ', [data-customers-grid-colvis]').length) { $('#' + menuId).remove(); }
        });
    });
})(jQuery);
</script>
@endpush
