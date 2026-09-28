@extends('layouts.app')

@section('title', __('subscription::lang.subscription_settings'))

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#tab_subscription_settings" data-toggle="tab">
                                <i class="fa fa-list"></i> <strong>
                                    @lang('subscription::lang.subscription_settings')</strong>
                            </a>
                        </li>
                        <li>
                            <a href="#tab_bank_accounts" data-toggle="tab">
                                <i class="fa fa-list"></i> <strong>
                                    @lang('subscription::lang.bank_ac_details')</strong>
                            </a>
                        </li>
                        <li>
                            <a href="#tab_payment_terms" data-toggle="tab">
                                <i class="fa fa-list"></i> <strong>
                                    @lang('subscription::lang.payment_terms')</strong>
                            </a>
                        </li>
                        <li>
                            <a href="#tab_banners" data-toggle="tab">
                                <i class="fa fa-list"></i> <strong>
                                    @lang('subscription::lang.banners')</strong>
                            </a>
                        </li>
                        <li>
                            <a href="#tab_invoice_prefix" data-toggle="tab">
                                <i class="fa fa-list"></i> <strong>
                                    @lang('subscription::lang.invoice_nos_and_prefixes')</strong>
                            </a>
                        </li>

                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab_subscription_settings">
                            @include('subscription::settings.tabs.subscription_settings')
                        </div>

                        <div class="tab-pane" id="tab_bank_accounts">
                            @include('subscription::settings.tabs.bank_accounts')
                        </div>

                        <div class="tab-pane" id="tab_payment_terms">
                            @include('subscription::settings.tabs.payment_terms')
                        </div>

                        <div class="tab-pane" id="tab_banners">
                            @include('subscription::settings.tabs.banners')
                        </div>

                        <div class="tab-pane" id="tab_invoice_prefix">
                            @include('subscription::settings.tabs.invoice_prefix')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade subscription_modal" role="dialog"></div>

    </section>
@endsection

@section('javascript')

    <script>
        $(document)
            .off('click.addNewSubscription')
            .on('click.addNewSubscription', '#add_fleet_btn', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadSubscriptionModal($(this).data('href'));
            });

        $(document)
            .off('click.editSubscription')
            .on('click.editSubscription', '.btn-edit-subscription', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadSubscriptionModal($(this).data('href'));
            });

        $(document)
            .off('click.addSubscription')
            .on('click.addSubscription', '.btn-add-subscription', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadSubscriptionModal($(this).data('href'));
            });

        function loadSubscriptionModal(url) {
            let container = $('.subscription_modal');

            if (container.hasClass('loading')) return;
            container.addClass('loading');

            container.load(url, function(response, status) {
                container.removeClass('loading');

                if (status !== "success") {
                    toastr.error('Could not load modal');
                    return;
                }

                container.modal({
                    backdrop: 'static',
                    keyboard: false
                });

                container.find('.select2').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });

                container.find('.select2').select2({
                    dropdownParent: container,
                    width: '100%',
                    placeholder: "{{ __('subscription::lang.please_select') }}",
                    allowClear: true,
                    minimumResultsForSearch: 0
                });

                container.off('change.baseAmount').on('change.baseAmount', '#product, #subscription_product_id',
                    function() {
                        let productId = $(this).val();
                        let baseInput = container.find('#base_amount');
                        if (!productId) return baseInput.val('');

                        $.get("{{ route('subscription-products.get-price') }}", {
                                id: productId
                            })
                            .done(res => baseInput.val(res.price))
                            .fail(() => baseInput.val(''));
                    });
            });
        }

        $(document).ready(function() {

            if ($('#subscription_settings_table').length) {
                subscription_settings_table = $('#subscription_settings_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ action('\Modules\Subscription\Http\Controllers\SubscriptionSettingController@index') }}",
                        data: function(d) {

                        }
                    },
                    columnDefs: [{
                        "targets": 1,
                        "orderable": false,
                        "searchable": false
                    }],
                    columns: [{
                            data: 'action',
                            name: 'action'
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'product_name',
                            name: 'product_name'
                        },
                        {
                            data: 'base_amount',
                            name: 'base_amount'
                        },
                        {
                            data: 'subscription_amount',
                            name: 'subscription_amount'
                        },
                        {
                            data: 'subscription_cycle',
                            name: 'subscription_cycle',
                            searchable: false
                        },
                        {
                            data: 'user',
                            name: 'users.username'
                        }

                    ],
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#subscription_settings_table'));
                    }
                });
            }

            $(document).on('click', 'a.delete-button', function() {
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
                            success: function(result) {
                                if (result.success == 1) {
                                    toastr.success(result.msg);
                                } else {
                                    toastr.error(result.msg);
                                }
                                subscription_settings_table.ajax.reload();
                            },
                        });
                    }
                });
            })

            // Modal triggers
            $(document).on('click',
                '.add-bank-account, .add-payment-term, .add-banner, .add-invoice-prefix',
                function(e) {
                    e.preventDefault();
                    loadSubscriptionModal($(this).data('href'));
                });

            // Bank Accounts form submission
            $(document)
                .off('submit.bankAccountForm')
                .on('submit.bankAccountForm', '.bank_account_form', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var submitBtn = form.find('#save_bank_account_btn');
                    var modal = form.closest('.modal');

                    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + submitBtn
                        .text());

                    $.ajax({
                        url: form.attr('action'),
                        method: form.attr('method'),
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg);
                                modal.modal('hide');
                                subscription_bank_accounts_table.ajax.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                // Validation errors
                                var errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    toastr.error(value[0]);
                                });
                            } else {
                                toastr.error('@lang('messages.something_went_wrong')');
                            }
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false);
                            submitBtn.html(
                                '@if (isset($account)) @lang('messages.update') @else @lang('messages.save') @endif'
                            );
                        }
                    });
                });

            // Delete bank account
            $(document)
                .off('click.deleteBankAccount')
                .on('click.deleteBankAccount', '.delete-bank-account', function(e) {
                    e.preventDefault();
                    var url = $(this).data('href');

                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $.ajax({
                                method: 'DELETE',
                                url: url,
                                success: function(result) {
                                    if (result.success) {
                                        toastr.success(result.msg);
                                        subscription_bank_accounts_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                                error: function() {
                                    toastr.error('@lang('messages.something_went_wrong')');
                                }
                            });
                        }
                    });
                });

            // Edit bank account modal trigger
            $(document)
                .off('click.editBankAccount')
                .on('click.editBankAccount', '.edit-bank-account', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    loadSubscriptionModal($(this).data('href'));
                });

            // Initialize DataTables for bank accounts
            if ($('#subscription_bank_accounts_table').length) {
                subscription_bank_accounts_table = $('#subscription_bank_accounts_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ action('\Modules\Subscription\Http\Controllers\SubscriptionBankAccountController@index') }}",
                        data: function(d) {
                            // Add any additional data if needed
                        }
                    },
                    columnDefs: [{
                        "targets": 1,
                        "orderable": false,
                        "searchable": false
                    }],
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date',
                            name: 'date'
                        },
                        {
                            data: 'template_name',
                            name: 'template_name'
                        },
                        {
                            data: 'ac_name',
                            name: 'ac_name'
                        },
                        {
                            data: 'ac_no',
                            name: 'ac_no'
                        },
                        {
                            data: 'bank',
                            name: 'bank'
                        },
                        {
                            data: 'branch',
                            name: 'branch'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'created_by',
                            name: 'created_by'
                        },
                    ],
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#subscription_bank_accounts_table'));
                    }
                });
            }

            // Banner form submission
            $(document)
                .off('submit.bannerForm')
                .on('submit.bannerForm', '.banner_form', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var submitBtn = form.find('#save_banner_btn');
                    var modal = form.closest('.modal');

                    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + submitBtn
                        .text());

                    // Create FormData object for file upload
                    var formData = new FormData(form[0]);

                    $.ajax({
                        url: form.attr('action'),
                        method: form.attr('method'),
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg);
                                modal.modal('hide');
                                subscription_banners_table.ajax.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                // Validation errors
                                var errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    toastr.error(value[0]);
                                });
                            } else {
                                toastr.error('@lang('messages.something_went_wrong')');
                            }
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false);
                            submitBtn.html(
                                '@if (isset($banner)) @lang('messages.update') @else @lang('messages.upload') @endif'
                            );
                        }
                    });
                });

            // Delete banner
            $(document)
                .off('click.deleteBanner')
                .on('click.deleteBanner', '.delete-banner', function(e) {
                    e.preventDefault();
                    var url = $(this).data('href');

                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $.ajax({
                                method: 'DELETE',
                                url: url,
                                success: function(result) {
                                    if (result.success) {
                                        toastr.success(result.msg);
                                        subscription_banners_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                                error: function() {
                                    toastr.error('@lang('messages.something_went_wrong')');
                                }
                            });
                        }
                    });
                });

            // Edit banner modal trigger
            $(document)
                .off('click.editBanner')
                .on('click.editBanner', '.edit-banner', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    loadSubscriptionModal($(this).data('href'));
                });

            if ($('#subscription_banners_table').length) {
                subscription_banners_table = $('#subscription_banners_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ action('\Modules\Subscription\Http\Controllers\SubscriptionBannerController@index') }}",
                        data: function(d) {
                            // Add any additional data if needed
                        }
                    },
                    columnDefs: [{
                        "targets": 1,
                        "orderable": false,
                        "searchable": false
                    }],
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'logo',
                            name: 'logo',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'width',
                            name: 'width',
                        },
                        {
                            data: 'height',
                            name: 'height',
                        },
                        {
                            data: 'date',
                            name: 'date'
                        },
                        {
                            data: 'created_by',
                            name: 'created_by'
                        }
                    ],
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#subscription_banners_table'));
                    }
                });
            }

            // Preview image on file selection
            $(document)
                .off('change.previewImage')
                .on('change.previewImage', '#file_path', function(e) {
                    var input = $(this);
                    var preview = input.closest('.form-group').find('.image-preview');

                    if (preview.length === 0) {
                        preview = $('<div class="image-preview mt-2"></div>');
                        input.closest('.form-group').append(preview);
                    }

                    preview.html('');

                    if (this.files && this.files[0]) {
                        var reader = new FileReader();
                        var file = this.files[0];

                        // Check if file is an image
                        if (file.type.match('image.*')) {
                            reader.onload = function(e) {
                                preview.html('<img src="' + e.target.result +
                                    '" alt="Preview" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">'
                                );
                            }
                            reader.readAsDataURL(file);
                        }
                    }
                });

            // Invoice Prefix form submission
            $(document)
                .off('submit.invoicePrefixForm')
                .on('submit.invoicePrefixForm', '.invoice_prefix_form', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var submitBtn = form.find('#save_invoice_prefix_btn');
                    var modal = form.closest('.modal');

                    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + submitBtn
                        .text());

                    $.ajax({
                        url: form.attr('action'),
                        method: form.attr('method'),
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg);
                                modal.modal('hide');
                                subscription_invoice_prefix_table.ajax.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                if (xhr.responseJSON.errors) {
                                    // Show only field-specific errors
                                    $.each(xhr.responseJSON.errors, function(key, value) {
                                        toastr.error(value[0]);
                                    });
                                } else if (xhr.responseJSON.msg) {
                                    toastr.error(xhr.responseJSON.msg);
                                }
                            } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                                toastr.error(xhr.responseJSON.msg);
                            } else {
                                toastr.error('@lang('messages.something_went_wrong')');
                            }
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false);
                            submitBtn.html(
                                '@if (isset($prefix)) @lang('messages.update') @else @lang('messages.save') @endif'
                            );
                        }
                    });
                });

            // Delete invoice prefix
            $(document)
                .off('click.deleteInvoicePrefix')
                .on('click.deleteInvoicePrefix', '.delete-invoice-prefix', function(e) {
                    e.preventDefault();
                    var url = $(this).data('href');

                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $.ajax({
                                method: 'DELETE',
                                url: url,
                                success: function(result) {
                                    if (result.success) {
                                        toastr.success(result.msg);
                                        subscription_invoice_prefix_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                                error: function() {
                                    toastr.error('@lang('messages.something_went_wrong')');
                                }
                            });
                        }
                    });
                });

            // Edit invoice prefix modal trigger
            $(document)
                .off('click.editInvoicePrefix')
                .on('click.editInvoicePrefix', '.edit-invoice-prefix', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    loadSubscriptionModal($(this).data('href'));
                });

            if ($('#subscription_invoice_prefix_table').length) {
                subscription_invoice_prefix_table = $('#subscription_invoice_prefix_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ action('\Modules\Subscription\Http\Controllers\SubscriptionInvoicePrefixController@index') }}",
                        data: function(d) {
                            // Add any additional data if needed
                        }
                    },
                    columnDefs: [{
                        "targets": 1,
                        "orderable": false,
                        "searchable": false
                    }],
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date',
                            name: 'date'
                        },
                        {
                            data: 'user',
                            name: 'user',
                            defaultContent: ''
                        },
                        {
                            data: 'prefix',
                            name: 'prefix'
                        },
                        {
                            data: 'current_number',
                            name: 'current_number'
                        },
                    ],
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#subscription_invoice_prefix_table'));
                    }
                });
            }

            // Auto-generate next invoice number preview
            $(document)
                .off('keyup.prefixPreview change.prefixPreview')
                .on('keyup.prefixPreview change.prefixPreview', '#prefix, #current_number', function() {
                    var prefix = $('#prefix').val();
                    var currentNumber = $('#current_number').val() || 1;

                    if (prefix) {
                        var nextNumber = parseInt(currentNumber);
                        var preview = prefix + nextNumber.toString().padStart(6, '0');

                        var previewElement = $('#next_invoice_preview');
                        if (previewElement.length === 0) {
                            previewElement = $('<div id="next_invoice_preview" class="help-block mt-2"></div>');
                            $('#prefix').closest('.form-group').append(previewElement);
                        }

                        previewElement.html('<strong>@lang('subscription::lang.next_invoice_number'):</strong> ' + preview);
                    }
                });

            // Payment Terms form submission
            $(document)
                .off('submit.paymentTermForm')
                .on('submit.paymentTermForm', '.payment_term_form', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var submitBtn = form.find('#save_payment_term_btn');
                    var modal = form.closest('.modal');

                    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + submitBtn
                        .text());

                    $.ajax({
                        url: form.attr('action'),
                        method: form.attr('method'),
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg);
                                modal.modal('hide');
                                subscription_payment_terms_table.ajax.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                // Validation errors
                                var errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    toastr.error(value[0]);
                                });
                            } else {
                                toastr.error('@lang('messages.something_went_wrong')');
                            }
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false);
                            submitBtn.html(
                                '@if (isset($payment_term)) @lang('messages.update') @else @lang('messages.save') @endif'
                            );
                        }
                    });
                });

            // Delete payment term
            $(document)
                .off('click.deletePaymentTerm')
                .on('click.deletePaymentTerm', '.delete-payment-term', function(e) {
                    e.preventDefault();
                    var url = $(this).data('href');

                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $.ajax({
                                method: 'DELETE',
                                url: url,
                                success: function(result) {
                                    if (result.success) {
                                        toastr.success(result.msg);
                                        subscription_payment_terms_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                    subscription_payment_terms_table.ajax.reload();
                                },
                                error: function() {
                                    toastr.error('@lang('messages.something_went_wrong')');
                                }
                            });
                        }
                    });
                });

            // Edit payment term modal trigger
            $(document)
                .off('click.editPaymentTerm')
                .on('click.editPaymentTerm', '.edit-payment-term', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    loadSubscriptionModal($(this).data('href'));
                });

            if ($('#subscription_payment_terms_table').length) {
                subscription_payment_terms_table = $('#subscription_payment_terms_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ action('\Modules\Subscription\Http\Controllers\SubscriptionPaymentTermController@index') }}",
                        data: function(d) {
                            // Add any additional data if needed
                        }
                    },
                    columnDefs: [{
                        "targets": 1,
                        "orderable": false,
                        "searchable": false
                    }],
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date',
                            name: 'date'
                        },
                        {
                            data: 'name',
                            name: 'payment_term_name'
                        },
                        {
                            data: 'payment_terms',
                            name: 'payment_terms',
                            render: function(data, type, row) {
                                if (type === 'display') {
                                    // Truncate long text for display
                                    return data.length > 100 ?
                                        data.substring(0, 100) + '...' :
                                        data;
                                }
                                return data;
                            }
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'created_by',
                            name: 'created_by',
                            render: function(data, type, row) {
                                return row.created_by_user ? row.created_by_user.username : data;
                            }
                        },
                    ],
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#subscription_payment_terms_table'));
                    }
                });
            }

            // Auto-resize textarea as user types
            $(document)
                .off('input.autoResizeTextarea')
                .on('input.autoResizeTextarea', '#terms', function(e) {
                    var el = e && e.currentTarget ? e.currentTarget : this;
                    if (!el || !el.style) return;
                    el.style.height = 'auto';
                    // fallback to clientHeight if scrollHeight missing
                    el.style.height = (el.scrollHeight || el.clientHeight || 0) + 'px';
                });

            // View full terms in a modal
            $(document)
                .off('click.viewTerms')
                .on('click.viewTerms', '.view-terms', function(e) {
                    e.preventDefault();
                    var terms = $(this).data('terms');
                    var title = $(this).data('title') || '@lang('subscription::lang.payment_terms')';

                    var modalHtml = `
                    <div class="modal fade" id="viewTermsModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">${title}</h4>
                                </div>
                                <div class="modal-body">
                                    <div style="white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
                                        ${terms}
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">
                                        @lang('messages.close')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                    $('body').append(modalHtml);
                    $('#viewTermsModal').modal('show');

                    // Clean up modal after it's closed
                    $('#viewTermsModal').on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                });
        });
    </script>
    <script>
        $(function() {

            function applySize() {
                const w = $('input[name="banner_width"]').val();
                const h = $('input[name="banner_height"]').val();

                $('#bannerPreview, .current-banner').css({
                    width: w ? w + 'px' : 'auto',
                    height: h ? h + 'px' : 'auto'
                });
            }

            $('#file_path').on('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#bannerPreview').attr('src', e.target.result);
                        applySize();
                        $('#previewBox').fadeIn(150);
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            $('input[name="banner_width"], input[name="banner_height"]').on('input', applySize);

        });
    </script>

@endsection
