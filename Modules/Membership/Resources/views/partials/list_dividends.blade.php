@extends('layouts.app')
@section('title', __('membership::lang.list_dividends'))

@section('content')
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('membership::lang.list_dividends')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('membership::lang.dividends')</a></li>
                        <li><span>@lang('membership::lang.list_dividends')</span></li>
                    </ul>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="pull-right">
                    <a href="{{ action('\Modules\Membership\Http\Controllers\DividendController@addDividends') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> @lang('membership::lang.add_dividends')
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <!-- Tabs -->
        <div class="box box-solid">
            <div class="box-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#last_dividends_tab" aria-controls="last_dividends_tab" role="tab" data-toggle="tab">
                            @lang('membership::lang.last_dividends')
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#issued_dividends_tab" aria-controls="issued_dividends_tab" role="tab" data-toggle="tab">
                            @lang('membership::lang.list_of_issued_dividends')
                        </a>
                    </li>
                </ul>

                <div class="tab-content" style="padding-top: 20px;">
                    <!-- Last Dividends Tab -->
                    <div role="tabpanel" class="tab-pane active" id="last_dividends_tab">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="last_dividends_content">
                                    <div class="text-center">
                                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                                        <p>{{ __('messages.loading') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List of Issued Dividends Tab -->
                    <div role="tabpanel" class="tab-pane" id="issued_dividends_tab">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="issued_dividends_table" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>@lang('membership::lang.dividend_date')</th>
                                            <th>@lang('membership::lang.member_number')</th>
                                            <th>@lang('membership::lang.member_name')</th>
                                            <th>@lang('membership::lang.region')</th>
                                            <th>@lang('membership::lang.dividend_amount')</th>
                                            <th>@lang('membership::lang.reference_number')</th>
                                            <th>@lang('membership::lang.added_by')</th>
                                            <th>@lang('membership::lang.date_time')</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Report Footer -->
        @php
            $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
        @endphp
        @if (!empty($reports_footer))
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="margin-top: 20px; padding: 10px; border-top: 1px solid #ddd;">
                        {{ $reports_footer->value }}
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Load Last Dividends
            function loadLastDividends() {
                $.ajax({
                    url: '{{ action("\Modules\Membership\Http\Controllers\DividendController@getLastDividends") }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            var html = '';
                            if (response.dividends.length === 0) {
                                html = '<div class="alert alert-info">@lang("membership::lang.no_dividends_found")</div>';
                            } else {
                                html = '<div class="box box-solid">';
                                html += '<div class="box-header with-border">';
                                html += '<h3 class="box-title">@lang("membership::lang.last_dividends") - ' + response.dividend_date + '</h3>';
                                html += '<div class="box-tools pull-right">';
                                html += '<button type="button" class="btn btn-default btn-sm" onclick="printLastDividends()">';
                                html += '<i class="fa fa-print"></i> @lang("messages.print")';
                                html += '</button>';
                                html += '</div>';
                                html += '</div>';
                                html += '<div class="box-body">';
                                html += '<div class="table-responsive">';
                                html += '<table class="table table-bordered table-striped">';
                                html += '<thead>';
                                html += '<tr>';
                                html += '<th>@lang("membership::lang.member_number")</th>';
                                html += '<th>@lang("membership::lang.member_name")</th>';
                                html += '<th>@lang("membership::lang.region")</th>';
                                html += '<th>@lang("membership::lang.dividend_amount")</th>';
                                html += '<th>@lang("membership::lang.reference_number")</th>';
                                html += '<th>@lang("membership::lang.added_by")</th>';
                                html += '<th>@lang("membership::lang.date_time")</th>';
                                html += '</tr>';
                                html += '</thead>';
                                html += '<tbody>';
                                
                                response.dividends.forEach(function(dividend) {
                                    html += '<tr>';
                                    html += '<td>' + dividend.member_number + '</td>';
                                    html += '<td>' + dividend.member_name + '</td>';
                                    html += '<td>' + dividend.region + '</td>';
                                    html += '<td>' + dividend.dividend_amount + '</td>';
                                    html += '<td>' + dividend.reference_number + '</td>';
                                    html += '<td>' + dividend.created_by + '</td>';
                                    html += '<td>' + dividend.created_at + '</td>';
                                    html += '</tr>';
                                });
                                
                                html += '</tbody>';
                                html += '<tfoot>';
                                html += '<tr>';
                                html += '<th colspan="3" class="text-right">@lang("report.total"):</th>';
                                html += '<th>' + response.total_amount + '</th>';
                                html += '<th colspan="3"></th>';
                                html += '</tr>';
                                html += '</tfoot>';
                                html += '</table>';
                                html += '</div>';
                                html += '</div>';
                                html += '</div>';
                            }
                            $('#last_dividends_content').html(html);
                        }
                    },
                    error: function() {
                        $('#last_dividends_content').html('<div class="alert alert-danger">@lang("messages.something_went_wrong")</div>');
                    }
                });
            }

            // Initialize Last Dividends tab
            loadLastDividends();

            // Initialize Issued Dividends DataTable only when tab is shown (fixes hidden tab column width issue)
            var issued_dividends_table = null;
            $('a[href="#issued_dividends_tab"]').on('shown.bs.tab', function() {
                if (issued_dividends_table) {
                    issued_dividends_table.columns.adjust().draw();
                    return;
                }
                issued_dividends_table = $('#issued_dividends_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ action("\Modules\Membership\Http\Controllers\DividendController@getIssuedDividends") }}',
                        error: function(xhr) {
                            if (xhr.status === 403) {
                                $('#issued_dividends_table').closest('.table-responsive').html('<div class="alert alert-warning">@lang("messages.unauthorized")</div>');
                            }
                        }
                    },
                    columns: [
                        { data: 'dividend_date', name: 'dividend_date' },
                        { data: 'member_number', name: 'member_number', orderable: false, searchable: false },
                        { data: 'member_name', name: 'member_name', orderable: false, searchable: false },
                        { data: 'region', name: 'region', orderable: false, searchable: false },
                        { data: 'dividend_amount', name: 'dividend_amount', orderable: false, searchable: false },
                        { data: 'reference_number', name: 'reference_number' },
                        { data: 'created_by', name: 'created_by', orderable: false, searchable: false },
                        { data: 'created_at', name: 'created_at' }
                    ],
                    order: [[0, 'desc']]
                });
            });

            // Reload Last Dividends when tab is clicked
            $('a[href="#last_dividends_tab"]').on('shown.bs.tab', function() {
                loadLastDividends();
            });

            // Print function for Last Dividends
            window.printLastDividends = function() {
                var printContent = $('#last_dividends_content').html();
                var printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write('<html><head><title>@lang("membership::lang.last_dividends")</title>');
                printWindow.document.write('<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(printContent);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            };
        });
    </script>
@endsection

