@extends('layouts.app')

@section('title', 'Customer Register')

@section('content')

<section class="content-header">
    <h1>
        Customer Register
        <small>Customer Master Records</small>
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border clearfix">

            <div class="pull-left">
                <h3 class="box-title">Customer Register</h3>
            </div>

            <div class="pull-right">
                <a href="{{ route('customers.import.form') }}" class="btn btn-info btn-sm">
                    <i class="fa fa-upload"></i> @lang('customers::lang.import_customers')
                </a>
                <a href="{{ route('customers.import_template') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-download"></i> @lang('customers::lang.import_template')
                </a>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> @lang('customers::lang.add_customer')
                </a>
            </div>

        </div>

        <div class="box-body">

            @include('layouts.partials.erp-ajax-datatable-standard', [
                'table_id' => 'customers_table',
                'default_per_page' => 25
            ])

            <div class="table-responsive">
                <table id="customers_table" class="table table-bordered table-striped table-hover" width="100%">
                    <thead>
                        <tr>
                            <th>Actions</th>
                            <th>Customer Code</th>
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Credit Limit</th>
                            <th>Total Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Page Total</th>
                            <th class="text-right" id="customers_page_total_due">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>

    </div>

</section>

@endsection

@section('javascript')
<script>
$(document).ready(function () {

    if ($.fn.DataTable.isDataTable('#customers_table')) {
        $('#customers_table').DataTable().destroy();
    }

    let customersTable = $('#customers_table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        searchDelay: 0,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 200, 500], [10, 25, 50, 100, 200, 500]],

        ajax: {
            url: "{{ route('customers.index') }}",
            type: "GET"
        },

        dom: 'Brtip',

        buttons: [
            {
                extend: 'csv',
                className: 'erp-hidden-dt-button'
            },
            {
                extend: 'excel',
                className: 'erp-hidden-dt-button'
            },
            {
                extend: 'pdf',
                className: 'erp-hidden-dt-button'
            },
            {
                extend: 'print',
                className: 'erp-hidden-dt-button'
            },
            {
                extend: 'colvis',
                className: 'erp-hidden-dt-button'
            }
        ],

        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'contact_id', name: 'contact_id' },
            { data: 'name', name: 'name' },
            { data: 'mobile', name: 'mobile' },
            { data: 'email', name: 'email' },
            { data: 'credit_limit', name: 'credit_limit', className: 'text-right' },
            { data: 'total_due', name: 'total_due', className: 'text-right', searchable: false },
            { data: 'active', name: 'active', orderable: false, searchable: false }
        ],

        order: [[2, 'asc']],

        initComplete: function () {
            $('#customers_table_wrapper .dt-buttons').hide();
        },

        drawCallback: function () {
            $('#customers_table_wrapper .dt-buttons').hide();

            var pageTotalDue = 0;
            $('#customers_table tbody .customer-total-due').each(function () {
                var value = parseFloat($(this).attr('data-orig-value'));
                if (!isNaN(value)) {
                    pageTotalDue += value;
                }
            });
            $('#customers_page_total_due').text(pageTotalDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            if (typeof __currency_convert_recursively === 'function') {
                __currency_convert_recursively($('#customers_table'));
            }
        }
    });

});
</script>
@endsection