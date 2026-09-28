dom: '<"row margin-bottom-20 text-center"<"col-sm-12"B><"col-sm-5 text-align-start"f><"col-sm-7"l> r>tip',
buttons: [
    {
        text: '<i class="fa fa-columns"></i> Column Visibility',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-colvis',
        action: function (event, datatable, node) {
            event.preventDefault();
            event.stopPropagation();

            if (window.VatStatementColumnVisibility) {
                window.VatStatementColumnVisibility.toggle(datatable, node);
            }
        }
    },
    {
        extend: 'csv',
        footer: true,
        text: '<i class="fa fa-file"></i> Export to CSV',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-csv',
        exportOptions: {
            columns: function (index, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(index).visible() && !$(node).hasClass('notexport');
            }
        }
    },
    {
        extend: 'excel',
        footer: true,
        text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-excel',
        exportOptions: {
            columns: function (index, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(index).visible() && !$(node).hasClass('notexport');
            }
        }
    },
    {
        extend: 'pdf',
        footer: true,
        text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-pdf',
        exportOptions: {
            columns: function (index, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(index).visible() && !$(node).hasClass('notexport');
            }
        }
    },
    {
        extend: 'print',
        footer: true,
        text: '<i class="fa fa-print"></i> Print',
        className: 'btn btn-sm erp-dt-btn erp-dt-btn-print',
        exportOptions: {
            columns: function (index, data, node) {
                var table = $(node).closest('table').DataTable();
                return table.column(index).visible() && !$(node).hasClass('notexport');
            }
        },
        customize: function (win) {
            $(win.document.body).find('h1').css({
                'text-align': 'center',
                'font-size': '25px'
            });
        }
    }
],
