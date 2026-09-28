@extends('distribution::layouts.app')

@section('title', 'VAT – Dis. Invoices')

@section('content')

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#list_vat_dis_invoice" data-toggle="tab" aria-expanded="true">
                                <i class="fa fa-list" aria-hidden="true"></i> List VAT Dis invoice
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('distribution.vat-invoices.create') }}">
                                <i class="fa fa-plus" aria-hidden="true"></i> Add VAT Dis invoice
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="list_vat_dis_invoice">
                            @component('distribution::components.filters', ['title' => __('report.filters')])
                                <form method="GET" action="{{ route('distribution.vat-invoices.index') }}">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label>Invoice No</label>
                                            {!! Form::select('invoice_no', $invoiceNos, request('invoice_no'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-3">
                                            <label>Customer Name / Contact Number</label>
                                            {!! Form::select('customer_lookup', $customers, request('customer_lookup'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            <label>Location</label>
                                            {!! Form::select('location', $locations, request('location'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            <label>Added By</label>
                                            {!! Form::select('added_by', $users, request('added_by'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            <label>Shipping Status</label>
                                            {!! Form::select('shipping_status', ['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'], request('shipping_status'), ['class' => 'form-control input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-1">
                                            <label>Payment</label>
                                            {!! Form::select('payment_status', ['paid' => 'Paid', 'due' => 'Due'], request('payment_status'), ['class' => 'form-control input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            <label>Pay Method</label>
                                            {!! Form::select('payment_method', ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'credit' => 'Credit'], request('payment_method'), ['class' => 'form-control input-sm', 'placeholder' => 'All']) !!}
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                {!! Form::label('date_range', __('report.date_range') . ':') !!}
                                                {!! Form::text('date_range', !empty(request('date_from')) && !empty(request('date_to')) ? request('date_from') . ' ~ ' . request('date_to') : null, [
                                                    'class' => 'form-control input-sm',
                                                    'id' => 'invoice_date_range',
                                                    'readonly',
                                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                                ]) !!}
                                                <input type="hidden" id="invoice_start_date" name="date_from" value="{{ request('date_from') }}">
                                                <input type="hidden" id="invoice_end_date" name="date_to" value="{{ request('date_to') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                                <a href="{{ route('distribution.vat-invoices.index') }}" class="btn btn-default btn-sm">Reset</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            @endcomponent

                            @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'VAT Dis. Invoice List'])
                                @slot('tool')
                                    <div class="box-tools">
                                        <a href="{{ route('distribution.vat-invoices.create') }}" class="btn btn-primary pull-right">
                                            <i class="fa fa-plus"></i> @lang('messages.add')
                                        </a>
                                    </div>
                                @endslot
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="vat_dis_invoice_table" style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Invoice No</th>
                                                <th>Delivery Date</th>
                                                <th>Customer Name & Contact Number</th>
                                                <th>Location</th>
                                                <th>Grand Total</th>
                                                <th>Paid Amount</th>
                                                <th>Balance Due</th>
                                                <th>Payment Method</th>
                                                <th>Shipping Status</th>
                                                <th>Payment Status</th>
                                                <th>Added User</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoices as $inv)
                                                <tr>
                                                    <td>
                                                        @if($inv->date)
                                                            @php
                                                                try {
                                                                    $dateStr = (string)$inv->date;
                                                                    if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}):(\d{2})(?::(\d{2}))?$/', $dateStr, $matches)) {
                                                                        if (!isset($matches[4]) || $matches[4] === '') {
                                                                            $dateStr = $matches[1] . ' ' . $matches[2] . ':' . $matches[3] . ':00';
                                                                        }
                                                                        $dateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateStr, 'UTC');
                                                                        echo $dateTime->format('Y-m-d g:i A');
                                                                    } else {
                                                                        $dateTime = \Carbon\Carbon::parse($inv->date)->setTimezone('UTC');
                                                                        echo $dateTime->format('Y-m-d g:i A');
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    echo $inv->date;
                                                                }
                                                            @endphp
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $inv->invoice_no }}</td>
                                                    <td>{{ $inv->delivery_date ?? '-' }}</td>
                                                    <td>{{ $inv->customer_name ?? '-' }}{{ !empty($inv->customer_contact) ? ' / ' . $inv->customer_contact : '' }}</td>
                                                    <td>{{ $inv->customer_address ?? '-' }}</td>
                                                    <td>{{ number_format($inv->grand_total, 2) }}</td>
                                                    <td>{{ number_format(($inv->payment_total ?? 0), 2) }}</td>
                                                    <td>{{ number_format(max(0, ($inv->grand_total - ($inv->payment_total ?? 0))), 2) }}</td>
                                                    <td>
                                                        @php
                                                            $methods = [];
                                                            if(($inv->payment_cash ?? 0) > 0) $methods[] = 'Cash';
                                                            if(($inv->payment_card ?? 0) > 0) $methods[] = 'Card';
                                                            if(($inv->payment_cheque ?? 0) > 0) $methods[] = 'Cheque';
                                                            if(($inv->payment_credit ?? 0) > 0) $methods[] = 'Credit';
                                                        @endphp
                                                        {{ count($methods) ? implode(', ', $methods) : '-' }}
                                                    </td>
                                                    <td>
                                                        <form method="POST" action="{{ route('distribution.vat-invoices.shipping_status', $inv->id) }}" class="inv-shipping-status-form">
                                                            @csrf
                                                            <div style="display:flex; gap:4px;">
                                                                <select name="shipping_status" class="form-control input-sm">
                                                                    @foreach (['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $status_key => $status_label)
                                                                        <option value="{{ $status_key }}" {{ ($inv->shipping_status ?? 'ordered') == $status_key ? 'selected' : '' }}>{{ $status_label }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <button type="submit" class="btn btn-xs btn-default">Save</button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        @if (($inv->grand_total - ($inv->payment_total ?? 0)) > 0.009)
                                                            <span class="label label-warning">Due</span>
                                                        @else
                                                            <span class="label label-success">Paid</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ optional($inv->addedUser)->username ?? optional($inv->addedUser)->first_name ?? '-' }}</td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                                Action <span class="caret"></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                                                <li><a href="{{ route('distribution.vat-invoices.show', $inv->id) }}"><i class="fa fa-eye"></i> View</a></li>
                                                                @if (($inv->grand_total - ($inv->payment_total ?? 0)) > 0.009)
                                                                    <li><a href="{{ url('customer-payments/create?contact_id=' . $inv->customer_id) }}"><i class="fa fa-money"></i> Pay Due Amount</a></li>
                                                                @endif
                                                                <li><a href="{{ url('contacts/' . $inv->customer_id . '?view=ledger') }}"><i class="fa fa-list"></i> View Payment</a></li>
                                                                @if(!empty($inv->linked_transaction_id))
                                                                    <li><a href="{{ url('sell-return/add/' . $inv->linked_transaction_id) }}"><i class="fa fa-undo"></i> Sell Return</a></li>
                                                                @endif
                                                                <li><a href="#" class="show-notes-btn"
                                                                    data-invoice-note="{{ e($inv->invoice_note ?? '') }}"
                                                                    data-shipping-note="{{ e($inv->shipping_note ?? '') }}"
                                                                    data-shipping-details="{{ e($inv->shipping_details ?? '') }}"
                                                                    data-activity-created="{{ e($inv->activity_payloads['created'] ?? '-') }}"
                                                                    data-activity-changed="{{ e($inv->activity_payloads['changed'] ?? '-') }}"
                                                                    data-activity-deleted="{{ e($inv->activity_payloads['deleted'] ?? '-') }}"
                                                                    data-created-at="{{ optional($inv->created_at)->format('Y-m-d H:i:s') }}"
                                                                    data-updated-at="{{ optional($inv->updated_at)->format('Y-m-d H:i:s') }}"
                                                                    data-added-user="{{ e(optional($inv->addedUser)->username ?? optional($inv->addedUser)->first_name ?? '-') }}"
                                                                    data-updated-user="{{ e(optional($inv->updatedUser)->username ?? optional($inv->updatedUser)->first_name ?? '-') }}"
                                                                    data-date="{{ \Carbon\Carbon::parse($inv->date)->format('Y-m-d H:i') }}"
                                                                    data-invoice-no="{{ e($inv->invoice_no ?? '-') }}"
                                                                    data-delivery-date="{{ e($inv->delivery_date ?: '-') }}"
                                                                    data-customer="{{ e($inv->customer_name ?? '-') }}"
                                                                    data-customer-contact="{{ e($inv->customer_contact ?: '-') }}"
                                                                    data-location="{{ e($inv->customer_address ?: '-') }}"
                                                                    data-total-amount="{{ number_format($inv->grand_total ?? 0, 2, '.', '') }}"
                                                                    data-total-paid="{{ number_format(($inv->payment_total ?? 0), 2, '.', '') }}"
                                                                    data-balance-due="{{ number_format(max(0, ($inv->grand_total - ($inv->payment_total ?? 0))), 2, '.', '') }}"
                                                                    data-payment-status="{{ (($inv->grand_total - ($inv->payment_total ?? 0)) > 0.009) ? 'Due' : 'Paid' }}"
                                                                    data-payment-method="{{ e(count($methods) ? implode(', ', $methods) : '-') }}"
                                                                    data-shipping-status="{{ e(ucfirst($inv->shipping_status ?? 'ordered')) }}"><i class="fa fa-book"></i> Notes</a></li>
                                                                @if (($inv->payment_total ?? 0) > 0)
                                                                    <li><a href="#" class="show-payment-btn"
                                                                        data-cash="{{ number_format(($inv->payment_cash ?? 0), 2, '.', '') }}"
                                                                        data-card="{{ number_format(($inv->payment_card ?? 0), 2, '.', '') }}"
                                                                        data-cheque="{{ number_format(($inv->payment_cheque ?? 0), 2, '.', '') }}"
                                                                        data-credit="{{ number_format(($inv->payment_credit ?? 0), 2, '.', '') }}"
                                                                        data-total="{{ number_format(($inv->payment_total ?? 0), 2, '.', '') }}"><i class="fa fa-money"></i> Payment Details</a></li>
                                                                @endif
                                                                @if(!empty($inv->linked_payment_id))
                                                                    <li><a href="#" class="edit_payment"
                                                                        data-href="{{ action('CustomerPaymentController@edit', [$inv->linked_payment_id]) }}"
                                                                        data-update-href="{{ action('CustomerPaymentController@update', [$inv->linked_payment_id]) }}"><i class="fa fa-edit"></i> Edit Payment</a></li>
                                                                @endif
                                                                <li><a href="{{ route('distribution.vat-invoices.edit', $inv->id) }}"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>
                                                                <li><a class="print-vat-2026" href="#" data-id="{{ $inv->id }}"><i class="fa fa-print"></i> Print – VAT Print 2026</a></li>
                                                                <li><a class="print-full-vat" href="#" data-id="{{ $inv->id }}"><i class="fa fa-print"></i> Print Full VAT Invoice</a></li>
                                                                <li><a href="{{ route('distribution.vat-invoices.duplicate', $inv->id) }}"><i class="fa fa-copy"></i> Duplicate</a></li>
                                                                <li>
                                                                    <form method="POST" action="{{ route('distribution.vat-invoices.destroy', $inv->id) }}" onsubmit="return confirm('Delete this invoice?');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" style="background:none;border:none;padding:3px 20px;color:#333;width:100%;text-align:left;"><i class="fa fa-trash"></i> Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-right">
                                    {{ $invoices->appends(request()->query())->links() }}
                                </div>
                            @endcomponent
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Notes -->
    <div class="modal fade" id="invoiceNotesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Invoice Notes</h4>
                </div>
                <div class="modal-body">
                    <p><strong>VAT Invoice Note:</strong> <span id="modal_invoice_note">-</span></p>
                    <p><strong>Shipping Note:</strong> <span id="modal_shipping_note">-</span></p>
                    <p><strong>Shipping Details:</strong> <span id="modal_shipping_details">-</span></p>
                    <hr>
                    <button type="button" class="btn btn-xs btn-default" id="show_changed_details_btn">Changed Details</button>
                    <div id="modal_changed_details_wrapper" class="changed-details-sections">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Created Details</strong></div>
                            <div class="panel-body" style="padding:8px 12px;">
                                <ul id="modal_activity_created_list" style="margin:0; padding-left:18px;">
                                    <li id="modal_activity_created">Created: -</li>
                                </ul>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Changed Details</strong></div>
                            <div class="panel-body" style="padding:8px 12px;">
                                <ul id="modal_activity_changed_list" style="margin:0; padding-left:18px;">
                                    <li id="modal_activity_changed">Changed: -</li>
                                </ul>
                            </div>
                        </div>
                        <div class="panel panel-default" style="margin-bottom:0;">
                            <div class="panel-heading"><strong>Deleted Details</strong></div>
                            <div class="panel-body" style="padding:8px 12px;">
                                <ul id="modal_activity_deleted_list" style="margin:0; padding-left:18px;">
                                    <li id="modal_activity_deleted">Deleted: -</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Payment Details -->
    <div class="modal fade" id="invoicePaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Payment Details</h4>
                </div>
                <div class="modal-body">
                    <p><strong>Cash:</strong> <span id="pay_cash">0.00</span></p>
                    <p><strong>Card:</strong> <span id="pay_card">0.00</span></p>
                    <p><strong>Cheque:</strong> <span id="pay_cheque">0.00</span></p>
                    <p><strong>Credit:</strong> <span id="pay_credit">0.00</span></p>
                    <hr>
                    <p><strong>Total:</strong> <span id="pay_total">0.00</span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Payment -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Edit Payment</h4>
                </div>
                <form id="editPaymentForm">
                    @csrf
                    <input type="hidden" id="edit_payment_id" name="payment_id">
                    <input type="hidden" id="edit_payment_update_url">
                    <input type="hidden" id="edit_account_id" name="account_id">
                    <input type="hidden" id="edit_location_id" name="location_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Payment Reference No</label>
                            <input type="text" class="form-control" id="edit_payment_ref_no" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="form-control" id="edit_paid_on" name="paid_on" required>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select class="form-control" id="edit_method" name="method">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div id="edit_payment_method_details">
                            <div class="form-group edit-payment-card-only" style="display:none;">
                                <label>Card Number</label>
                                <input type="text" class="form-control" id="edit_card_number" name="card_number">
                            </div>
                            <div class="form-group edit-payment-cheque-only" style="display:none;">
                                <label>Cheque Number</label>
                                <input type="text" class="form-control" id="edit_cheque_number" name="cheque_number">
                            </div>
                            <div class="form-group edit-payment-cheque-only" style="display:none;">
                                <label>Bank Name</label>
                                <input type="text" class="form-control" id="edit_bank_name" name="bank_name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Payment Note</label>
                            <textarea class="form-control" id="edit_note" name="note" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            function renderActivityList(selector, text, prefix) {
                var $list = $(selector);
                var value = text || (prefix + ': -');
                var normalized = String(value)
                    .replace(/\s+//g, ' ')
                    .replace(/\s+\|\s+/g, '||')
                    .replace(/<br\s*\/?>/gi, '||');
                var items = normalized.split('||').map(function(item) {
                    return $.trim(item);
                }).filter(function(item) {
                    return item.length > 0;
                });

                if (!items.length) {
                    items = [prefix + ': -'];
                }

                $list.empty();
                items.forEach(function(item) {
                    $('<li>').text(item).appendTo($list);
                });
            }

            function toggleEditPaymentMethodFields() {
                var method = ($('#edit_method').val() || '').toLowerCase();
                $('.edit-payment-card-only').toggle(method === 'card');
                $('.edit-payment-cheque-only').toggle(method === 'cheque' || method === 'bank_transfer');
            }

            if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#vat_dis_invoice_table')) {
                $('#vat_dis_invoice_table').DataTable({
                    dom: 'Bfrtip',
                    buttons: ['csv', 'excel', 'pdf', 'print', 'colvis']
                });
            }

            if ($('#invoice_date_range').length === 1 && typeof $.fn.daterangepicker !== 'undefined') {
                $('#invoice_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#invoice_date_range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    $('#invoice_start_date').val(start.format('YYYY-MM-DD'));
                    $('#invoice_end_date').val(end.format('YYYY-MM-DD'));
                });

                $('#invoice_date_range').on('cancel.daterangepicker', function() {
                    $('#invoice_date_range').val('');
                    $('#invoice_start_date').val('');
                    $('#invoice_end_date').val('');
                });

                if ($('#invoice_start_date').val() && $('#invoice_end_date').val()) {
                    $('#invoice_date_range').data('daterangepicker').setStartDate(moment($('#invoice_start_date').val()));
                    $('#invoice_date_range').data('daterangepicker').setEndDate(moment($('#invoice_end_date').val()));
                }
            }

            $(document).on('click', '.show-notes-btn', function() {
                $('#modal_invoice_note').text($(this).data('invoice-note') || '-');
                $('#modal_shipping_note').text($(this).data('shipping-note') || '-');
                $('#modal_shipping_details').text($(this).data('shipping-details') || '-');
                renderActivityList('#modal_activity_created_list', $(this).data('activity-created'), 'Created');
                renderActivityList('#modal_activity_changed_list', $(this).data('activity-changed'), 'Changed');
                renderActivityList('#modal_activity_deleted_list', $(this).data('activity-deleted'), 'Deleted');
                $('#modal_changed_details_wrapper').hide();
                $('#invoiceNotesModal').modal('show');
            });

            $(document).on('click', '#show_changed_details_btn', function() {
                $('#modal_changed_details_wrapper').toggle();
            });

            $(document).on('click', '.show-payment-btn', function() {
                $('#pay_cash').text($(this).data('cash') || '0.00');
                $('#pay_card').text($(this).data('card') || '0.00');
                $('#pay_cheque').text($(this).data('cheque') || '0.00');
                $('#pay_credit').text($(this).data('credit') || '0.00');
                $('#pay_total').text($(this).data('total') || '0.00');
                $('#invoicePaymentModal').modal('show');
            });

            $(document).on('click', '.edit_payment', function(e) {
                e.preventDefault();

                var editHref = $(this).data('href');
                var updateHref = $(this).data('update-href');

                $.ajax({
                    method: 'GET',
                    url: editHref,
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            toastr.error(result.msg || 'Error loading payment details.');
                            return;
                        }

                        var p = result.payment || {};
                        $('#edit_payment_id').val(p.id || '');
                        $('#edit_payment_update_url').val(updateHref || '');
                        $('#edit_paid_on').val(p.paid_on ? p.paid_on.substring(0, 10) : '');
                        $('#edit_amount').val(p.amount || '');
                        $('#edit_method').val(p.method || 'cash');
                        $('#edit_payment_ref_no').val(p.payment_ref_no || '');
                        $('#edit_card_number').val(p.card_number || '');
                        $('#edit_cheque_number').val(p.cheque_number || '');
                        $('#edit_bank_name').val(p.bank_name || '');
                        $('#edit_note').val(p.note || '');
                        $('#edit_account_id').val(p.account_id || '');
                        $('#edit_location_id').val(p.location_id || '');
                        toggleEditPaymentMethodFields();
                        $('#editPaymentModal').modal('show');
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error loading payment details.');
                    }
                });
            });

            $(document).on('submit', '#editPaymentForm', function(e) {
                e.preventDefault();

                var url = $('#edit_payment_update_url').val();
                $.ajax({
                    method: 'PUT',
                    url: url,
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg || 'Payment updated successfully.');
                            $('#editPaymentModal').modal('hide');
                            window.location.reload();
                        } else {
                            toastr.error(result.msg || 'Error saving payment.');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving payment.');
                    }
                });
            });

            $(document).on('change', '#edit_method', toggleEditPaymentMethodFields);

            $(document).on('submit', '.inv-shipping-status-form', function(e) {
                var selectedStatus = $(this).find('select[name="shipping_status"] option:selected').text() || 'selected';
                if (!confirm('Change shipping status to "' + selectedStatus + '"?')) {
                    e.preventDefault();
                }
            });

            $(document).on('click', '.print-vat-2026', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url = "{{ route('distribution.vat-invoices.show', ':id') }}".replace(':id', id) + '?print=vat_2026';
                var win = window.open(url, '_blank');
                win.focus();
            });

            $(document).on('click', '.print-full-vat', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url = "{{ route('distribution.vat-invoices.show', ':id') }}".replace(':id', id) + '?print=full_vat';
                var win = window.open(url, '_blank');
                win.focus();
            });
        });

        @if(is_array(session('status')) && !empty(session('status')['print_url']))
            let href = "{{ session('status')['print_url'] }}";
            $.ajax({
                method: 'get',
                url: href,
                data: {  },
                contentType: 'html',
                success: function(result) {
                    html = result;
                    var w = window.open('', '_self');
                    $(w.document.body).html(html);
                    w.print();
                    w.close();
                    location.reload();
                },
            });
        @endif
    </script>
@endsection

@section('css')
    <style>
        .changed-details-sections {
            display: none;
            margin-top: 8px;
        }
    </style>
@endsection
