@extends('layouts.app')
@section('title', __('petrogeneral::lang.issue_bill_customer'))

<style>
    .settlement_tabs {
        margin-top: 0px !important;
    }
</style>
@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#issue_bill_customer" class="issue_bill_customer" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i>
                                <strong>@lang('petrogeneral::lang.issue_bills_customer')</strong>
                            </a>
                        </li>
                        <li class="">
                            <a href="#customer_bill_printer_setting" class="customer_bill_printer_setting"
                               data-toggle="tab">
                                <i class="fa fa-file-text-o"></i>
                                <strong>@lang('petrogeneral::lang.customer_bill_printer_setting')</strong>
                            </a>
                        </li>
                        <li class="">
                            <a href="#map_pump_to_operator" class="map_pump_to_operator"
                               data-toggle="tab">
                                <i class="fa fa-file-text-o"></i>
                                <strong>@lang('petrogeneral::lang.map_pump_to_operator')</strong>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="issue_bill_customer">
                            @include('petrogeneral::issue_bill_customer.partials.issue_bill_customer')
                        </div>
                        <div class="tab-pane " id="customer_bill_printer_setting">
                            @include('petrogeneral::issue_bill_customer.partials.customer_bill_printer_setting')
                        </div>
                        <div class="tab-pane " id="map_pump_to_operator">
                            @include('petrogeneral::issue_bill_customer.partials.map_pump_to_operator')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->

@endsection
@section('javascript')
    <script type="text/javascript">
        issue_bill_customer_table = $('#issue_bill_customer_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{action('\Modules\PetroGeneral\Http\Controllers\IssueCustomerBillController@index')}}",
            },
            columnDefs: [{
                "targets": 9,
                "orderable": false,
                "searchable": false
            }],
            columns: [
                {data: 'date', name: 'date'},
                {data: 'customer_bill_no', name: 'customer_bill_no'},
                {
                    data: 'total_amount',
                    name: 'total_amount',
                    className: 'text-right'
                },
                {data: 'pump_name', name: 'pump_name'},
                {data: 'operator_name', name: 'pump_operators.name'},
                {data: 'customer_name', name: 'contacts.name'},
                {data: 'reference', name: 'reference'},
                {data: 'order_bill_no', name: 'order_bill_no'},
                {data: 'username', name: 'users.username'},
                {data: 'action', name: 'action'},
            ],
            "fnDrawCallback": function (oSettings) {
            }
        });
        $(document).on('click', 'a.delete-issue_bill_customer', function () {
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    let href = $(this).data('href');

                    $.ajax({
                        method: 'delete',
                        url: href,
                        data: {},
                        success: function (result) {
                            if (result.success == 1) {
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            issue_bill_customer_table.ajax.reload();
                        },
                    });
                }
            });
        });

        $(document).on('click', 'a.print_bill', function () {
            let href = $(this).data('href');

            $.ajax({
                method: 'get',
                url: href,
                data: {},
                contentType: 'html',
                success: function (result) {
                    html = result;
                    console.log(html);
                    var w = window.open('', '_self');
                    $(w.document.body).html(html);
                    w.print();
                    w.close();
                    location.reload();
                },
            });


        });


        $(document).on('click', '#add_issue_bill_customer_btn', function () {
            $('.issue_bill_customer_model').modal({
                backdrop: 'static',
                keyboard: false
            })
        })

        $(document).on('submit', 'form#customer_bill_printer_setting_form', function(e) {
            e.preventDefault();
            var form = $(this);
            form.find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                    form.find('button[type="submit"]').attr('disabled', false);
                },
            });
        });

        $(document).on('click', '#add_pump_operator_mapping_btn', function () {
            $('.pump_to_operator_modal').modal({
                backdrop: 'static',
                keyboard: false
            })
        })

        var pump_operator_mapping_table = $('#pump_operator_mapping_table').DataTable({
            ajax: {
                url: '/petro-general/pump-operator-mapping',
                dataSrc: 'data'
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'assigned_date', name: 'assigned_date' },
                { data: 'assigned_time', name: 'assigned_time' },
                { data: 'operator_name', name: 'operator_name' },
                { data: 'pumps', name: 'pumps' }
            ]
        });

        $(document).on('change', '.select-multiple', function () {
            const $this = $(this);
            const selectedPumps = $this.val() || [];

            var block = $(this).closest('.operator-block');
            var container = block.find('#pump-products-list');
            if (selectedPumps && selectedPumps.length > 0) {
                $.ajax({
                    url: '/petro-general/get-products-by-pump',
                    method: 'GET',
                    data: {pump_ids: selectedPumps},
                    success: function (products) {
                        if (products.length > 0) {
                            var html = '<div class="product-badges">';
                            products.forEach(function (prod) {
                                html += '<span class="badge badge-primary">' + prod.product_name + '</span> ';
                            });
                            html += '</div>';
                            container.html(html);
                        } else {
                            container.empty();
                        }
                    }
                });
            } else {
                container.empty();
            }
        });

        $(document).on('select2:select select2:unselect', '.select-multiple', function () {
            refreshPumpOptions();

            // mở lại dropdown sau refresh
            let $select = $(this);
            setTimeout(() => $select.select2('open'), 0);
        });

        $(document).on('submit', 'form#issue_bill_customer_form', function(e) {
            e.preventDefault();
            var form = $(this);
            form.find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        $.ajax({
                            method: 'get',
                            url: '/petro-general/issue-customer-bill/print/' + result.id,
                            data: {},
                            contentType: 'html',
                            success: function (result) {
                                html = result;
                                var w = window.open('', '_self');
                                $(w.document.body).html(html);
                                w.print();
                                w.close();
                                location.reload();
                            },
                        });
                    } else {
                        form.find('button[type="submit"]').attr('disabled', false);
                        toastr.error(result.msg);
                    }
                }
            });
        });
    </script>
@endsection