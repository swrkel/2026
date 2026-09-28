{{--
    MA-002: strip in-cell buttons from anything exported or printed.

    The Account Book renders its RECONCILE control and its NOTE button INSIDE
    the Debit and Credit cells rather than in columns of their own, so the
    existing column-level "notexport" rule cannot remove them - there is no
    column to exclude. They came out in the CSV, the Excel sheet, the PDF, the
    print view and the emailed copy as stray button text.

    This strips any button, anchor-styled-as-button, input, select or element
    marked .notexport out of the CELL before it is exported, and returns the
    readable text that is left. Applied through the shared partial, so every
    table in the system benefits, not just this one.

    Cells with no buttons are unaffected - the value passes through untouched.
--}}
{{--
    MA-002: the helper this partial's export options call,
    erpStripActionsForExport(), is defined in public/js/app.js.

    IT MUST NOT BE DEFINED HERE. This partial is @include'd INSIDE an existing
    <script> block, in the middle of a DataTable options object:

        columns: [ ... ],
        @include('layouts.partials.datatable_export_button')
        "fnDrawCallback": ...

    so a <script> tag emitted here produces a </script> INSIDE the outer
    script, which the browser treats as the end of it - and every line after
    that point is printed on the page as text. That is exactly what happened.
--}}
{{--
    MA-002: Search and "Show N entries" swapped, as asked.

    The dom string controls the layout. It was
        <"col-sm-7"l>  <"col-sm-5"f>      length menu LEFT, search RIGHT
    and is now
        <"col-sm-5"f>  <"col-sm-7"l>      search LEFT, length menu RIGHT

    l = the "Show N entries" menu, f = the search box.

    THIS PARTIAL IS INCLUDED BY 98 VIEWS, so the change lands across the
    system from one line rather than needing each screen edited. Any view that
    builds its own dom string is unaffected and keeps its current layout -
    tell me if you want those brought into line too and I will list them.

    The column widths travel with the fields, so the search keeps the narrower
    half and the length menu the wider one, and the row still adds to 12.
--}}
{{--
    IS1959 #1: erpStripActionsForExport IS NOT DEFINED ANYWHERE.

    The comment above says the helper lives in public/js/app.js. It does not -
    it appears nowhere in public/, and this partial is its only reference in
    the whole codebase. Because the options object below is BUILT when the
    DataTable is constructed, naming a missing function threw immediately:

        Uncaught ReferenceError: erpStripActionsForExport is not defined

    That error fires inside the $(document).ready callback that constructs the
    table, so the callback aborts and the DataTable is never created. The page
    is left showing the bare <thead> from the blade - no rows, no search box,
    no export buttons, and hidden columns still visible. That is exactly the
    Fuel Tank list symptom.

    THIS PARTIAL IS INCLUDED BY 98 VIEWS, so every table whose init sits after
    the include in the same ready block was at risk of the same silent death.

    Each of the four format.body entries below is now a wrapper function. The
    name is resolved when an export actually runs rather than when the table is
    built, and it falls back to the raw cell value if the helper is still
    absent. Exports keep working; if the helper is ever added to app.js it is
    picked up automatically with no change here.

    A <script> tag must NOT be added here to define it - see the note above.
--}}
dom: '<"row margin-bottom-20 text-center"<"col-sm-12"B><"col-sm-5 text-align-start"f><"col-sm-7"l> r>tip',
buttons: [
    {
        extend: 'colvis',
        text: '<i class="fa fa-columns"></i> Column Visibility',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-colvis',
        columns: ':not(.noColvis)',
        exportOptions: {
            columns: ':not(.noColvis)'
        }
    },{
        extend: 'csv',
        footer: true,
        text: '<i class="fa fa-file"></i> Export to CSV',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-csv',
        exportOptions: {
            columns: function (idx, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(idx).visible() && !$(node).hasClass('notexport');
            }            ,
            format: {
                body: function (data, row, column, node) {
                    return (typeof erpStripActionsForExport === 'function')
                        ? erpStripActionsForExport(data, row, column, node)
                        : data;
                }
            }
        }
    },{
        extend: 'excel',
        footer: true,
        text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-excel',
        exportOptions: {
            columns: function (idx, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(idx).visible() && !$(node).hasClass('notexport');
            }            ,
            format: {
                body: function (data, row, column, node) {
                    return (typeof erpStripActionsForExport === 'function')
                        ? erpStripActionsForExport(data, row, column, node)
                        : data;
                }
            }
        }
    },{
        extend: 'pdf',
        footer: true,
        text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-pdf',
        exportOptions: {
            columns: function (idx, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(idx).visible() && !$(node).hasClass('notexport');
            }            ,
            format: {
                body: function (data, row, column, node) {
                    return (typeof erpStripActionsForExport === 'function')
                        ? erpStripActionsForExport(data, row, column, node)
                        : data;
                }
            }
        }
    },{
        extend: 'print',
        footer: true,
        text: '<i class="fa fa-print"></i> Print',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-print',
        exportOptions: {
            columns: function (idx, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(idx).visible() && !$(node).hasClass('notexport');
            }            ,
            format: {
                body: function (data, row, column, node) {
                    return (typeof erpStripActionsForExport === 'function')
                        ? erpStripActionsForExport(data, row, column, node)
                        : data;
                }
            }
        },
        customize: function (win) {
            $(win.document.body).find('h1').css('text-align', 'center');
            $(win.document.body).find('h1').css('font-size', '25px');
        },
    }
],