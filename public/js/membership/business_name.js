(function ($) {
    'use strict';

    var state = { table: null, initialized: false };

    function cfg() { return window.membershipBusinessNameConfig || {}; }
    function sel(key) { return (cfg().selectors || {})[key] || ''; }

    function notifySuccess(msg) {
        if (typeof toastr !== 'undefined') { toastr.success(msg); }
    }

    function notifyError(msg) {
        if (typeof toastr !== 'undefined') { toastr.error(msg); } else { alert(msg); }
    }

    function modalInstance($modal) {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function' && $modal.length) {
            return window.bootstrap.Modal.getOrCreateInstance($modal[0]);
        }
        return null;
    }

    function showModal($modal) {
        if (!$modal.length) { return; }
        var bs5 = modalInstance($modal);
        if (bs5) { bs5.show(); return; }
        if (typeof $.fn.modal === 'function') { $modal.modal('show'); return; }
        $modal.addClass('in show').show().attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        if (!$('.modal-backdrop').length) {
            $('<div class="modal-backdrop fade in show"></div>').appendTo(document.body);
        }
    }

    function hideModal($modal) {
        if (!$modal.length) { return; }
        var bs5 = modalInstance($modal);
        if (bs5) { bs5.hide(); }
        else if (typeof $.fn.modal === 'function') { $modal.modal('hide'); }
        else { $modal.removeClass('in show').hide().attr('aria-hidden', 'true'); }

        window.setTimeout(function () {
            if (!$('.modal.in:visible, .modal.show:visible').length) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            }
        }, 200);
    }

    function initSelect2($container) {
        if (typeof $.fn.select2 !== 'function') { return; }
        $container.find('.select2').each(function () {
            var $select = $(this);
            if ($select.data('select2')) { $select.select2('destroy'); }
            $select.select2({ dropdownParent: $container });
        });
    }

    function rowHtml(inputName, rowClass, removeClass, withAdd) {
        var label = cfg().langBusinessName || 'Business Name';
        var placeholder = cfg().langEnterBusinessName || 'Enter Business Name';
        var icon = withAdd ? 'fa fa-plus' : 'fa fa-minus';
        var btnClass = withAdd ? 'btn btn-primary add-business-name-row' : 'btn btn-danger ' + removeClass;
        var required = withAdd ? ' required' : '';
        return '<div class="form-group ' + rowClass + '">' +
            '<label>' + label + (withAdd ? ':*' : ':') + '</label>' +
            '<div class="input-group">' +
            '<input type="text" name="' + inputName + '" class="form-control business-name-input" placeholder="' + placeholder + '" autocomplete="off"' + required + '>' +
            '<span class="input-group-btn"><button type="button" class="' + btnClass + '" style="height:34px;"><i class="' + icon + '"></i></button></span>' +
            '</div></div>';
    }

    function initTable() {
        var tableSelector = sel('table');
        if (!$(tableSelector).length || typeof $.fn.DataTable !== 'function') { return; }
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            state.table = $(tableSelector).DataTable();
            return;
        }
        state.table = $(tableSelector).DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: cfg().listUrl },
            order: [[4, 'desc']],
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'business_type', name: 'business_type' },
                { data: 'business_name', name: 'business_name' },
                { data: 'added_by', name: 'added_by' },
                { data: 'date_time', name: 'created_at' }
            ]
        });
    }

    function reloadTable() {
        if (state.table) { state.table.ajax.reload(null, false); }
    }

    function openModal(url) {
        var $modal = $(sel('ajaxModal'));
        if (!$modal.length || !url) { return; }
        $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
        showModal($modal);
        $.ajax({
            method: 'GET',
            url: url,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                $modal.html(html);
                showModal($modal);
                initSelect2($modal);
            },
            error: function () {
                hideModal($modal);
                notifyError(cfg().langSomethingWrong || 'Something went wrong');
            }
        });
    }

    function resetCreateRows($modal) {
        var $container = $modal.find('#business_name_rows_container');
        if ($container.length) {
            $container.html(rowHtml('business_names[]', 'business-name-row', 'remove-business-name-row', true));
        }
    }

    function submitForm($form) {
        var $submit = $form.find('button[type="submit"]');
        var original = $submit.html();
        var method = $form.find('input[name="_method"]').val() || $form.attr('method') || 'POST';
        $submit.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            method: method,
            url: $form.attr('action'),
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            success: function (result) {
                if (result && result.success) {
                    notifySuccess(result.msg || 'Saved successfully');
                    hideModal($form.closest('.modal'));
                    reloadTable();
                    return;
                }
                notifyError((result && result.msg) || cfg().langSomethingWrong || 'Something went wrong');
            },
            error: function (xhr) {
                var msg = cfg().langSomethingWrong || 'Something went wrong';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.msg || msg;
                }
                notifyError(msg);
            },
            complete: function () {
                $submit.prop('disabled', false).html(original);
            }
        });
    }

    function deleteBusinessName(id) {
        $.ajax({
            method: 'POST',
            url: cfg().deleteUrlBase + '/' + id,
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            data: { _token: cfg().csrfToken, _method: 'DELETE' },
            success: function (result) {
                if (result && result.success) {
                    notifySuccess(result.msg || 'Deleted successfully');
                    reloadTable();
                    return;
                }
                notifyError((result && result.msg) || cfg().langSomethingWrong || 'Something went wrong');
            },
            error: function () { notifyError(cfg().langSomethingWrong || 'Something went wrong'); }
        });
    }

    function bindEvents() {
        $(document)
            .off('click.membershipBusinessName.open', sel('addButton') + ', .membership-business-name-ajax')
            .on('click.membershipBusinessName.open', sel('addButton') + ', .membership-business-name-ajax', function (e) {
                e.preventDefault();
                openModal($(this).data('href') || cfg().createUrl);
            });

        $(document)
            .off('click.membershipBusinessName.rowAdd', '.add-business-name-row')
            .on('click.membershipBusinessName.rowAdd', '.add-business-name-row', function (e) {
                e.preventDefault();
                $('#business_name_rows_container').append(rowHtml('business_names[]', 'business-name-row', 'remove-business-name-row', false));
            });

        $(document)
            .off('click.membershipBusinessName.editRowAdd', '.add-edit-business-name-row')
            .on('click.membershipBusinessName.editRowAdd', '.add-edit-business-name-row', function (e) {
                e.preventDefault();
                $('#edit_business_name_rows_container').append(rowHtml('additional_business_names[]', 'business-name-edit-row', 'remove-edit-business-name-row', false));
            });

        $(document)
            .off('click.membershipBusinessName.rowRemove', '.remove-business-name-row, .remove-edit-business-name-row')
            .on('click.membershipBusinessName.rowRemove', '.remove-business-name-row, .remove-edit-business-name-row', function (e) {
                e.preventDefault();
                $(this).closest('.business-name-row, .business-name-edit-row').remove();
            });

        $(document)
            .off('submit.membershipBusinessName.form', sel('addForm') + ', ' + sel('editForm'))
            .on('submit.membershipBusinessName.form', sel('addForm') + ', ' + sel('editForm'), function (e) {
                e.preventDefault();
                submitForm($(this));
            });

        $(document)
            .off('click.membershipBusinessName.close', '.business_name_modal [data-dismiss="modal"], .business_name_modal [data-bs-dismiss="modal"], .business_name_modal .close')
            .on('click.membershipBusinessName.close', '.business_name_modal [data-dismiss="modal"], .business_name_modal [data-bs-dismiss="modal"], .business_name_modal .close', function (e) {
                e.preventDefault();
                hideModal($(this).closest('.modal'));
            });

        $(document)
            .off('click.membershipBusinessName.delete', '.delete_business_name_btn')
            .on('click.membershipBusinessName.delete', '.delete_business_name_btn', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var run = function () { deleteBusinessName(id); };
                if (typeof swal === 'function') {
                    swal({ title: (window.LANG && LANG.sure) || 'Are you sure?', icon: 'warning', buttons: true, dangerMode: true }).then(function (ok) { if (ok) { run(); } });
                } else if (window.confirm('Are you sure?')) { run(); }
            });

        $(document)
            .off('hidden.bs.modal.membershipBusinessName', sel('ajaxModal'))
            .on('hidden.bs.modal.membershipBusinessName', sel('ajaxModal'), function () {
                resetCreateRows($(this));
                $(this).empty();
            });
    }

    function init() {
        if (!state.initialized) {
            bindEvents();
            state.initialized = true;
        }
        initTable();
    }

    window.membershipBusinessName = { init: init, reload: reloadTable };
    $(document).ready(init);
})(jQuery);
