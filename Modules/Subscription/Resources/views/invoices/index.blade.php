@extends('layouts.app')

@section('title', __('subscription::lang.create_invoice'))

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => 'Saved Invoices'])
                    @slot('tool')
                        <div class="box-tools pull-right">
                            <a href="{{ route('subscription.invoices.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Create Invoice
                            </a>
                        </div>
                    @endslot

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="subscription_invoices_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Invoice No</th>
                                    <th>Customer</th>
                                    <th>Customer Code</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Total</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            let invoicesTable = $('#subscription_invoices_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('subscription.invoices.index') }}",
                columns: [
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'invoice_no', name: 'subscription_invoices.invoice_no' },
                    { data: 'customer_name', name: 'contacts.name' },
                    { data: 'customer_code', name: 'subscription_invoices.customer_code' },
                    { data: 'from_date', name: 'subscription_invoices.from_date' },
                    { data: 'to_date', name: 'subscription_invoices.to_date' },
                    { data: 'total', name: 'subscription_invoices.total', className: 'text-right' },
                    { data: 'created_at', name: 'subscription_invoices.created_at' }
                ],
                fnDrawCallback: function() {
                    __currency_convert_recursively($('#subscription_invoices_table'));
                }
            });

            $(document).on('click', '.delete-invoice', function() {
                let href = $(this).data('href');

                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                } else {
                                    toastr.error(result.msg || 'Unable to delete invoice');
                                }
                                invoicesTable.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.msg || 'Unable to delete invoice');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
