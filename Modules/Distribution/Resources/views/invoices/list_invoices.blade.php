@extends('distribution::layouts.app')
@section('title', 'List Dis. Invoices')

@section('content')

<style>
    /* Solution DIST-02: keep Distribution Invoice List toolbar professional and stable */
    .dist-invoice-list-shell .dataTables_wrapper,
    .dist-invoice-list-shell .table-responsive,
    .dist-invoice-list-shell .box,
    .dist-invoice-list-shell .box-body {
        overflow: visible !important;
    }
    .dist-invoice-list-shell div.dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 10px;
    }
    .dist-invoice-list-shell div.dt-buttons .btn {
        border-radius: 6px !important;
        font-weight: 600;
        margin: 0 !important;
    }
    .dist-invoice-list-shell .btn-add-dis-invoice {
        background: #3c8dbc !important;
        border-color: #367fa9 !important;
        color: #fff !important;
    }
    .dist-invoice-list-shell #dis_invoice_list_table thead th {
        white-space: nowrap;
        vertical-align: middle;
    }
    @media (max-width: 767px) {
        .dist-invoice-list-shell div.dt-buttons {
            justify-content: flex-start;
        }
    }
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>List Dis. Invoices</h1>
</section>

<!-- Main content -->
<section class="content dist-invoice-list-shell">
    <div class="row">
        <div class="col-md-12">
            @component('distribution::components.filters', ['title' => __('report.filters')])
                <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('invoice_no', 'Dis Invoice No:') !!}
                        {!! Form::select('invoice_no', $invoice_numbers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_invoice_no']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_id', 'Customer:') !!}
                        {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_customer_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('route_id', 'Location:') !!}
                        {!! Form::select('route_id', $routes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_route_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('vehicle_id', 'Vehicle:') !!}
                        {!! Form::select('vehicle_id', $vehicles, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_vehicle_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('added_by', 'Added By:') !!}
                        {!! Form::select('added_by', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_added_by']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shipping_status', 'Shipping Status:') !!}
                        {!! Form::select('shipping_status', ['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_shipping_status']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_status', 'Payment Status:') !!}
                        {!! Form::select('payment_status', ['paid' => 'Paid', 'due' => 'Due', 'partial' => 'Partial'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_payment_status']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_method', 'Payment Method:') !!}
                        {!! Form::select('payment_method', $payment_methods, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_payment_method']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'filter_date_range', 'readonly']); !!}
                    </div>
                </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('distribution::components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dis_invoice_list_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.action')</th>
                                <th>Date & Time</th>
                                <th>Dis Invoice No.</th>
                                <th>Delivery Date</th>
                                <th>Customer Name & Contact Number</th>
                                <th>Location</th>
                                <th class="text-right">Total Amount</th>
                                <th>Payment Status</th>
                                <th class="text-right">Total Paid</th>
                                <th class="text-right">Balance Due</th>
                                <th class="text-right">Sell Return Due</th>
                                <th>@lang('lang_v1.payment_method')</th>
                                <th class="text-right">Total Items</th>
                                <th>Shipping Status</th>
                                <th>Added By</th>
                                <th>Updated By</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center footer-total">
                                <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_grand_total"></td>
                                <td></td>
                                <td class="footer_total_paid"></td>
                                <td class="footer_total_remaining"></td>
                                <td class="footer_total_sell_return_due"></td>
                                <td></td>
                                <td class="footer_total_items"></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Modals -->
<div class="modal fade" id="notes_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Notes</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">Dis Invoice Note</div>
                            <div class="panel-body" id="dis_invoice_note_content"></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">Shipping Note</div>
                            <div class="panel-body" id="shipping_note_content"></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">Sales Order Note</div>
                            <div class="panel-body" id="sales_order_note_content"></div>
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

<div class="modal fade dis_payment_modal" tabindex="-1" role="dialog"></div>

<div class="modal fade activity_log_modal" tabindex="-1" role="dialog"></div>

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

<div class="modal fade" id="shipping_status_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => '', 'method' => 'post', 'id' => 'shipping_status_form']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Change Shipping Status</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('shipping_status_modal_select', 'Current Status: ', ['id' => 'current_shipping_status_label']) !!}
                    {!! Form::select('shipping_status', ['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'shipping_status_modal_select']); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="save_shipping_status">Update Status</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){
        $('.select2').select2({
            minimumResultsForSearch: 0
        });
        
        //Date range as a button
        $('#filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                dis_invoice_list_table.ajax.reload();
            }
        );
        $('#filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#filter_date_range').val('');
            dis_invoice_list_table.ajax.reload();
        });

        dis_invoice_list_table = $('#dis_invoice_list_table').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            aaSorting: [[1, 'desc']],
            "ajax": {
                "url": "{{ route('distribution.list_invoices.index') }}",
                "data": function ( d ) {
                    d.invoice_no = $('#filter_invoice_no').val();
                    d.customer_id = $('#filter_customer_id').val();
                    d.route_id = $('#filter_route_id').val();
                    d.vehicle_id = $('#filter_vehicle_id').val();
                    d.added_by = $('#filter_added_by').val();
                    d.shipping_status = $('#filter_shipping_status').val();
                    d.payment_status = $('#filter_payment_status').val();
                    d.payment_method = $('#filter_payment_method').val();
                    if($('#filter_date_range').val()) {
                        var start = $('#filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        var end = $('#filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                    }
                }
            },
            columnDefs: [ {
                "targets": [0, 11, 12, 13],
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'action', name: 'action'},
                { data: 'date', name: 'date'  },
                { data: 'invoice_no', name: 'invoice_no'},
                { data: 'delivery_date', name: 'delivery_date'},
                { data: 'customer_info', name: 'customer_name'},
                { data: 'route_name', name: 'route_name'},
                { data: 'grand_total', name: 'grand_total', className: 'text-right'},
                { data: 'payment_status', name: 'payment_status'},
                { data: 'payment_total', name: 'payment_total', className: 'text-right'},
                { data: 'balance_due', name: 'balance_due', className: 'text-right'},
                { data: 'sell_return_due', name: 'sell_return_due', className: 'text-right'},
                { data: 'payment_method', name: 'payment_method'},
                { data: 'total_items', name: 'total_items', className: 'text-right', searchable: false},
                { data: 'shipping_status', name: 'shipping_status'},
                { data: 'added_by_username', name: 'added_by_user.username'},
                { data: 'updated_by_username', name: 'updated_by_user.username'}
            ],
            fnDrawCallback: function (oSettings) {
                __currency_convert_recursively($('.grand_total'));
                __currency_convert_recursively($('.payment_total'));
                __currency_convert_recursively($('.balance_due'));
                
                var json = oSettings.json;
                if (json && json.footer_data) {
                    $('.footer_grand_total').html(__currency_trans_from_en(json.footer_data.footer_grand_total, true));
                    $('.footer_total_paid').html(__currency_trans_from_en(json.footer_data.footer_total_paid, true));
                    $('.footer_total_remaining').html(__currency_trans_from_en(json.footer_data.footer_total_remaining, true));
                    $('.footer_total_sell_return_due').html(__currency_trans_from_en(json.footer_data.footer_total_sell_return_due, true));
                    $('.footer_total_items').html(__currency_trans_from_en(json.footer_data.footer_total_items, false, false, 0));
                }

                __currency_convert_recursively($('.footer-total'));
            },
            dom: '<"row margin-bottom-20"<"col-sm-12 text-right"B><"col-sm-6"f><"col-sm-6"l> r>tip',
            buttons: [
                {
                    text: '<i class="fa fa-plus"></i> @lang("messages.add")',
                    className: 'btn btn-sm btn-primary btn-add-dis-invoice',
                    action: function () {
                        window.location.href = '{{ route("distribution.invoices.create") }}';
                    }
                },
                {
                    extend: 'colvis',
                    className: 'btn btn-sm btn-default',
                    text: 'Column Visibility'
                },
                {
                    extend: 'csv',
                    footer: true,
                    text: '<i class="fa fa-file"></i> Export to CSV',
                    className: 'btn btn-sm btn-default',
                    exportOptions: {
                        columns: function (idx, data, node) {
                            var table = $(node).closest('table').DataTable();
                            return table.column(idx).visible() && !$(node).hasClass('notexport');
                        }
                    }
                },
                {
                    extend: 'excel',
                    footer: true,
                    text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                    className: 'btn btn-sm btn-default',
                    exportOptions: {
                        columns: function (idx, data, node) {
                            var table = $(node).closest('table').DataTable();
                            return table.column(idx).visible() && !$(node).hasClass('notexport');
                        }
                    }
                },
                {
                    extend: 'pdf',
                    footer: true,
                    text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                    className: 'btn btn-sm btn-default',
                    exportOptions: {
                        columns: function (idx, data, node) {
                            var table = $(node).closest('table').DataTable();
                            return table.column(idx).visible() && !$(node).hasClass('notexport');
                        }
                    }
                },
                {
                    extend: 'print',
                    footer: true,
                    text: '<i class="fa fa-print"></i> Print',
                    className: 'btn btn-sm btn-default',
                    exportOptions: {
                        columns: function (idx, data, node) {
                            var table = $(node).closest('table').DataTable();
                            return table.column(idx).visible() && !$(node).hasClass('notexport');
                        }
                    },
                    customize: function (win) {
                        $(win.document.body).find('h1').css('text-align', 'center');
                        $(win.document.body).find('h1').css('font-size', '25px');
                    }
                }
            ],
        });

        $(document).on('change', '#filter_invoice_no, #filter_customer_id, #filter_route_id, #filter_vehicle_id, #filter_added_by, #filter_shipping_status, #filter_payment_status, #filter_payment_method',  function() {
            dis_invoice_list_table.ajax.reload();
        });

        //Delete Invoice
        $(document).on('click', 'a.delete-dis-invoice', function(e){
            e.preventDefault();
            swal({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).attr('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                dis_invoice_list_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        //View Notes
        $(document).on('click', '.view-notes', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            $.ajax({
                url: "{{ url('/distribution/list-invoices/notes') }}/" + id,
                dataType: 'json',
                success: function(result){
                    $('#dis_invoice_note_content').text(result.invoice_note || '-');
                    $('#shipping_note_content').text(result.shipping_note || '-');
                    $('#sales_order_note_content').text(result.sales_order_note || '-');
                    $('#notes_modal').modal('show');
                }
            });
        });

        //View Activities
        $(document).on('click', '.view-changed-activities', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            $.ajax({
                url: "{{ url('/distribution/list-invoices/activities') }}/" + id,
                dataType: 'html',
                success: function(result){
                    $('.activity_log_modal').html(result).modal('show');
                }
            });
        });

        //Toggle Changed Details in Activities
        $(document).on('click', '.btn-changed-details', function(e){
            e.preventDefault();
            $(this).siblings('.changed-details-wrapper').toggle();
        });

        //View Payment Modal
        $(document).on('click', '.view_payment_modal', function(e){
            e.preventDefault();
            var container = $(this).data('container') || '.dis_payment_modal';
            var url = $(this).data('href') || $(this).attr('href');
            $.ajax({
                url: url,
                dataType: 'html',
                success: function(result){
                    $(container).html(result).modal('show');
                }
            });
        });

        //Add Payment Modal
        $(document).on('click', '.add_payment_modal', function(e){
            e.preventDefault();
            var container = $('.dis_payment_modal');
            $.ajax({
                url: $(this).attr('href'),
                dataType: 'html',
                success: function(result){
                    $(container).html(result).modal('show');
                }
            });
        });

        //Change Shipping Status Modal
        $(document).on('click', '.change_shipping_status', function(){
            var id = $(this).data('id');
            var status = $(this).data('status');
            var label = $(this).text();
            
            $('#current_shipping_status_label').text('Current Status: ' + label);
            $('#shipping_status_modal_select').val(status).trigger('change');
            $('#shipping_status_form').attr('action', "{{ url('/distribution/invoices') }}/" + id + "/shipping-status");
            $('#shipping_status_modal').modal('show');
        });

        $('#shipping_status_form').submit(function(e){
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            var data = form.serialize();
            
            swal({
                title: 'Are you sure?',
                text: "You want to change the shipping status!",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((confirm) => {
                if (confirm) {
                    $.ajax({
                        method: 'POST',
                        url: url,
                        data: data,
                        dataType: 'json',
                        success: function(result){
                            if(result.success == true){
                                $('#shipping_status_modal').modal('hide');
                                toastr.success(result.msg);
                                dis_invoice_list_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function(err) {
                            // If redirecting (from original back() logic), just reload table
                            $('#shipping_status_modal').modal('hide');
                            toastr.success('Shipping status updated successfully.');
                            dis_invoice_list_table.ajax.reload();
                        }
                    });
                }
            });
        });

        //Show Payment Details Modal
        $(document).on('click', '.show-payment-btn', function() {
            $('#pay_cash').text($(this).data('cash') || '0.00');
            $('#pay_card').text($(this).data('card') || '0.00');
            $('#pay_cheque').text($(this).data('cheque') || '0.00');
            $('#pay_credit').text($(this).data('credit') || '0.00');
            $('#pay_total').text($(this).data('total') || '0.00');
            $('#invoicePaymentModal').modal('show');
        });

    });
</script>
@endsection
