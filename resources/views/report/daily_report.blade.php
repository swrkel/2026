@extends('layouts.app')

@section('title', __('lang_v1.daily_report'))

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @include('report.partials.daily_report_header')
            </div>
        </div>
    </section>

    <div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" tabindex="-1" id="email_report_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open(['url' => '#', 'method' => 'post', 'id' => 'email_report_form']) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Email report</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" val="" id="file_url">
                        <div class="form-group col-sm-12">
                            {!! Form::label('recipient', 'Recipient:*') !!}
                            {!! Form::text('recipient', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => 'Recipient Email',
                                'id' => 'recipient',
                            ]) !!}
                        </div>
                        <div class="form-group col-sm-12">
                            {!! Form::label('subject', 'Email Subject:*') !!}
                            {!! Form::text('subject', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => 'Subject',
                                'id' => 'subject',
                            ]) !!}
                        </div>
                        <div class="form-group col-sm-12">
                            {!! Form::label('message', 'Email Body:*') !!}
                            {!! Form::textarea('message', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => 'Email Body',
                                'id' => 'message',
                                'rows' => 6,
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary send_report_email">
                        <i class="fa fa-envelope"></i>
                        @lang('messages.send')
                    </button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">
        let selected_report_cases = [];

        function whatsappPdf(file) {
            const whatsappUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(file);
            window.open(whatsappUrl, '_blank');
        }

        function emailPdf(file, startDate) {
            const parsedDate = new Date(startDate);
            const localized = $.datepicker.formatDate('dd/mm/yy', parsedDate);
            $('#file_url').val(file);
            $('#subject').val('Daily report of ' + localized);
            const msg = [
                'Dear Sir / Madam,',
                '',
                'Please find the Daily Report of ' + localized + ' date for your kind attention.',
                '',
                'This is a PDF report which could be opened online.',
                '',
                'Thank You'
            ].join('\n');
            $('#message').val(msg);
            $('#email_report_modal').modal('show');
        }

        function generatePdf(html, action, startDate) {
            $.ajax({
                method: 'post',
                url: '/download-pdf',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    html
                },
                success: function(response) {
                    if (action === 'email') {
                        emailPdf(response.path, startDate);
                    } else if (action === 'whatsapp') {
                        whatsappPdf(response.path);
                    } else if (action === 'download') {
                        window.open(response.path, '_blank');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while preparing the PDF.');
                }
            });
        }

        function downloadTextFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }

        function exportDailyReportHtml(resultHtml, action, startDate, endDate) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = resultHtml;
            const safeStart = (startDate || '').replace(/-/g, '');
            const safeEnd = (endDate || '').replace(/-/g, '');
            if (action === 'export_excel') {
                const htmlDoc = '<html><head><meta charset="utf-8"></head><body>' +
                    wrapper.innerHTML + '</body></html>';
                downloadTextFile(htmlDoc, 'daily_report_' + safeStart + '_' + safeEnd + '.xls',
                    'application/vnd.ms-excel;charset=utf-8');
                return;
            }
            const lines = [];
            wrapper.querySelectorAll('table').forEach(function(table, tIndex) {
                if (tIndex > 0) {
                    lines.push('');
                }
                table.querySelectorAll('tr').forEach(function(tr) {
                    const row = [];
                    tr.querySelectorAll('th,td').forEach(function(cell) {
                        const text = (cell.textContent || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                        row.push('"' + text + '"');
                    });
                    if (row.length) {
                        lines.push(row.join(','));
                    }
                });
            });
            downloadTextFile(lines.join('\n'), 'daily_report_' + safeStart + '_' + safeEnd + '.csv', 'text/csv;charset=utf-8');
        }

        function prepareToPrint() {
            getDailyReport(true, 'print');
        }

        function getDailyReport(print = false, action = 'view') {
            const locationId = $('#daily_report_location_id').val();
            const workShift = $('#daily_report_work_shift').val();
            const rangePicker = $('input#daily_report_date_range').data('daterangepicker');

            let startDate = moment().startOf('day').format('YYYY-MM-DD');
            let endDate = moment().endOf('day').format('YYYY-MM-DD');

            if (rangePicker) {
                startDate = rangePicker.startDate.format('YYYY-MM-DD');
                endDate = rangePicker.endDate.format('YYYY-MM-DD');
            }

            const formattedStart = moment(startDate).format('DD/MM/YYYY');
            const formattedEnd = moment(endDate).format('DD/MM/YYYY');
            $('#selected_range').html('Date Range: From ' + formattedStart + ' to ' + formattedEnd);

            if (!print) {
                const loader =
                    '<div class="row text-center"><i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i></div>';
                $('.daily_report_content').html(loader);
            }

            $.ajax({
                method: 'get',
                url: '/reports/daily-report',
                data: {
                    location_id: locationId,
                    work_shift: workShift,
                    start_date: startDate,
                    end_date: endDate,
                    print_only: print ? 'true' : 'false',
                    action_r: action,
                    report_cases: selected_report_cases,
                    activate_sales_section: $('#activate_sales_section').length ? ($('#activate_sales_section').is(':checked') ? 1 : 0) : 1,
                    activate_sales_by_cashier: $('#activate_sales_by_cashier').length ? ($('#activate_sales_by_cashier').is(':checked') ? 1 : 0) : 1,
                    activate_add: $('#activate_add').length ? ($('#activate_add').is(':checked') ? 1 : 0) : 1,
                    activate_less: $('#activate_less').length ? ($('#activate_less').is(':checked') ? 1 : 0) : 1,
                    activate_sales_return: $('#activate_sales_return').length ? ($('#activate_sales_return').is(':checked') ? 1 : 0) : 1,
                    activate_purchase_return: $('#activate_purchase_return').length ? ($('#activate_purchase_return').is(':checked') ? 1 : 0) : 1,
                    activate_financial_status: $('#activate_financial_status').length ? ($('#activate_financial_status').is(':checked') ? 1 : 0) : 1,
                    activate_financial_status_2: $('#activate_financial_status_2').length ? ($('#activate_financial_status_2').is(':checked') ? 1 : 0) : 1,
                    activate_financial_status_breakups: $('#activate_financial_status_breakups').length ? ($('#activate_financial_status_breakups').is(':checked') ? 1 : 0) : 1,
                    activate_outstanding_details: $('#activate_outstanding_details').length ? ($('#activate_outstanding_details').is(':checked') ? 1 : 0) : 1,
                    activate_stock_value_status: $('#activate_stock_value_status').length ? ($('#activate_stock_value_status').is(':checked') ? 1 : 0) : 1,
                    activate_pump_operators_shortage: $('#activate_pump_operators_shortage').length ? ($('#activate_pump_operators_shortage').is(':checked') ? 1 : 0) : 1,
                    activate_pump_operators_excess: $('#activate_pump_operators_excess').length ? ($('#activate_pump_operators_excess').is(':checked') ? 1 : 0) : 1,
                    activate_dip_details: $('#activate_dip_details').length ? ($('#activate_dip_details').is(':checked') ? 1 : 0) : 1,
                    activate_review_section: $('#activate_review_section').length ? ($('#activate_review_section').is(':checked') ? 1 : 0) : 1,
                },
                dataType: 'html',
                success: function(result) {
                    if (print) {
                        if (action === 'print') {
                            const w = window.open('', '_self');
                            $(w.document.body).html(result);
                            w.print();
                            w.close();
                            location.reload();
                        } else if (action === 'export_csv' || action === 'export_excel') {
                            exportDailyReportHtml(result, action, startDate, endDate);
                        } else if (action === 'export_pdf') {
                            generatePdf(result, 'download', startDate);
                        } else {
                            generatePdf(result, action, startDate);
                        }
                        return;
                    }

                    $('.daily_report_content').empty().append(result);

                    $('body').off('change.reportCases').on('change.reportCases', '.report-cases', function() {
                        const value = $(this).val();
                        if ($(this).prop('checked')) {
                            if (!selected_report_cases.includes(value)) {
                                selected_report_cases.push(value);
                            }
                            $('#step-' + value).show();
                        } else {
                            selected_report_cases = selected_report_cases.filter(function(item) {
                                return item !== value;
                            });
                            $('#step-' + value).hide();
                        }
                    });

                    if ($.fn.DataTable && $('#table-step-1').length) {
                        $('#table-step-1').DataTable({
                            destroy: true,
                            processing: true,
                            serverSide: true,
                            ajax: '/reports/daily-report/getOutStandingReceived?start_date=' +
                                startDate + '&end_date=' + endDate,
                            columns: [{
                                    data: 'operation_date'
                                },
                                {
                                    data: 'amount',
                                    className: 'text-right',
                                    render: function(data) {
                                        return '<span class="od_amount" data-orig-value="' +
                                            data.replace(/,/g, '') + '">' + data + '</span>';
                                    }
                                },
                                {
                                    data: 'customer_name'
                                },
                                {
                                    data: 'payment_method'
                                },
                                {
                                    data: 'bank_account_number'
                                },
                                {
                                    data: 'cheque_numbers'
                                },
                                {
                                    data: 'cheque_date'
                                }
                            ],
                            footerCallback: function(row, data) {
                                const api = this.api();
                                const total = api.column(1).data().reduce(function(acc, value) {
                                    const numeric = parseFloat(String(value).replace(/,/g,
                                        ''));
                                    return acc + (isNaN(numeric) ? 0 : numeric);
                                }, 0);
                                $(api.column(1).footer()).find('span').html(
                                    __currency_trans_from_en(total, true));
                            }
                        });
                    }
                },
                error: function(xhr) {
                    let message = 'Failed to load the daily report.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    $('.daily_report_content').html('<div class="alert alert-danger">' + message + '</div>');
                }
            });
        }

        $(document).ready(function() {
            // Ensure moment_date_format exists, create fallback if not
            if (typeof moment_date_format === 'undefined') {
                var moment_date_format = 'DD/MM/YYYY';
            }
            
            $('#email_report_form').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    method: 'post',
                    url: '/reports/email_report',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        recipient: $('#recipient').val(),
                        subject: $('#subject').val(),
                        email: $('#message').val(),
                        file: $('#file_url').val(),
                    },
                    success: function(data) {
                        if (data && typeof data.status !== 'undefined' && data.status === 0) {
                            toastr.error(data.msg);
                        } else {
                            toastr.success(data.msg || 'Email queued successfully.');
                        }
                        $('#email_report_modal').modal('hide');
                    },
                    error: function() {
                        toastr.error('Failed to send the report email.');
                    }
                });
            });

            if ($('#daily_report_date_range').length) {
                // Ensure dateRangeSettings exists, create fallback if not
                if (typeof dateRangeSettings === 'undefined') {
                    var dateRangeSettings = {
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        },
                        locale: {
                            format: 'DD/MM/YYYY'
                        }
                    };
                }
                
                const todayStart = moment().startOf('day');
                const todayEnd = moment().endOf('day');
                const todayRangeSettings = $.extend(true, {}, dateRangeSettings, {
                    startDate: todayStart,
                    endDate: todayEnd
                });

                $('#daily_report_date_range').daterangepicker(todayRangeSettings, function(start, end) {
                    $('#daily_report_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    getDailyReport(false, 'view');
                });

                $('#daily_report_date_range').val(
                    todayStart.format(moment_date_format) + ' ~ ' + todayEnd.format(moment_date_format)
                );

                // Handler for Custom Date Range modal Apply button
                $('#custom_date_apply_button').on('click', function() {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() +
                        $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + '-' +
                        $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + '-' +
                        $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() +
                        $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + '-' +
                        $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + '-' +
                        $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);

                        $('#daily_report_date_range').val(formattedStartDate + ' ~ ' + formattedEndDate);
                        $('#daily_report_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#daily_report_date_range').data('daterangepicker').setEndDate(moment(endDate));

                        $('.custom_date_typing_modal').modal('hide');
                        getDailyReport(false, 'view');
                    } else {
                        alert('Please select both start and end dates.');
                    }
                });

                // Show custom date modal when "Custom Date Range" is selected
                $('#daily_report_date_range').on('apply.daterangepicker', function(ev, picker) {
                    if (picker.chosenLabel === 'Custom Date Range') {
                        $('.custom_date_typing_modal').modal('show');
                    }
                });

                $('#daily_report_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#daily_report_date_range').val('');
                });
            }

            $(document).on('change', '.daily_report_change', function() {
                getDailyReport(false, 'view');
            });

            getDailyReport(false, 'view');
        });
    </script>
@endsection
<style>
