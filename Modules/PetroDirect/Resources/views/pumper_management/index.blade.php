@extends('layouts.app')

@section('title', __('petrodirect::lang.pumper_management'))

@section('content')
<section class="content-header">
    <h1>@lang('petrodirect::lang.pumper_management')</h1>
</section>

<section class="content petrodirect-pumper-management-page">
    <div class="nav-tabs-custom petrodirect-pumper-tabs">
        <ul class="nav nav-tabs petrodirect-pumper-management-tabs">
            <li class="{{ $active_tab == 'operators' ? 'active' : '' }}">
                <a href="#operators_tab" data-toggle="tab" data-tab="operators"><i class="fa fa-users"></i> @lang('petrodirect::lang.pump_operators')</a>
            </li>
            <li class="{{ $active_tab == 'payments' ? 'active' : '' }}">
                <a href="#payments_tab" data-toggle="tab" data-tab="payments"><i class="fa fa-minus"></i> @lang('petrodirect::lang.pumper_excess_shortage_payments')</a>
            </li>
            <li class="{{ $active_tab == 'pumper_day_entries' ? 'active' : '' }}">
                <a href="#pumper_day_entries_tab" data-toggle="tab" data-tab="pumper_day_entries"><i class="fa fa-calculator"></i> @lang('petrodirect::lang.pumper_day_entries')</a>
            </li>
            <li class="{{ $active_tab == 'shift_summary' ? 'active' : '' }}">
                <a href="#shift_summary_tab" data-toggle="tab" data-tab="shift_summary"><i class="fa fa-clock-o"></i> @lang('petrodirect::lang.shift_summary')</a>
            </li>
            <li class="{{ $active_tab == 'payment_summary' ? 'active' : '' }}">
                <a href="#payment_summary_tab" data-toggle="tab" data-tab="payment_summary"><i class="fa fa-money"></i> @lang('petrodirect::lang.payment_summary')</a>
            </li>
            <li class="{{ $active_tab == 'meters_with_payments' ? 'active' : '' }}">
                <a href="#meters_with_payments_tab" data-toggle="tab" data-tab="meters_with_payments"><i class="fa fa-money"></i> @lang('petrodirect::lang.meters_with_payments')</a>
            </li>
            <li class="{{ $active_tab == 'daily_pump_status' ? 'active' : '' }}">
                <a href="#daily_pump_status_tab" data-toggle="tab" data-tab="daily_pump_status"><i class="fa fa-calculator"></i> @lang('petrodirect::lang.daily_pump_status')</a>
            </li>
            <li class="{{ $active_tab == 'close_shift' ? 'active' : '' }}">
                <a href="#close_shift_tab" data-toggle="tab" data-tab="close_shift"><i class="fa fa-ban"></i> @lang('petrodirect::lang.close_shift')</a>
            </li>
            <li class="{{ $active_tab == 'current_meter' ? 'active' : '' }}">
                <a href="#current_meter_tab" data-toggle="tab" data-tab="current_meter"><i class="fa fa-thermometer-half"></i> @lang('petrodirect::lang.current_meter')</a>
            </li>
            <li class="{{ $active_tab == 'unload_stock' ? 'active' : '' }}">
                <a href="#unload_stock_tab" data-toggle="tab" data-tab="unload_stock"><i class="fa fa-arrow-down"></i> @lang('petrodirect::lang.unload_stock')</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane {{ $active_tab == 'operators' ? 'active' : '' }}" id="operators_tab">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('petrodirect::lang.all_pumpers')</h3>
                        <div class="box-tools">
                            <a href="{{ route('petrodirect.pumper-management.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> @lang('messages.add')</a>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="petrodirect_pumper_table" style="width: 100%;">
                                {{-- MA-002 (LA-1136): the figure columns, matching Petro's
                                     Pumper Management list. Order here must match the
                                     columns[] array in the script below - DataTables pairs
                                     them by position, and a mismatch silently shifts every
                                     value one column left. --}}
                                <thead><tr>
                                    <th>@lang('petrodirect::lang.action')</th>
                                    <th>@lang('petrodirect::lang.name')</th>
                                    <th><span>Current</span><br><span>Balance</span></th>
                                    <th><span>Balance</span><br><span>For Period</span></th>
                                    <th><span>Sold Qty</span><br><span>In Lts</span></th>
                                    <th><span>Sale Amount</span><br><span>(Fuel)</span></th>
                                    <th><span>Commission</span><br><span>Type</span></th>
                                    <th><span>Commission</span><br><span>Rate</span></th>
                                    <th><span>Commission</span><br><span>Amount</span></th>
                                    <th><span>Excess</span><br><span>Amount</span></th>
                                    <th><span>Shortage</span><br><span>Amount</span></th>
                                    <th>@lang('petrodirect::lang.mobile')</th>
                                    <th>@lang('petrodirect::lang.username')</th>
                                    <th>@lang('petrodirect::lang.location')</th>
                                    <th>@lang('petrodirect::lang.status')</th>
                                </tr></thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane {{ $active_tab == 'payments' ? 'active' : '' }}" id="payments_tab">
                @include('petrodirect::pumper_management.partials.payment_table', ['table_id' => 'petrodirect_payments_table', 'title' => __('petrodirect::lang.pumper_excess_shortage_payments')])
            </div>

            <div class="tab-pane {{ $active_tab == 'pumper_day_entries' ? 'active' : '' }}" id="pumper_day_entries_tab">
                @include('petrodirect::pumper_management.partials.assignment_table', ['table_id' => 'petrodirect_pumper_day_entries_table', 'title' => __('petrodirect::lang.pumper_day_entries')])
            </div>

            <div class="tab-pane {{ $active_tab == 'shift_summary' ? 'active' : '' }}" id="shift_summary_tab">
                @include('petrodirect::pumper_management.partials.assignment_table', ['table_id' => 'petrodirect_shift_summary_table', 'title' => __('petrodirect::lang.shift_summary')])
            </div>

            <div class="tab-pane {{ $active_tab == 'payment_summary' ? 'active' : '' }}" id="payment_summary_tab">
                @include('petrodirect::pumper_management.partials.payment_table', ['table_id' => 'petrodirect_payment_summary_table', 'title' => __('petrodirect::lang.payment_summary')])
            </div>

            <div class="tab-pane {{ $active_tab == 'meters_with_payments' ? 'active' : '' }}" id="meters_with_payments_tab">
                @include('petrodirect::pumper_management.partials.payment_table', ['table_id' => 'petrodirect_meters_with_payments_table', 'title' => __('petrodirect::lang.meters_with_payments')])
            </div>

            <div class="tab-pane {{ $active_tab == 'daily_pump_status' ? 'active' : '' }}" id="daily_pump_status_tab">
                @include('petrodirect::pumper_management.partials.assignment_table', ['table_id' => 'petrodirect_daily_pump_status_table', 'title' => __('petrodirect::lang.daily_pump_status')])
            </div>

            <div class="tab-pane {{ $active_tab == 'close_shift' ? 'active' : '' }}" id="close_shift_tab">
                @include('petrodirect::pumper_management.partials.assignment_table', ['table_id' => 'petrodirect_close_shift_table', 'title' => __('petrodirect::lang.close_shift')])
            </div>

            <div class="tab-pane {{ $active_tab == 'current_meter' ? 'active' : '' }}" id="current_meter_tab">
                @include('petrodirect::pumper_management.partials.generic_table', ['table_id' => 'petrodirect_current_meter_table', 'title' => __('petrodirect::lang.current_meter')])
            </div>

            <div class="tab-pane {{ $active_tab == 'unload_stock' ? 'active' : '' }}" id="unload_stock_tab">
                @include('petrodirect::pumper_management.partials.generic_table', ['table_id' => 'petrodirect_unload_stock_table', 'title' => __('petrodirect::lang.unload_stock')])
            </div>

            <div class="tab-pane {{ $active_tab == 'assignments' ? 'active' : '' }}" id="assignments_tab">
                @include('petrodirect::pumper_management.partials.assignment_table', ['table_id' => 'petrodirect_assignment_table', 'title' => __('petrodirect::lang.assign_pumps'), 'show_add' => true])
            </div>

            <div class="tab-pane {{ $active_tab == 'excess' ? 'active' : '' }}" id="excess_tab">
                @include('petrodirect::pumper_management.partials.payment_table', ['table_id' => 'petrodirect_excess_table', 'title' => __('petrodirect::lang.pay_excess_commission'), 'show_excess_add' => true])
            </div>

            <div class="tab-pane {{ $active_tab == 'shortage' ? 'active' : '' }}" id="shortage_tab">
                @include('petrodirect::pumper_management.partials.payment_table', ['table_id' => 'petrodirect_shortage_table', 'title' => __('petrodirect::lang.recover_shortage'), 'show_shortage_add' => true])
            </div>

            <div class="tab-pane {{ $active_tab == 'ledger' ? 'active' : '' }}" id="ledger_tab">
                <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">@lang('petrodirect::lang.pump_operator_ledger')</h3></div>
                    <div class="box-body">
                        <div class="row"><div class="col-md-4"><div class="form-group">
                            {!! Form::label('ledger_pump_operator_id', __('petrodirect::lang.pump_operator')) !!}
                            {!! Form::select('ledger_pump_operator_id', $operators, request('pump_operator_id'), ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%;']) !!}
                        </div></div></div>
                        <div class="table-responsive"><table class="table table-bordered table-striped" id="petrodirect_ledger_table" style="width: 100%;">
                            <thead><tr><th>@lang('petrodirect::lang.date_time')</th><th>@lang('petrodirect::lang.pump_operator')</th><th>@lang('petrodirect::lang.type')</th><th>@lang('petrodirect::lang.reference')</th><th>@lang('petrodirect::lang.debit')</th><th>@lang('petrodirect::lang.credit')</th><th>@lang('petrodirect::lang.note')</th></tr></thead>
                        </table></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    @include('petrodirect::partials.global_tab_standard')
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    if ($.fn.select2) { $('.select2').select2(); }

    function datatableUrl(tab) { return '{{ route('petrodirect.pumper-management.index') }}' + '?tab=' + tab; }

    var tables = {};

    function makeButtons() {
        return ['csv', 'excel', 'colvis', 'pdf', 'print'];
    }

    function loadOperators() {
        if (tables.operators) { return; }
        tables.operators = $('#petrodirect_pumper_table').DataTable({
            processing: true, serverSide: true, ajax: datatableUrl('operators'), aaSorting: [[1, 'asc']],
            dom: 'Bfrtip', buttons: makeButtons(), autoWidth: false,
            columnDefs: [
                { targets: 2, width: '82px', className: 'text-right pd-num pd-current-balance' },
                { targets: 3, width: '88px', className: 'text-right pd-num pd-period-balance' },
                { targets: 4, width: '72px', className: 'text-right pd-num pd-sold-qty' },
                { targets: 5, width: '92px', className: 'text-right pd-num pd-sale-amount-fuel' },
                { targets: 6, width: '86px', className: 'pd-commission-type' },
                { targets: 7, width: '76px', className: 'text-right pd-num pd-commission-rate' },
                { targets: 8, width: '82px', className: 'text-right pd-num pd-commission-amount' },
                { targets: 9, width: '78px', className: 'text-right pd-num pd-excess-amount' },
                { targets: 10, width: '82px', className: 'text-right pd-num pd-shortage-amount' }
            ],
            columns: [
                {data: 'action', name: 'action', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                // MA-002 (LA-1136): computed columns. orderable/searchable false because
                // they are calculated in PHP, not selected - DataTables cannot sort or
                // search on them at the database level and would error if asked to.
                {data: 'current_balance', name: 'current_balance', orderable: false, searchable: false},
                {data: 'balance_for_period', name: 'balance_for_period', orderable: false, searchable: false},
                {data: 'sold_fuel_qty', name: 'sold_fuel_qty', orderable: false, searchable: false},
                {data: 'sale_amount_fuel', name: 'sale_amount_fuel', orderable: false, searchable: false},
                {data: 'commission_type', name: 'commission_type'},
                {data: 'commission_rate', name: 'commission_rate', orderable: false, searchable: false},
                {data: 'commission_amount', name: 'commission_amount', orderable: false, searchable: false},
                {data: 'excess_amount', name: 'excess_amount', orderable: false, searchable: false},
                {data: 'short_amount', name: 'short_amount', orderable: false, searchable: false},
                {data: 'mobile', name: 'mobile'},
                {data: 'username', name: 'username'},
                {data: 'location_name', name: 'location_name'},
                {data: 'active', name: 'active'},
            ]
        });
    }

    function loadAssignmentTable(key, selector, tab) {
        if (tables[key]) { return; }
        tables[key] = $(selector).DataTable({
            processing: true, serverSide: true, ajax: datatableUrl(tab), aaSorting: [[0, 'desc']], dom: 'Bfrtip', buttons: makeButtons(), autoWidth: false,
            columnDefs: [
                { targets: 4, width: '88px', className: 'pd-settlement-no' },
                { targets: 5, className: 'text-right pd-num' }
            ],
            columns: [
                {data: 'date', name: 'date'},
                {data: 'pump_operator_name', name: 'pump_operator_name'},
                {data: 'pump_no', name: 'pump_no'},
                {data: 'shift_number', name: 'shift_number'},
                {data: 'location_name', name: 'location_name'},
                {data: 'status', name: 'status'},
            ]
        });
    }

    function loadPaymentTable(key, selector, tab) {
        if (tables[key]) { return; }
        tables[key] = $(selector).DataTable({
            processing: true, serverSide: true, ajax: datatableUrl(tab), aaSorting: [[0, 'desc']], dom: 'Bfrtip', buttons: makeButtons(),
            columns: [
                {data: 'date_and_time', name: 'date_and_time'},
                {data: 'pump_operator_name', name: 'pump_operator_name'},
                {data: 'payment_type', name: 'payment_type'},
                {data: 'collection_form_no', name: 'collection_form_no'},
                {data: 'settlement_no', name: 'settlement_no'},
                {data: 'payment_amount', name: 'payment_amount'},
                {data: 'note', name: 'note'},
            ]
        });
    }

    function loadGenericTable(key, selector, tab) {
        if (tables[key]) { return; }
        tables[key] = $(selector).DataTable({
            processing: true, serverSide: true, ajax: datatableUrl(tab), aaSorting: [[0, 'desc']], dom: 'Bfrtip', buttons: makeButtons(),
            columns: [
                {data: 'date', name: 'date'},
                {data: 'reference', name: 'reference'},
                {data: 'amount', name: 'amount'},
                {data: 'created_at', name: 'created_at'},
            ]
        });
    }

    function loadLedger() {
        if (tables.ledger) { return; }
        tables.ledger = $('#petrodirect_ledger_table').DataTable({
            processing: true, serverSide: true, dom: 'Bfrtip', buttons: makeButtons(),
            ajax: { url: datatableUrl('ledger'), data: function (d) { d.pump_operator_id = $('#ledger_pump_operator_id').val(); } },
            aaSorting: [[0, 'desc']],
            columns: [
                {data: 'date_and_time', name: 'date_and_time'},
                {data: 'pump_operator_name', name: 'pump_operator_name'},
                {data: 'payment_type', name: 'payment_type'},
                {data: 'collection_form_no', name: 'collection_form_no'},
                {data: 'debit', name: 'debit', searchable: false},
                {data: 'credit', name: 'credit', searchable: false},
                {data: 'note', name: 'note'},
            ]
        });
    }

    function loadTab(tab) {
        if (tab === 'operators') { loadOperators(); }
        if (tab === 'payments') { loadPaymentTable('payments', '#petrodirect_payments_table', 'payments'); }
        if (tab === 'pumper_day_entries') { loadAssignmentTable('pumper_day_entries', '#petrodirect_pumper_day_entries_table', 'pumper_day_entries'); }
        if (tab === 'shift_summary') { loadAssignmentTable('shift_summary', '#petrodirect_shift_summary_table', 'shift_summary'); }
        if (tab === 'payment_summary') { loadPaymentTable('payment_summary', '#petrodirect_payment_summary_table', 'payment_summary'); }
        if (tab === 'meters_with_payments') { loadPaymentTable('meters_with_payments', '#petrodirect_meters_with_payments_table', 'meters_with_payments'); }
        if (tab === 'daily_pump_status') { loadAssignmentTable('daily_pump_status', '#petrodirect_daily_pump_status_table', 'daily_pump_status'); }
        if (tab === 'close_shift') { loadAssignmentTable('close_shift', '#petrodirect_close_shift_table', 'close_shift'); }
        if (tab === 'current_meter') { loadGenericTable('current_meter', '#petrodirect_current_meter_table', 'current_meter'); }
        if (tab === 'unload_stock') { loadGenericTable('unload_stock', '#petrodirect_unload_stock_table', 'unload_stock'); }
        if (tab === 'assignments') { loadAssignmentTable('assignments', '#petrodirect_assignment_table', 'assignments'); }
        if (tab === 'excess') { loadPaymentTable('excess', '#petrodirect_excess_table', 'excess'); }
        if (tab === 'shortage') { loadPaymentTable('shortage', '#petrodirect_shortage_table', 'shortage'); }
        if (tab === 'ledger') { loadLedger(); }
    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) { loadTab($(e.target).data('tab')); });
    $('#ledger_pump_operator_id').on('change', function () { if (tables.ledger) { tables.ledger.ajax.reload(); } });

    loadTab('{{ $active_tab ?: 'operators' }}');

    $(document).on('click', '.delete-petrodirect-pumper', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        swal({ title: LANG.sure, text: LANG.confirm_delete_user, icon: 'warning', buttons: true, dangerMode: true }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({ method: 'DELETE', url: href, dataType: 'json', data: {_token: '{{ csrf_token() }}'}, success: function(result) {
                    if (result.success) { toastr.success(result.msg); if (tables.operators) { tables.operators.ajax.reload(); } }
                    else { toastr.error(result.msg || 'Unable to delete.'); }
                }});
            }
        });
    });
});
</script>
<style>
.petrodirect-pumper-tabs .tab-content { background: #fff; padding: 15px; }
.petrodirect-actions-dropdown .dropdown-menu { z-index: 9999; }
.petrodirect-pumper-management-page .table-responsive { overflow-x: auto; }

/* Pump Operators table: compact, page-scoped typography/column sizing only. */
#petrodirect_pumper_table thead th {
    font-size: calc(100% - 2pt);
    line-height: 1.12;
    vertical-align: middle;
    white-space: normal;
}
#petrodirect_pumper_table tbody td {
    font-size: calc(100% - .5pt);
    vertical-align: middle;
}
#petrodirect_pumper_table th:nth-child(3),
#petrodirect_pumper_table td:nth-child(3) { width: 82px !important; max-width: 82px; }
#petrodirect_pumper_table th:nth-child(4),
#petrodirect_pumper_table td:nth-child(4) { width: 88px !important; max-width: 88px; }
#petrodirect_pumper_table th:nth-child(5),
#petrodirect_pumper_table td:nth-child(5) { width: 72px !important; max-width: 72px; }
#petrodirect_pumper_table th:nth-child(6),
#petrodirect_pumper_table td:nth-child(6) { width: 92px !important; max-width: 92px; }
#petrodirect_pumper_table th:nth-child(7),
#petrodirect_pumper_table td:nth-child(7) { width: 86px !important; max-width: 86px; }
#petrodirect_pumper_table th:nth-child(8),
#petrodirect_pumper_table td:nth-child(8) { width: 76px !important; max-width: 76px; }
#petrodirect_pumper_table th:nth-child(9),
#petrodirect_pumper_table td:nth-child(9) { width: 82px !important; max-width: 82px; }
#petrodirect_pumper_table th:nth-child(10),
#petrodirect_pumper_table td:nth-child(10) { width: 78px !important; max-width: 78px; }
#petrodirect_pumper_table th:nth-child(11),
#petrodirect_pumper_table td:nth-child(11) { width: 82px !important; max-width: 82px; }

/* All numeric quantity/rate/amount values are right aligned. */
#petrodirect_pumper_table tbody td:nth-child(3),
#petrodirect_pumper_table tbody td:nth-child(4),
#petrodirect_pumper_table tbody td:nth-child(5),
#petrodirect_pumper_table tbody td:nth-child(6),
#petrodirect_pumper_table tbody td:nth-child(8),
#petrodirect_pumper_table tbody td:nth-child(9),
#petrodirect_pumper_table tbody td:nth-child(10),
#petrodirect_pumper_table tbody td:nth-child(11) {
    text-align: right !important;
}

/* Payment-related tabs on Pumper Management: compact Settlement No heading/column. */
.petrodirect-pumper-management-page table[id^="petrodirect_"] thead th {
    font-size: calc(100% - 2pt);
    line-height: 1.12;
    vertical-align: middle;
}
.petrodirect-pumper-management-page .pd-settlement-no {
    width: 88px !important;
    max-width: 88px;
    white-space: normal !important;
}
</style>
@stop
