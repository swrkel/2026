@extends('layouts.app')
@section('title', __('purchase.purchase_settings'))

@section('content')

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('purchase.purchase_settings')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">Purchases</a></li>
                        <li><span>@lang('purchase.purchase_settings')</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <section class="content main-content-inner no-print">

        @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.purchase_settings')])
            @slot('tool')
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-modal"
                        data-href="{{ action('PurchaseSettingsController@showCreateForm') }}"
                        data-container=".purchase_return_account_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                </div>
            @endslot
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="purchase_return_account_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('purchase.date_time_added')</th>
                            <th>@lang('purchase.purchase_return_account')</th>
                            <th>@lang('purchase.added_user')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
    </section>

    <div class="modal fade purchase_return_account_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            // Datatable
            var purchase_return_account_table = $('#purchase_return_account_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ action('PurchaseSettingsController@getPurchaseReturnAccounts') }}',
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'account.name',
                        name: 'account.name'
                    },
                    {
                        data: 'created_user',
                        name: 'created_user'
                    }
                ]
            });

            // Fix for double request - use event delegation properly
            var modalLoaded = false;

            // Load modal form
            $(document).on('click', '.btn-modal', function(e) {
                e.preventDefault();

                if (!modalLoaded) {
                    var href = $(this).data('href');
                    var container = $(this).data('container');

                    $(container).load(href, function() {
                        $(this).modal('show');
                        $('.select2').select2();
                        modalLoaded = true;
                    });
                }
            });

            // Reset modalLoaded when modal is closed
            $(document).on('hidden.bs.modal', '.purchase_return_account_modal', function() {
                modalLoaded = false;
            });

            // Add purchase return account - Submit form
            $(document).on('submit', 'form#purchase_return_account_add_form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var submitBtn = $form.find('button[type="submit"]');

                // Disable submit button to prevent double submission
                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                var data = $form.serialize();
                $.ajax({
                    method: "POST",
                    url: $form.attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success) {
                            $('.purchase_return_account_modal').modal('hide');
                            toastr.success(result.msg);
                            purchase_return_account_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html('Save');
                    },
                    error: function() {
                        toastr.error('An error occurred. Please try again.');
                        submitBtn.prop('disabled', false).html('Save');
                    }
                });
            });

            // Edit purchase return account
            $(document).on('click', '.edit-purchase-return-account-btn', function(e) {
                e.preventDefault();
                var href = $(this).data('href');
                $('div.purchase_return_account_modal').load(href, function() {
                    $(this).modal('show');
                    $('.select2').select2();
                });
            });

            // Update purchase return account
            $(document).on('submit', 'form#purchase_return_account_edit_form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var submitBtn = $form.find('button[type="submit"]');

                // Disable submit button to prevent double submission
                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');

                var data = $form.serialize();
                $.ajax({
                    method: "PUT",
                    url: $form.attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success) {
                            $('.purchase_return_account_modal').modal('hide');
                            toastr.success(result.msg);
                            purchase_return_account_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html('Update');
                    },
                    error: function() {
                        toastr.error('An error occurred. Please try again.');
                        submitBtn.prop('disabled', false).html('Update');
                    }
                });
            });

            // Delete purchase return account
            $(document).on('click', '.delete-purchase-return-account-btn', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    purchase_return_account_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
