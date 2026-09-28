<script type="text/javascript">
(function ($) {
    "use strict";

    var selectors = {
        table: '#business_names_table',
        addButton: '#open_add_business_name_modal',
        addModal: '#add_business_name_modal',
        ajaxModal: '.business_name_modal',
        addForm: 'form#add_business_name_form',
        editForm: 'form#edit_business_name_form'
    };

    var membershipBusinessNameConfig = {
        listUrl: @json(action('\\Modules\\Membership\\Http\\Controllers\\MembershipSettingController@getBusinessNames')),
        deleteUrlBase: @json(url('/membership/setting/business-names')),
        csrfToken: @json(csrf_token()),
        langSomethingWrong: @json(__('messages.something_went_wrong')),
        langSaving: @json(__('messages.saving') ?: 'Saving...'),
        langRemove: @json(__('messages.remove')),
        langBusinessName: @json(__('membership::lang.Business_name')),
        langEnterBusinessName: @json(__('membership::lang.enter_business_name'))
    };

    var businessNamesTable = null;

    function getBootstrapModal($modal) {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function' && $modal.length) {
            return window.bootstrap.Modal.getOrCreateInstance($modal[0]);
        }

        return null;
    }

    function showModal($modal) {
        if (!$modal.length) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Business Name modal is not available. Please refresh and try again.');
            }
            return;
        }

        var bs5Modal = getBootstrapModal($modal);
        if (bs5Modal) {
            bs5Modal.show();
            return;
        }

        if (typeof $.fn.modal === 'function') {
            $modal.modal('show');
            return;
        }

        $modal.addClass('in').show().attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        if (!$('.modal-backdrop.membership-business-name-backdrop').length) {
            $('<div class="modal-backdrop fade in membership-business-name-backdrop"></div>').appendTo(document.body);
        }
    }

    function hideModal($modal) {
        if (!$modal.length) {
            return;
        }

        var bs5Modal = getBootstrapModal($modal);
        if (bs5Modal) {
            bs5Modal.hide();
        } else if (typeof $.fn.modal === 'function') {
            $modal.modal('hide');
        }

        // Safe cleanup for mixed Bootstrap 3/4/5 or failed modal states.
        $modal.removeClass('in show').hide().attr('aria-hidden', 'true');
        $('.modal-backdrop.membership-business-name-backdrop').remove();
        if (!$('.modal.in, .modal.show').length) {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    }

    function initSelect2InModal($modal) {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }

        $modal.find('.select2').each(function () {
            var $select = $(this);
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            $select.select2({ dropdownParent: $modal });
        });
    }

    function resetAddBusinessNameModal() {
        var $modal = $(selectors.addModal);
        var $form = $modal.find(selectors.addForm);

        if ($form.length && $form[0]) {
            $form[0].reset();
        }

        $modal.find('#business_name_rows_container').html(
            '<div class="form-group business-name-row">' +
                '<label>' + membershipBusinessNameConfig.langBusinessName + ':*</label>' +
                '<div class="input-group">' +
                    '<input type="text" name="business_names[]" class="form-control business-name-input" placeholder="' + membershipBusinessNameConfig.langEnterBusinessName + '" autocomplete="off" required>' +
                    '<span class="input-group-btn">' +
                        '<button type="button" class="btn btn-primary add-business-name-row" title="Add" style="height: 34px;"><i class="fa fa-plus"></i></button>' +
                    '</span>' +
                '</div>' +
            '</div>'
        );

        $modal.find('.select2').val('').trigger('change');
    }

    function reloadBusinessNamesTable() {
        if (businessNamesTable) {
            businessNamesTable.ajax.reload(null, false);
        }
    }

    function initBusinessNameDataTable() {
        if (!$(selectors.table).length || typeof $.fn.DataTable !== 'function') {
            return;
        }

        if ($.fn.DataTable.isDataTable(selectors.table)) {
            businessNamesTable = $(selectors.table).DataTable();
            return;
        }

        businessNamesTable = $(selectors.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: membershipBusinessNameConfig.listUrl },
            aaSorting: [[4, 'desc']],
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'business_type', name: 'business_type' },
                { data: 'business_name', name: 'business_name' },
                { data: 'added_by', name: 'added_by' },
                { data: 'date_time', name: 'created_at' }
            ]
        });
    }

    function addBusinessNameRow(containerSelector, inputName, rowClass, removeClass) {
        var rowHtml = '<div class="form-group ' + rowClass + '">' +
            '<label>' + membershipBusinessNameConfig.langBusinessName + ':</label>' +
            '<div class="input-group">' +
                '<input type="text" name="' + inputName + '" class="form-control" placeholder="' + membershipBusinessNameConfig.langEnterBusinessName + '" autocomplete="off">' +
                '<span class="input-group-btn">' +
                    '<button type="button" class="btn btn-danger ' + removeClass + '" title="' + membershipBusinessNameConfig.langRemove + '" style="height: 34px;"><i class="fa fa-minus"></i></button>' +
                '</span>' +
            '</div>' +
        '</div>';

        $(containerSelector).append(rowHtml);
    }

    function submitBusinessNameForm($form) {
        var $submitBtn = $form.find('button[type="submit"]');
        var originalBtnHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            method: 'POST',
            url: $form.attr('action'),
            dataType: 'json',
            data: $form.serialize(),
            success: function (result) {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);

                if (result.success == true) {
                    hideModal($(selectors.addModal));
                    hideModal($(selectors.ajaxModal));
                    if (typeof toastr !== 'undefined') {
                        toastr.success(result.msg);
                    }
                    reloadBusinessNamesTable();
                    resetAddBusinessNameModal();
                    return;
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(result.msg || membershipBusinessNameConfig.langSomethingWrong);
                }
            },
            error: function (xhr) {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);

                var message = membershipBusinessNameConfig.langSomethingWrong;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
            }
        });
    }

    function deleteBusinessName(id) {
        $.ajax({
            method: 'DELETE',
            url: membershipBusinessNameConfig.deleteUrlBase + '/' + id,
            dataType: 'json',
            data: { _token: membershipBusinessNameConfig.csrfToken },
            success: function (result) {
                if (result.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(result.msg);
                    }
                    reloadBusinessNamesTable();
                    return;
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(result.msg || membershipBusinessNameConfig.langSomethingWrong);
                }
            },
            error: function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error(membershipBusinessNameConfig.langSomethingWrong);
                }
            }
        });
    }

    $(function () {
        initBusinessNameDataTable();

        $(document)
            .off('click.membershipBusinessNameOpen', selectors.addButton)
            .on('click.membershipBusinessNameOpen', selectors.addButton, function (e) {
                e.preventDefault();
                showModal($(selectors.addModal));
                initSelect2InModal($(selectors.addModal));
            });

        $(document)
            .off('click.membershipBusinessNameClose', '[data-membership-dismiss="modal"], #add_business_name_modal [data-dismiss="modal"], #add_business_name_modal [data-bs-dismiss="modal"], .business_name_modal [data-dismiss="modal"], .business_name_modal [data-bs-dismiss="modal"]')
            .on('click.membershipBusinessNameClose', '[data-membership-dismiss="modal"], #add_business_name_modal [data-dismiss="modal"], #add_business_name_modal [data-bs-dismiss="modal"], .business_name_modal [data-dismiss="modal"], .business_name_modal [data-bs-dismiss="modal"]', function (e) {
                e.preventDefault();
                hideModal($(this).closest('.modal'));
            });

        $(document)
            .off('click.membershipBusinessNameAddRow', '.add-business-name-row')
            .on('click.membershipBusinessNameAddRow', '.add-business-name-row', function (e) {
                e.preventDefault();
                addBusinessNameRow('#business_name_rows_container', 'business_names[]', 'business-name-row', 'remove-business-name-row');
            });

        $(document)
            .off('click.membershipBusinessNameRemoveRow', '.remove-business-name-row')
            .on('click.membershipBusinessNameRemoveRow', '.remove-business-name-row', function (e) {
                e.preventDefault();
                $(this).closest('.business-name-row').remove();
            });

        $(document)
            .off('click.membershipBusinessNameEditAddRow', '.add-edit-business-name-row')
            .on('click.membershipBusinessNameEditAddRow', '.add-edit-business-name-row', function (e) {
                e.preventDefault();
                addBusinessNameRow('#edit_business_name_rows_container', 'additional_business_names[]', 'business-name-edit-row', 'remove-edit-business-name-row');
            });

        $(document)
            .off('click.membershipBusinessNameEditRemoveRow', '.remove-edit-business-name-row')
            .on('click.membershipBusinessNameEditRemoveRow', '.remove-edit-business-name-row', function (e) {
                e.preventDefault();
                $(this).closest('.business-name-edit-row').remove();
            });

        $(document)
            .off('submit.membershipBusinessNameSubmit', selectors.addForm + ', ' + selectors.editForm)
            .on('submit.membershipBusinessNameSubmit', selectors.addForm + ', ' + selectors.editForm, function (e) {
                e.preventDefault();
                submitBusinessNameForm($(this));
            });

        $(selectors.addModal)
            .off('shown.bs.modal.membershipBusinessName')
            .on('shown.bs.modal.membershipBusinessName', function () {
                initSelect2InModal($(this));
            })
            .off('hidden.bs.modal.membershipBusinessName')
            .on('hidden.bs.modal.membershipBusinessName', resetAddBusinessNameModal);

        $(document)
            .off('shown.bs.modal.membershipBusinessNameAjax', selectors.ajaxModal)
            .on('shown.bs.modal.membershipBusinessNameAjax', selectors.ajaxModal, function () {
                initSelect2InModal($(this));
            });

        $(document)
            .off('click.membershipBusinessNameDelete', '.delete_business_name_btn')
            .on('click.membershipBusinessNameDelete', '.delete_business_name_btn', function (e) {
                e.preventDefault();
                var businessNameId = $(this).data('id');

                if (typeof swal === 'function') {
                    swal({
                        title: (window.LANG && LANG.sure) ? LANG.sure : 'Are you sure?',
                        icon: 'warning',
                        buttons: true,
                        dangerMode: true
                    }).then(function (confirmed) {
                        if (confirmed) {
                            deleteBusinessName(businessNameId);
                        }
                    });
                    return;
                }

                if (confirm('Are you sure?')) {
                    deleteBusinessName(businessNameId);
                }
            });
    });
})(jQuery);
</script>
