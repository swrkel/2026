@extends('layouts.app')

@section('title', 'Customer Payment Report')

@section('content')
<section class="content-header">
    <h1>Customer Payment Report <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title pull-left">Customer Payments</h3>
            <div class="pull-right">
                <a href="{{ route('customers.reports.payments.export', request()->only(['payment_start_date', 'payment_end_date', 'customer_id'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
                <button type="button" onclick="window.print()" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
                <a href="{{ route('customers.reports.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Reports</a>
            </div>
        </div>

        <div class="box-body cust-payment-filter-area">
            <form method="GET" action="{{ route('customers.reports.payments') }}" id="customer_payment_report_filter_form">
                <div class="row cust-payment-filter-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="customer_payment_report_date_range">Date Range</label>
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                <input type="text" id="customer_payment_report_date_range" class="form-control" autocomplete="off" placeholder="All dates" readonly>
                            </div>
                            <input type="hidden" name="payment_start_date" id="customer_payment_report_start_date" value="{{ $filters['start_date'] ?? '' }}">
                            <input type="hidden" name="payment_end_date" id="customer_payment_report_end_date" value="{{ $filters['end_date'] ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="customer_payment_report_filter_customer_id">Customers</label>
                            <select name="customer_id" id="customer_payment_report_filter_customer_id" class="form-control select2" style="width:100%;">
                                <option value="">All Customers</option>
                                @foreach(($customers ?? []) as $customerId => $customerLabel)
                                    <option value="{{ $customerId }}" {{ (string)($filters['customer_id'] ?? '') === (string)$customerId ? 'selected' : '' }}>{{ $customerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 cust-payment-filter-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('customers.reports.payments') }}" class="btn btn-default"><i class="fa fa-refresh"></i> Clear</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- IS1968: cust-payment-table-wrap lets the CSS below lift the overflow that
         Bootstrap's .table-responsive applies. .table-responsive sets
         overflow-x: auto WITH overflow-y: hidden, which clips a dropdown opening
         downward out of a cell - the same cause as IS1966 (a) and IS1967 (3). --}}
    <div class="box-body table-responsive cust-payment-table-wrap">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        {{-- IS1968: Action column. Placed first so it is reachable
                             without scrolling a wide table sideways. --}}
                        <th class="cust-payment-action-col">Action</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Payment Ref</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="cust-payment-action-col">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        Action <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left">
                                        {{--
                                            LA-1193: point at THIS module's payment
                                            actions, not the core ones.

                                            These linked to /payments/{id} - the core
                                            TransactionPaymentController - whose edit
                                            requires purchase.create or sell.create, and
                                            whose destroy requires one of five purchase
                                            or sell payment permissions. A user who
                                            works with customer payments has no reason
                                            to hold any of them, so both actions were
                                            refused with 403 and the page reported
                                            "Something went wrong, please try again
                                            later".

                                            The module already has its own edit, update
                                            and destroy on CustomerStandalonePaymentController,
                                            reached through the customer-payments
                                            resource route and gated on
                                            list_customer_payments.delete and the
                                            customers.access middleware. The List
                                            Customer Payments screen already uses them;
                                            this report was the odd one out.

                                            Using them also removes a core dependency
                                            from this module, which is the direction
                                            this codebase is moving in.
                                        --}}
                                        <li>
                                            <a href="#" class="customer-payment-edit"
                                               data-url="{{ url('/customers/customer-payments/' . $row->id . '/edit') }}"
                                               data-update-url="{{ url('/customers/customer-payments/' . $row->id) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.edit')
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" class="customer-payment-delete text-red"
                                               data-url="{{ url('/customers/customer-payments/' . $row->id) }}">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>{{ !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '-' }}</td>
                            <td>{{ $row->customer_name ?? '-' }}<br><small>{{ $row->customer_code ?? '' }}</small></td>
                            <td>{{ $row->payment_ref_no ?? ('PAY-' . ($row->id ?? '')) }}</td>
                            <td>{{ $row->invoice_no ?? '-' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $row->method ?? '-')) }}</td>
                            <td class="text-right">{{ number_format((float)($row->amount ?? 0), 2) }}</td>
                            <td>{{ $row->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No payment records found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-right">Total</th>
                        <th class="text-right">{{ number_format((float)($summary['total_amount'] ?? 0), 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>

{{--
    Payment Report edit modal.

    CustomerStandalonePaymentController@edit returns JSON (the same contract
    used by Customers -> Customer Payments).  This report previously treated
    that JSON as HTML and injected it into an empty .modal container. Bootstrap
    therefore showed only the dark backdrop, with no dialog to display.

    Keep the report on the Customers-owned customer-payments resource routes
    and render the form locally so the existing edit endpoint and update logic
    remain unchanged.
--}}
<div class="modal fade" id="customerPaymentReportEditModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('messages.edit') @lang('lang_v1.payment')</h4>
            </div>

            <form id="customerPaymentReportEditForm">
                @csrf
                <input type="hidden" id="customer_payment_report_edit_id" name="payment_id">
                <input type="hidden" id="customer_payment_report_update_url">
                <input type="hidden" id="customer_payment_report_location_id" name="location_id">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="customer_payment_report_customer_id">@lang('lang_v1.customer')</label>
                        <select class="form-control" id="customer_payment_report_customer_id" name="customer_id" required>
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="customer_payment_report_paid_on">@lang('lang_v1.date')</label>
                        <input type="date" class="form-control" id="customer_payment_report_paid_on" name="paid_on" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_payment_report_amount">@lang('lang_v1.amount')</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="customer_payment_report_amount" name="amount" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_payment_report_method">@lang('lang_v1.payment_method')</label>
                        <select class="form-control" id="customer_payment_report_method" name="method" required>
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="customer_payment_report_account_id">@lang('lang_v1.payment_account')</label>
                        <select class="form-control" id="customer_payment_report_account_id" name="account_id">
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>

                    <div id="customer_payment_report_bank_details" style="display:none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="customer_payment_report_bank_name">Bank</label>
                                    <input type="text" class="form-control" id="customer_payment_report_bank_name" name="bank_name" maxlength="191">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="customer_payment_report_cheque_number">Cheque No</label>
                                    <input type="text" class="form-control" id="customer_payment_report_cheque_number" name="cheque_number" maxlength="191">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="customer_payment_report_cheque_date">Cheque Date</label>
                                    <input type="date" class="form-control" id="customer_payment_report_cheque_date" name="cheque_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="customer_payment_report_note">@lang('lang_v1.payment_note')</label>
                        <textarea class="form-control" id="customer_payment_report_note" name="note" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary" id="customer_payment_report_save_btn">
                        <i class="fa fa-save"></i> @lang('messages.save')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('css')
@parent
<style>
    /*
     * A box cannot have overflow-x: auto with overflow-y: visible - the browser
     * promotes visible to auto and it clips again. Both axes must be visible.
     * Action is the first column, so nothing needs horizontal scrolling.
     */
    .cust-payment-table-wrap { overflow: visible !important; }
    .cust-payment-action-col { width: 90px; white-space: nowrap; }
    .cust-payment-table-wrap .btn-group.open .dropdown-menu { z-index: 1055; }
    .cust-payment-filter-area {
        border-bottom: 1px solid #e8edf3;
        background: #fbfcfe;
        padding-top: 16px;
        padding-bottom: 8px;
    }
    .cust-payment-filter-row { display: flex; align-items: flex-end; flex-wrap: wrap; }
    .cust-payment-filter-area label { font-weight: 700; color: #344054; }
    .cust-payment-filter-actions { padding-bottom: 15px; display: flex; gap: 8px; }
    .cust-payment-filter-area .select2-container { width: 100% !important; }
    @media (max-width: 991px) {
        .cust-payment-filter-row { display: block; }
        .cust-payment-filter-actions { padding-top: 0; }
    }
    @media print {
        .cust-payment-filter-area { display: none !important; }
    }
</style>
@endsection

@section('javascript')
@parent
<script>
$(function () {
    var $editModal = $('#customerPaymentReportEditModal');
    var $editForm = $('#customerPaymentReportEditForm');
    var editWasSaved = false;
    var paymentAccountMap = {};
    var loadedPaymentMethod = '';
    var loadedPaymentAccountId = '';
    var loadedPaymentAccountName = '';

    // IS2272: Date Range + Customer filters. Keep the visible range and hidden
    // ISO dates separate so server filtering is independent of display format.
    var reportStartDate = @json($filters['start_date'] ?? '');
    var reportEndDate = @json($filters['end_date'] ?? '');
    var $reportDateRange = $('#customer_payment_report_date_range');

    if ($('#customer_payment_report_filter_customer_id').length && $.fn.select2) {
        $('#customer_payment_report_filter_customer_id').select2({ width: '100%' });
    }

    if ($reportDateRange.length && $.fn.daterangepicker && window.moment) {
        var reportDateRangeSettings = typeof dateRangeSettings !== 'undefined'
            ? Object.assign({}, dateRangeSettings)
            : {};
        reportDateRangeSettings.autoUpdateInput = false;

        $reportDateRange.daterangepicker(reportDateRangeSettings, function (start, end) {
            var displayFormat = typeof moment_date_format !== 'undefined' ? moment_date_format : 'YYYY-MM-DD';
            $reportDateRange.val(start.format(displayFormat) + ' - ' + end.format(displayFormat));
            $('#customer_payment_report_start_date').val(start.format('YYYY-MM-DD'));
            $('#customer_payment_report_end_date').val(end.format('YYYY-MM-DD'));
        });

        if (reportStartDate && reportEndDate) {
            var picker = $reportDateRange.data('daterangepicker');
            picker.setStartDate(moment(reportStartDate, 'YYYY-MM-DD'));
            picker.setEndDate(moment(reportEndDate, 'YYYY-MM-DD'));
            var displayFormat = typeof moment_date_format !== 'undefined' ? moment_date_format : 'YYYY-MM-DD';
            $reportDateRange.val(
                moment(reportStartDate, 'YYYY-MM-DD').format(displayFormat) + ' - ' +
                moment(reportEndDate, 'YYYY-MM-DD').format(displayFormat)
            );
        }

        $reportDateRange.on('cancel.daterangepicker', function () {
            $(this).val('');
            $('#customer_payment_report_start_date, #customer_payment_report_end_date').val('');
        });
    }

    function showPaymentActionError(message) {
        if (window.toastr && typeof window.toastr.error === 'function') {
            window.toastr.error(message);
        } else {
            window.alert(message);
        }
    }

    function showPaymentActionSuccess(message) {
        if (window.toastr && typeof window.toastr.success === 'function') {
            window.toastr.success(message);
        }
    }

    function paymentMethodLabel(method) {
        return String(method || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function populatePaymentMethods(methods, payment) {
        var $method = $('#customer_payment_report_method');
        $method.empty().append($('<option>', { value: '', text: 'Please select' }));

        $.each(methods || {}, function (value, label) {
            $('<option>', {
                value: String(value),
                text: String(label)
            }).appendTo($method);
        });

        var currentMethod = payment && payment.method ? String(payment.method) : '';
        if (currentMethod && $method.find('option').filter(function () {
            return $(this).val() === currentMethod;
        }).length === 0) {
            $('<option>', {
                value: currentMethod,
                text: paymentMethodLabel(currentMethod)
            }).appendTo($method);
        }

        $method.val(currentMethod);
    }

    function populatePaymentAccounts(method, selectedAccountId, selectedAccountName) {
        var $account = $('#customer_payment_report_account_id');

        if ($account.hasClass('select2-hidden-accessible') && $.fn.select2) {
            $account.select2('destroy');
        }

        $account.empty().append($('<option>', { value: '', text: 'Please select' }));
        var options = paymentAccountMap && paymentAccountMap[method] ? paymentAccountMap[method] : {};

        $.each(options || {}, function (id, label) {
            $('<option>', {
                value: String(id),
                text: String(label)
            }).appendTo($account);
        });

        var selectedId = selectedAccountId ? String(selectedAccountId) : '';
        if (selectedId && $account.find('option').filter(function () {
            return $(this).val() === selectedId;
        }).length === 0) {
            $('<option>', {
                value: selectedId,
                text: selectedAccountName || ('Account #' + selectedId)
            }).appendTo($account);
        }

        $account.val(selectedId);

        if ($.fn.select2) {
            $account.select2({
                width: '100%',
                dropdownParent: $editModal
            });
        }
    }

    function usesBankDetails(method) {
        return $.inArray(String(method || '').toLowerCase(), [
            'bank',
            'online',
            'cheque',
            'bank_transfer',
            'direct_bank_deposit',
            'bank_deposit'
        ]) !== -1;
    }

    function toggleBankDetails(method) {
        $('#customer_payment_report_bank_details').toggle(usesBankDetails(method));
    }

    function populatePaymentCustomers(customers, payment) {
        var $customer = $('#customer_payment_report_customer_id');

        // Rebuild on each edit so the list always reflects the current business.
        if ($customer.hasClass('select2-hidden-accessible') && $.fn.select2) {
            $customer.select2('destroy');
        }
        $customer.empty().append($('<option>', { value: '', text: 'Please select' }));

        $.each(customers || {}, function (id, label) {
            $('<option>', {
                value: String(id),
                text: String(label)
            }).appendTo($customer);
        });

        var currentCustomerId = payment && payment.customer_id
            ? String(payment.customer_id)
            : '';

        // The edit endpoint normally includes the current customer in the list,
        // but keep this fallback so an older/inactive assignment can still be
        // displayed instead of showing a blank field.
        if (currentCustomerId && $customer.find('option').filter(function () {
            return $(this).val() === currentCustomerId;
        }).length === 0) {
            $('<option>', {
                value: currentCustomerId,
                text: (payment && payment.customer_name) || ('Customer #' + currentCustomerId)
            }).appendTo($customer);
        }

        $customer.val(currentCustomerId);

        if ($.fn.select2) {
            $customer.select2({
                width: '100%',
                dropdownParent: $editModal
            });
        }
    }

    /*
     * Payment edit endpoint returns JSON, not modal HTML. Populate this page's
     * real Bootstrap modal from that response. This is the same API contract
     * already used by Customers -> Customer Payments.
     */
    $(document)
        .off('click.custPaymentActions')
        .on('click.custPaymentActions', '.customer-payment-edit', function (event) {
            event.preventDefault();

            var editUrl = $(this).data('url');
            var updateUrl = $(this).data('update-url');

            $.ajax({
                method: 'GET',
                url: editUrl,
                dataType: 'json'
            }).done(function (result) {
                if (!result || result.success !== true || !result.payment) {
                    showPaymentActionError((result && result.msg) || 'The payment could not be opened for editing.');
                    return;
                }

                var payment = result.payment;
                populatePaymentCustomers(result.customers, payment);

                paymentAccountMap = result.payment_accounts || {};
                loadedPaymentMethod = payment.method ? String(payment.method) : '';
                loadedPaymentAccountId = payment.account_id ? String(payment.account_id) : '';
                loadedPaymentAccountName = payment.account_name || '';

                populatePaymentMethods(result.payment_methods || {}, payment);
                populatePaymentAccounts(loadedPaymentMethod, loadedPaymentAccountId, loadedPaymentAccountName);

                $('#customer_payment_report_edit_id').val(payment.id);
                $('#customer_payment_report_update_url').val(updateUrl);
                $('#customer_payment_report_paid_on').val(payment.paid_on ? String(payment.paid_on).substring(0, 10) : '');
                $('#customer_payment_report_amount').val(payment.amount);
                $('#customer_payment_report_note').val(payment.note || '');
                $('#customer_payment_report_location_id').val(payment.location_id || '');
                $('#customer_payment_report_bank_name').val(payment.bank_name || '');
                $('#customer_payment_report_cheque_number').val(payment.cheque_number || '');
                $('#customer_payment_report_cheque_date').val(payment.cheque_date ? String(payment.cheque_date).substring(0, 10) : '');
                toggleBankDetails(loadedPaymentMethod);

                editWasSaved = false;
                $editModal.modal('show');
            }).fail(function (xhr) {
                var message = xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg);
                showPaymentActionError(message || 'The payment could not be opened for editing.');
            });
        })
        .on('click.custPaymentActions', '.customer-payment-delete', function (event) {
            event.preventDefault();

            if (!window.confirm('This payment will be deleted. Continue?')) {
                return;
            }

            $.ajax({
                url: $(this).data('url'),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'DELETE'
                }
            }).done(function (response) {
                if (response && response.success === false) {
                    showPaymentActionError(response.msg || 'The payment could not be deleted.');
                    return;
                }

                window.location.reload();
            }).fail(function (xhr) {
                showPaymentActionError(
                    (xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) ||
                    'The payment could not be deleted.'
                );
            });
        });

    $('#customer_payment_report_method').on('change', function () {
        var method = String($(this).val() || '');
        var selectedId = method === loadedPaymentMethod ? loadedPaymentAccountId : '';
        var selectedName = method === loadedPaymentMethod ? loadedPaymentAccountName : '';

        populatePaymentAccounts(method, selectedId, selectedName);
        toggleBankDetails(method);
    });

    $editForm.on('submit', function (event) {
        event.preventDefault();

        var updateUrl = $('#customer_payment_report_update_url').val();
        if (!updateUrl) {
            showPaymentActionError('The payment update URL is missing. Please reopen the payment and try again.');
            return;
        }

        var $saveButton = $('#customer_payment_report_save_btn');
        $saveButton.prop('disabled', true);

        $.ajax({
            method: 'PUT',
            url: updateUrl,
            data: $editForm.serialize(),
            dataType: 'json'
        }).done(function (result) {
            if (!result || result.success !== true) {
                showPaymentActionError((result && result.msg) || 'The payment could not be saved.');
                return;
            }

            editWasSaved = true;
            showPaymentActionSuccess(result.msg || 'Payment updated successfully.');
            $editModal.modal('hide');
        }).fail(function (xhr) {
            var message = xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message);
            showPaymentActionError(message || 'The payment could not be saved.');
        }).always(function () {
            $saveButton.prop('disabled', false);
        });
    });

    $editModal.on('hidden.bs.modal', function () {
        $editForm[0].reset();
        $('#customer_payment_report_edit_id, #customer_payment_report_update_url, #customer_payment_report_location_id').val('');

        paymentAccountMap = {};
        loadedPaymentMethod = '';
        loadedPaymentAccountId = '';
        loadedPaymentAccountName = '';
        $('#customer_payment_report_bank_details').hide();

        var $account = $('#customer_payment_report_account_id');
        if ($account.hasClass('select2-hidden-accessible') && $.fn.select2) {
            $account.select2('destroy');
        }
        $account.empty().append($('<option>', { value: '', text: 'Please select' }));

        $('#customer_payment_report_method').empty().append($('<option>', { value: '', text: 'Please select' }));

        var $customer = $('#customer_payment_report_customer_id');
        if ($customer.hasClass('select2-hidden-accessible') && $.fn.select2) {
            $customer.select2('destroy');
        }
        $customer.empty().append($('<option>', { value: '', text: 'Please select' }));

        // Refresh only after a successful save so the amount, method, note and
        // report total reflect the saved database values. Closing/cancelling an
        // edit no longer reloads the report unnecessarily.
        if (editWasSaved) {
            window.location.reload();
        }
    });
});
</script>
@endsection
