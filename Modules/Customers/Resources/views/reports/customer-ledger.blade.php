@extends('layouts.app')

@section('title', 'Customer Ledger Report')

@section('content')
@php
    $summary = $summary ?? [
        'opening_balance' => 0,
        'debit' => 0,
        'credit' => 0,
        'balance' => 0,
        'outstanding_total' => 0,
    ];
    $rows = collect($rows ?? []);
    $customers = $customers ?? [];
    $selectedCustomerId = $selectedCustomerId ?? null;
    $dateFilter = $dateFilter ?? [
        'preset' => 'current_month',
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ];
@endphp

<style>
    .customer-ledger-report {
        --ledger-blue: #2563eb;
        --ledger-green: #16a34a;
        --ledger-orange: #f59e0b;
        --ledger-purple: #7c3aed;
        --ledger-ink: #0f172a;
        --ledger-muted: #64748b;
    }
    .customer-ledger-report .ledger-filter-card {
        margin-bottom: 18px;
        padding: 18px 20px;
        border: 1px solid #dfe8f3;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }
    .customer-ledger-report .ledger-filter-grid {
        display: grid;
        grid-template-columns: minmax(210px, 1.15fr) minmax(280px, 1.65fr) minmax(155px, .85fr) minmax(155px, .85fr) auto;
        gap: 14px;
        align-items: end;
    }
    .customer-ledger-report .ledger-filter-field label {
        display: block;
        margin: 0 0 7px;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }
    .customer-ledger-report .ledger-filter-field .form-control,
    .customer-ledger-report .ledger-filter-field .select2-container .select2-selection--single {
        min-height: 42px;
        border-radius: 11px;
        border-color: #d6e0ed;
    }
    .customer-ledger-report .ledger-filter-field .select2-container .select2-selection--single {
        padding-top: 5px;
    }
    .customer-ledger-report .ledger-filter-apply {
        min-height: 42px;
        padding: 9px 18px;
        border-radius: 11px;
        font-weight: 800;
    }
    .customer-ledger-report .ledger-period-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 12px;
        padding: 6px 11px;
        border-radius: 999px;
        background: #eef4ff;
        color: #2456bd;
        font-size: 12px;
        font-weight: 700;
    }
    .customer-ledger-report .ledger-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }
    .customer-ledger-report .ledger-summary-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 104px;
        padding: 17px 18px;
        border: 1px solid #e2eaf4;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .055);
        overflow: hidden;
    }
    .customer-ledger-report .ledger-summary-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--card-color);
    }
    .customer-ledger-report .ledger-summary-card.opening { --card-color: var(--ledger-blue); --card-soft: #eef4ff; }
    .customer-ledger-report .ledger-summary-card.debit { --card-color: var(--ledger-purple); --card-soft: #f4efff; }
    .customer-ledger-report .ledger-summary-card.credit { --card-color: var(--ledger-green); --card-soft: #eefbf2; }
    .customer-ledger-report .ledger-summary-card.balance { --card-color: var(--ledger-orange); --card-soft: #fff8e8; }
    .customer-ledger-report .ledger-summary-icon {
        width: 48px;
        height: 48px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--card-color);
        background: var(--card-soft);
        font-size: 21px;
    }
    .customer-ledger-report .ledger-summary-label {
        display: block;
        margin-bottom: 7px;
        color: #6b7a92;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .055em;
    }
    .customer-ledger-report .ledger-summary-value {
        display: block;
        color: var(--card-color);
        font-size: clamp(19px, 1.7vw, 27px);
        font-weight: 850;
        line-height: 1.1;
        white-space: nowrap;
    }
    .customer-ledger-report .ledger-box {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 9px 28px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .customer-ledger-report .ledger-box .box-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e8eef6;
    }
    .customer-ledger-report .ledger-table-wrap {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .customer-ledger-report #customer_ledger_report_table {
        width: 100% !important;
        min-width: 850px;
        table-layout: fixed;
    }
    .customer-ledger-report #customer_ledger_report_table th,
    .customer-ledger-report #customer_ledger_report_table td {
        vertical-align: middle;
        white-space: normal;
        word-break: break-word;
    }
    .customer-ledger-report #customer_ledger_report_table th {
        background: #357ca5;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
    }
    .customer-ledger-report #customer_ledger_report_table th:nth-child(1),
    .customer-ledger-report #customer_ledger_report_table td:nth-child(1) { width: 12%; }
    .customer-ledger-report #customer_ledger_report_table th:nth-child(2),
    .customer-ledger-report #customer_ledger_report_table td:nth-child(2) { width: 18%; }
    .customer-ledger-report #customer_ledger_report_table th:nth-child(3),
    .customer-ledger-report #customer_ledger_report_table td:nth-child(3) { width: 24%; }
    .customer-ledger-report #customer_ledger_report_table th:nth-child(4),
    .customer-ledger-report #customer_ledger_report_table td:nth-child(4) { width: 12%; }
    .customer-ledger-report #customer_ledger_report_table th:nth-child(5),
    .customer-ledger-report #customer_ledger_report_table td:nth-child(5) { width: 22%; }
    .customer-ledger-report #customer_ledger_report_table th:nth-child(6),
    .customer-ledger-report #customer_ledger_report_table td:nth-child(6) { width: 12%; }
    .customer-ledger-report .customer-opening-balance-row td {
        background: #fff8e1 !important;
        font-weight: 700;
    }
    .customer-ledger-report .ledger-status-wrap {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }
    .customer-ledger-report .ledger-status-label {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        background: #eef4ff;
        color: #2456bd;
        font-size: 11px;
        font-weight: 800;
    }
    .customer-ledger-report .customer-ledger-bills-btn {
        border: 0;
        border-radius: 7px;
        background: #f59e0b;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        padding: 5px 9px;
        box-shadow: 0 5px 12px rgba(245, 158, 11, .22);
    }
    .customer-ledger-report .ledger-total-card {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 12px;
        background: #eef6ff;
        border: 1px solid #d8e8ff;
        color: #164e9d;
        font-weight: 700;
    }
    #customer_ledger_bills_modal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 22px 50px rgba(15, 23, 42, .26);
    }
    #customer_ledger_bills_modal .modal-header {
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #174fc4);
    }
    #customer_ledger_bills_modal .modal-header .close { color: #fff; opacity: .9; }
    #customer_ledger_bills_modal .bills-reference {
        display: inline-block;
        margin-bottom: 12px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eef4ff;
        color: #2456bd;
        font-weight: 800;
    }
    @media (max-width: 1199px) {
        .customer-ledger-report .ledger-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .customer-ledger-report .ledger-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767px) {
        .customer-ledger-report .ledger-filter-grid,
        .customer-ledger-report .ledger-summary-grid { grid-template-columns: 1fr; }
        .customer-ledger-report .ledger-filter-apply { width: 100%; }
        .customer-ledger-report .ledger-summary-value { white-space: normal; }
    }
    @media print {
        .customer-ledger-report .ledger-filter-card,
        .customer-ledger-report [data-customers-grid-toolbar],
        .customer-ledger-report .box-header .pull-right,
        .customer-ledger-report .dataTables_info,
        .customer-ledger-report .dataTables_paginate,
        .customer-ledger-report .customer-ledger-bills-btn {
            display: none !important;
        }
        .customer-ledger-report .ledger-box {
            border: 0 !important;
            box-shadow: none !important;
        }
    }
</style>

<section class="content-header">
    <h1>Customer Ledger Report <small>Customers Module</small></h1>
</section>

<section class="content customer-ledger-report">
    <form method="GET" action="{{ route('customers.reports.ledger') }}" class="ledger-filter-card" id="customer_ledger_report_filter_form">
        <div class="ledger-filter-grid">
            <div class="ledger-filter-field">
                <label for="customer_ledger_report_date_preset">Date Range</label>
                <select name="date_preset" id="customer_ledger_report_date_preset" class="form-control">
                    <option value="current_month" {{ $dateFilter['preset'] === 'current_month' ? 'selected' : '' }}>Current Month</option>
                    <option value="today" {{ $dateFilter['preset'] === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $dateFilter['preset'] === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last_7_days" {{ $dateFilter['preset'] === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="last_30_days" {{ $dateFilter['preset'] === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="last_month" {{ $dateFilter['preset'] === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ $dateFilter['preset'] === 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="last_year" {{ $dateFilter['preset'] === 'last_year' ? 'selected' : '' }}>Last Year</option>
                    <option value="this_fy" {{ $dateFilter['preset'] === 'this_fy' ? 'selected' : '' }}>This FY</option>
                    <option value="last_fy" {{ $dateFilter['preset'] === 'last_fy' ? 'selected' : '' }}>Last FY</option>
                    <option value="custom" {{ $dateFilter['preset'] === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>

            <div class="ledger-filter-field">
                <label for="customer_ledger_report_customer_id">Customers</label>
                <select name="customer_id" id="customer_ledger_report_customer_id" class="form-control select2" style="width:100%;">
                    <option value="">All</option>
                    @foreach($customers as $customerId => $customerLabel)
                        <option value="{{ $customerId }}" {{ (string) $selectedCustomerId === (string) $customerId ? 'selected' : '' }}>{{ $customerLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ledger-filter-field customer-ledger-report-custom-date">
                <label for="customer_ledger_report_start_date">Start Date</label>
                <input type="date" name="ledger_start_date" id="customer_ledger_report_start_date" class="form-control" value="{{ $dateFilter['start_date'] }}">
            </div>

            <div class="ledger-filter-field customer-ledger-report-custom-date">
                <label for="customer_ledger_report_end_date">End Date</label>
                <input type="date" name="ledger_end_date" id="customer_ledger_report_end_date" class="form-control" value="{{ $dateFilter['end_date'] }}">
            </div>

            <div>
                <button type="submit" class="btn btn-primary ledger-filter-apply"><i class="fa fa-filter"></i> Apply</button>
            </div>
        </div>
        <span class="ledger-period-badge">
            <i class="fa fa-calendar"></i>
            {{ \Carbon\Carbon::parse($dateFilter['start_date'])->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateFilter['end_date'])->format('d M Y') }}
        </span>
    </form>

    <div class="ledger-summary-grid">
        <div class="ledger-summary-card opening">
            <span class="ledger-summary-icon"><i class="fa fa-history"></i></span>
            <div>
                <span class="ledger-summary-label">Opening Balance</span>
                <strong class="ledger-summary-value">{{ number_format((float)($summary['opening_balance'] ?? 0), 2) }}</strong>
            </div>
        </div>
        <div class="ledger-summary-card debit">
            <span class="ledger-summary-icon"><i class="fa fa-arrow-down"></i></span>
            <div>
                <span class="ledger-summary-label">Total Debit</span>
                <strong class="ledger-summary-value">{{ number_format((float)($summary['debit'] ?? 0), 2) }}</strong>
            </div>
        </div>
        <div class="ledger-summary-card credit">
            <span class="ledger-summary-icon"><i class="fa fa-arrow-up"></i></span>
            <div>
                <span class="ledger-summary-label">Total Credit</span>
                <strong class="ledger-summary-value">{{ number_format((float)($summary['credit'] ?? 0), 2) }}</strong>
            </div>
        </div>
        <div class="ledger-summary-card balance">
            <span class="ledger-summary-icon"><i class="fa fa-balance-scale"></i></span>
            <div>
                <span class="ledger-summary-label">Balance Due</span>
                <strong class="ledger-summary-value">{{ number_format((float)($summary['balance'] ?? 0), 2) }}</strong>
            </div>
        </div>
    </div>

    <div class="box box-primary ledger-box">
        <div class="box-header with-border clearfix">
            <h3 class="box-title pull-left">Customer Ledger</h3>
            <div class="pull-right">
                <span class="ledger-total-card">
                    <i class="fa fa-balance-scale"></i>
                    Outstanding Total:
                    <span>{{ number_format((float)($summary['outstanding_total'] ?? 0), 2) }}</span>
                </span>
                <a href="{{ route('customers.reports.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Reports</a>
            </div>
        </div>
        <div class="box-body">
            @include('customers::partials.erp-ajax-datatable-standard', [
                'table_id' => 'customer_ledger_report_table',
                'default_per_page' => 'all',
                'length_options' => [-1, 10, 25, 50, 100, 250, 500],
                'search_placeholder' => 'Search ledger details...'
            ])

            <div class="ledger-table-wrap">
                <table id="customer_ledger_report_table" class="table table-bordered table-striped table-hover" width="100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Invoice/Ref</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $description = strtolower((string)($row->description ?? ''));
                                $transactionType = strtolower((string)($row->transaction_type ?? $row->type ?? ''));
                                $isOpeningBalance = !empty($row->is_opening_balance)
                                    || in_array($transactionType, ['opening_balance', 'fleet_opening_balance'], true)
                                    || str_contains($description, 'opening balance');
                                $billDetails = is_array($row->bulk_payment_bills ?? null) ? $row->bulk_payment_bills : [];
                                $invoiceReference = $isOpeningBalance
                                    ? 'Opening Balance'
                                    : ($row->invoice_no ?? $row->ref_no ?? $row->description ?? '-');
                                $statusLabel = ucwords(str_replace('_', ' ', (string)($row->payment_status ?? '-')));
                            @endphp
                            <tr class="{{ $isOpeningBalance ? 'customer-opening-balance-row' : '' }}">
                                <td data-order="{{ $isOpeningBalance ? 9999999999 : (!empty($row->transaction_date) ? strtotime($row->transaction_date) : 0) }}">
                                    {{ !empty($row->transaction_date) && strtotime($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '-' }}
                                </td>
                                <td>{{ $row->customer_name ?? '-' }}<br><small>{{ $row->customer_code ?? '' }}</small></td>
                                <td>{{ $invoiceReference }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $row->transaction_type ?? $row->type ?? '-')) }}</td>
                                <td>
                                    <div class="ledger-status-wrap">
                                        <span class="ledger-status-label">{{ $statusLabel }}</span>
                                        @if(!empty($billDetails))
                                            <button
                                                type="button"
                                                class="customer-ledger-bills-btn"
                                                data-reference="{{ $row->payment_ref_no ?? '' }}"
                                                data-bills="{{ e(json_encode($billDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
                                            >Bills</button>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right" data-order="{{ (float)($row->amount ?? $row->final_total ?? 0) }}">
                                    {{ number_format((float)($row->amount ?? $row->final_total ?? 0), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Balance Due</th>
                            <th class="text-right">{{ number_format((float)($summary['balance'] ?? 0), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="customer_ledger_bills_modal" tabindex="-1" role="dialog" aria-labelledby="customer_ledger_bills_modal_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="customer_ledger_bills_modal_title"><i class="fa fa-file-text-o"></i> Bulk Payment Bills</h4>
            </div>
            <div class="modal-body">
                <span class="bills-reference" id="customer_ledger_bills_reference"></span>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="customer_ledger_bills_rows"></tbody>
                        <tfoot>
                            <tr>
                                <th class="text-right">Total</th>
                                <th class="text-right" id="customer_ledger_bills_total">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('model-scritps')
@parent
<script>
(function ($) {
    'use strict';

    $(function () {
        var filterForm = $('#customer_ledger_report_filter_form');
        var preset = $('#customer_ledger_report_date_preset');
        var customer = $('#customer_ledger_report_customer_id');
        var customDates = $('.customer-ledger-report-custom-date');

        function updateCustomDates() {
            customDates.toggle(preset.val() === 'custom');
        }

        if ($.fn.select2) {
            customer.select2({
                width: '100%',
                placeholder: 'All',
                allowClear: true
            });
        }

        var isNavigating = false;

        function submitLedgerFilter() {
            if (isNavigating || !filterForm.length) {
                return;
            }
            isNavigating = true;
            // Use the browser's native submit method. jQuery trigger('submit')
            // can invoke unrelated global report handlers registered by the
            // host application.
            HTMLFormElement.prototype.submit.call(filterForm.get(0));
        }

        preset.off('change.customerLedgerReport').on('change.customerLedgerReport', function () {
            updateCustomDates();
            if (preset.val() !== 'custom') {
                submitLedgerFilter();
            }
        });

        customer.off('change.customerLedgerReport').on('change.customerLedgerReport', function () {
            submitLedgerFilter();
        });

        updateCustomDates();

        var tableId = '#customer_ledger_report_table';
        if ($.fn.DataTable) {
            if ($.fn.DataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }

            var buttons = [];
            var availableButtons = $.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.buttons
                ? $.fn.dataTable.ext.buttons
                : {};

            if (availableButtons.copyHtml5 || availableButtons.copy) {
                buttons.push({extend: availableButtons.copyHtml5 ? 'copyHtml5' : 'copy', exportOptions: {columns: ':visible'}});
            }
            if (availableButtons.csvHtml5 || availableButtons.csv) {
                buttons.push({extend: availableButtons.csvHtml5 ? 'csvHtml5' : 'csv', title: 'Customer Ledger', exportOptions: {columns: ':visible'}});
            }
            if (availableButtons.excelHtml5 || availableButtons.excel) {
                buttons.push({extend: availableButtons.excelHtml5 ? 'excelHtml5' : 'excel', title: 'Customer Ledger', exportOptions: {columns: ':visible'}});
            }
            if (availableButtons.pdfHtml5 || availableButtons.pdf) {
                buttons.push({extend: availableButtons.pdfHtml5 ? 'pdfHtml5' : 'pdf', title: 'Customer Ledger', orientation: 'landscape', pageSize: 'A4', exportOptions: {columns: ':visible'}});
            }
            if (availableButtons.print) {
                buttons.push({extend: 'print', title: 'Customer Ledger', exportOptions: {columns: ':visible'}});
            }
            if (availableButtons.colvis) {
                buttons.push({extend: 'colvis', columns: ':visible'});
            }

            $(tableId).DataTable({
                deferRender: true,
                autoWidth: false,
                pageLength: -1,
                lengthMenu: [[-1, 10, 25, 50, 100, 250, 500], ['All', 10, 25, 50, 100, 250, 500]],
                order: [[0, 'desc']],
                dom: buttons.length ? 'Brtip' : 'rtip',
                buttons: buttons,
                columnDefs: [
                    {targets: 1, width: '18%'},
                    {targets: 5, width: '12%', className: 'text-right'}
                ],
                drawCallback: function () {
                    if (typeof __currency_convert_recursively === 'function') {
                        __currency_convert_recursively($('.customer-ledger-report'));
                    }
                }
            });
        }

        $(document).on('click', '.customer-ledger-bills-btn', function () {
            var bills = [];
            try {
                bills = JSON.parse($(this).attr('data-bills') || '[]');
            } catch (ignore) {
                bills = [];
            }

            var rows = '';
            var total = 0;
            bills.forEach(function (bill) {
                var amount = parseFloat(bill.amount || 0) || 0;
                total += amount;
                rows += '<tr><td>' + $('<div>').text(bill.bill_no || '-').html() + '</td>' +
                    '<td class="text-right">' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td></tr>';
            });

            if (!rows) {
                rows = '<tr><td colspan="2" class="text-center text-muted">No allocated bills found.</td></tr>';
            }

            $('#customer_ledger_bills_reference').text($(this).data('reference') || 'Bulk Payment');
            $('#customer_ledger_bills_rows').html(rows);
            $('#customer_ledger_bills_total').text(total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#customer_ledger_bills_modal').modal('show');
        });
    });
})(jQuery);
</script>
@endsection
