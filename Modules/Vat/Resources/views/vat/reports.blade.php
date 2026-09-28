@extends('layouts.app')

@section('title', __('vat::lang.vat_module'))

@section('content')
<!-- Main content -->
<section class="content">

    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs no-print">
                    <li class="active">
                        <a href="#tax_report" class="tax_report" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('report.tax_report')</strong>
                        </a>
                    </li>
                    
                   
                    
                </ul> 
                <div class="tab-content">
                    <div class="tab-pane active" id="tax_report">
                        @include('vat::vat.vat_reports')
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>
    
    <div class="hide">
        <div id="report_print_div"></div>
    </div>
    
    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div id="settlement_print" class="container"></div>
    
</section>
<!-- /.content -->

@endsection
@section('javascript')
@if(!empty(session('status')) && empty(session('status')['success']))
    <script>
        toastr.error('{{session('status')['msg']}}');
    </script>
@endif
<script>
    /*
     * IS2322 - VAT Report owns its own date picker, summary and DataTable.
     *
     * Do NOT load the global report.js here. That file also defines
     * updateTaxReport() for the separate Reports module and can bind this same
     * table/date control to a different endpoint. Keeping this page standalone
     * prevents a later script from replacing the VAT module's report logic.
     */
    (function($) {
        'use strict';

        var vatTaxReportTable = null;

        function vatReportFilters() {
            var picker = $('#tax_report_date_filter').data('daterangepicker');

            return {
                start_date: picker ? picker.startDate.format('YYYY-MM-DD') : '',
                end_date: picker ? picker.endDate.format('YYYY-MM-DD') : '',
                location_id: $('#tax_report_location_filter').val() || '',
                contact_id: $('#contact_id').val() || '',
                reference_type: $('#reference_type').val() || ''
            };
        }

        function refreshVatSummary() {
            var filters = vatReportFilters();
            var loader = '<i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i>';

            $('.input_tax, .output_tax, .expense_tax, .tax_diff').html(loader);

            $.ajax({
                method: 'GET',
                url: '/vat-module/reports-summary',
                dataType: 'json',
                global: false,
                cache: false,
                data: filters,
                success: function(data) {
                    $('.input_tax').html(data.input_tax || 0);
                    $('.output_tax').html(data.output_tax || 0);
                    $('.expense_tax').html(data.expense_tax || 0);
                    $('.tax_diff').html(__currency_trans_from_en(parseFloat(data.tax_diff || 0), true));
                    __currency_convert_recursively($('.input_tax, .output_tax, .expense_tax'));
                    __highlight(parseFloat(data.tax_diff || 0), $('.tax_diff'));
                },
                error: function() {
                    $('.input_tax, .output_tax, .expense_tax').html('0');
                    $('.tax_diff').html(__currency_trans_from_en(0, true));
                }
            });
        }

        function initVatReportTable() {
            var $table = $('#taxes_details_table');
            if (!$table.length) {
                return;
            }

            // A globally loaded script must never be allowed to retain ownership
            // of this table. Destroy any previous instance before binding the
            // VAT-module endpoint.
            if ($.fn.DataTable.isDataTable('#taxes_details_table')) {
                $table.DataTable().clear().destroy();
            }

            vatTaxReportTable = $table.DataTable({
                processing: true,
                serverSide: true,
                stateSave: false,
                autoWidth: false,
                order: [[0, 'desc']],
                ajax: {
                    url: '/vat-module/reports',
                    data: function(d) {
                        $.extend(d, vatReportFilters());
                    }
                },
                columns: [
                    { data: 'transaction_date', name: 'vat_report_rows.transaction_date' },
                    { data: 'type', name: 'vat_report_rows.type' },
                    { data: 'ref_no', name: 'vat_report_rows.invoice_no' },
                    { data: 'contact_name', name: 'vat_report_rows.contact_name' },
                    { data: 'final_total', name: 'vat_report_rows.final_total' },
                    { data: 'tax_amount', name: 'vat_report_rows.vat_report_tax_amount' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                drawCallback: function() {
                    $('#footer_total_amount').text(sum_table_col($table, 'final-total'));
                    $('#footer_vat_total').text(sum_table_col($table, 'tax-amount'));
                    __currency_convert_recursively($table);
                }
            });

            window.taxes_details_table = vatTaxReportTable;
        }

        window.updateTaxReport = function() {
            if (vatTaxReportTable && $.fn.DataTable.isDataTable('#taxes_details_table')) {
                vatTaxReportTable.ajax.reload(null, false);
            } else {
                initVatReportTable();
            }

            refreshVatSummary();
        };

        $(function() {
            var $dateFilter = $('#tax_report_date_filter');

            if ($dateFilter.length) {
                // Guard against duplicate date-picker initialization by any
                // application-wide script.
                var oldPicker = $dateFilter.data('daterangepicker');
                if (oldPicker && typeof oldPicker.remove === 'function') {
                    oldPicker.remove();
                }

                $dateFilter.daterangepicker(dateRangeSettings, function(start, end) {
                    $dateFilter.val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    window.updateTaxReport();
                });

                var picker = $dateFilter.data('daterangepicker');
                picker.setStartDate(moment().startOf('month'));
                picker.setEndDate(moment().endOf('month'));
                $dateFilter.val(
                    picker.startDate.format(moment_date_format) + ' ~ ' +
                    picker.endDate.format(moment_date_format)
                );
            }

            initVatReportTable();
            refreshVatSummary();
        });

        $(document).on('change', '#tax_report_location_filter, #contact_id, #reference_type', function() {
            window.updateTaxReport();
        });

        $(document).on('click', '.print-report', function(e) {
            e.preventDefault();

            var filters = vatReportFilters();
            $.ajax({
                method: 'GET',
                contentType: 'html',
                url: '/vat-module/print',
                data: filters,
                success: function(result) {
                    $('#report_print_div').empty().append(result);
                    $('#report_print_div').printThis();
                }
            });
        });

        $(document).on('click', '.print_settlement_button', function() {
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                data: {},
                success: function(result) {
                    $('#settlement_print').html(result);
                    var divToPrint = document.getElementById('settlement_print');
                    var newWin = window.open('', 'Print-Ledger');
                    newWin.document.open();
                    newWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</body></html>');
                    newWin.document.close();
                }
            });
        });

        $('#settlement_print').css('visibility', 'hidden');

        $(document).on('click', '#regenerate_vat', function(e) {
            e.preventDefault();
            $('#transaction_type').val('').trigger('change');
            $('#regenerate_vat_modal').modal('show');
        });

        $(document).on('submit', 'form#regenerate_vat_form', function(e) {
            e.preventDefault();
            var form = $(this);

            $.ajax({
                method: 'POST',
                url: form.attr('action'),
                dataType: 'json',
                data: form.serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('#regenerate_vat_modal').modal('hide');
                        toastr.success(result.msg);
                        window.updateTaxReport();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    })(jQuery);
</script>
@endsection
