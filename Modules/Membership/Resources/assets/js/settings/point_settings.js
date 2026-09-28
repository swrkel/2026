(function ($) {
    'use strict';

    var table = null;

    function cfg() { return window.MembershipPointSettingsConfig || {}; }

    function notifySuccess(msg) { if (typeof toastr !== 'undefined') { toastr.success(msg); } }
    function notifyError(msg) { if (typeof toastr !== 'undefined') { toastr.error(msg); } else { alert(msg); } }

    function modalInstance($modal) {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function' && $modal.length) {
            return window.bootstrap.Modal.getOrCreateInstance($modal[0]);
        }
        return null;
    }

    function showPointModal() {
        var $modal = $('.point_setting_modal');
        var bs5 = modalInstance($modal);
        if (bs5) { bs5.show(); return; }
        if (typeof $.fn.modal === 'function') { $modal.modal('show'); return; }
        $modal.addClass('in show').show().attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        if (!$('.modal-backdrop').length) {
            $('<div class="modal-backdrop fade in show"></div>').appendTo(document.body);
        }
    }

    function hidePointModal() {
        var $modal = $('.point_setting_modal');
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

    function initTable() {
        if (!$('#membership_point_settings_table').length || typeof $.fn.DataTable !== 'function') { return; }
        if ($.fn.DataTable.isDataTable('#membership_point_settings_table')) {
            table = $('#membership_point_settings_table').DataTable();
            return;
        }

        table = $('#membership_point_settings_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: cfg().tableUrl,
            order: [[1, 'desc']],
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'date_time', name: 'created_at' },
                { data: 'business_type_name', name: 'businessType.business_type' },
                { data: 'reward_point_percent', name: 'reward_point_percent' },
                { data: 'min_bill_total_to_earn', name: 'min_bill_total_to_earn' },
                { data: 'max_points_per_bill', name: 'max_points_per_bill' },
                { data: 'min_bill_total_to_redeem', name: 'min_bill_total_to_redeem' },
                { data: 'min_redeem_point', name: 'min_redeem_point' },
                { data: 'max_redeem_point_per_bill', name: 'max_redeem_point_per_bill' },
                { data: 'expiry_period', name: 'expiry_period', orderable: false, searchable: false },
                { data: 'added_by', name: 'createdBy.first_name' }
            ]
        });
    }

    function reloadTable() { if (table) { table.ajax.reload(null, false); } }

    function openModal(url) {
        if (!url) { return; }
        var $modal = $('.point_setting_modal');
        $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
        showPointModal();

        $.ajax({
            method: 'GET',
            url: url,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                $modal.html(html);
                showPointModal();
                initSelect2($modal);
            },
            error: function () {
                hidePointModal();
                notifyError((window.LANG && LANG.something_went_wrong) || 'Something went wrong');
            }
        });
    }

    function submitPointSetting($form) {
        var $submit = $form.find('[type="submit"]');
        var original = $submit.html();
        var method = $form.find('input[name="_method"]').val() || $form.attr('method') || 'POST';

        $submit.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            method: method,
            url: $form.attr('action'),
            dataType: 'json',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            success: function (result) {
                if (result && result.success === true) {
                    notifySuccess(result.msg || 'Saved successfully');
                    hidePointModal();
                    reloadTable();
                    return;
                }
                notifyError((result && result.msg) || ((window.LANG && LANG.something_went_wrong) || 'Something went wrong'));
            },
            error: function (xhr) {
                var msg = (window.LANG && LANG.something_went_wrong) || 'Something went wrong';
                if (xhr.responseJSON) { msg = xhr.responseJSON.message || xhr.responseJSON.msg || msg; }
                notifyError(msg);
            },
            complete: function () { $submit.prop('disabled', false).html(original); }
        });
    }

    function deletePointSetting(url) {
        $.ajax({
            method: 'POST',
            url: url,
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            data: { _token: cfg().csrfToken, _method: 'DELETE' },
            success: function (result) {
                if (result && result.success === true) {
                    notifySuccess(result.msg || 'Deleted successfully');
                    reloadTable();
                    return;
                }
                notifyError((result && result.msg) || ((window.LANG && LANG.something_went_wrong) || 'Something went wrong'));
            },
            error: function () { notifyError((window.LANG && LANG.something_went_wrong) || 'Something went wrong'); }
        });
    }

    function bindEvents() {
        $(document)
            .off('click.membershipPointSettings.open', '.membership-point-setting-modal, .point_setting_modal_btn, [data-container=".point_setting_modal"]')
            .on('click.membershipPointSettings.open', '.membership-point-setting-modal, .point_setting_modal_btn, [data-container=".point_setting_modal"]', function (e) {
                e.preventDefault();
                openModal($(this).data('href') || $(this).attr('href'));
            });

        $(document)
            .off('submit.membershipPointSettings.form', 'form#add_point_setting_form, form#edit_point_setting_form')
            .on('submit.membershipPointSettings.form', 'form#add_point_setting_form, form#edit_point_setting_form', function (e) {
                e.preventDefault();
                submitPointSetting($(this));
            });

        $(document)
            .off('click.membershipPointSettings.close', '.point_setting_modal [data-dismiss="modal"], .point_setting_modal [data-bs-dismiss="modal"], .point_setting_modal .close')
            .on('click.membershipPointSettings.close', '.point_setting_modal [data-dismiss="modal"], .point_setting_modal [data-bs-dismiss="modal"], .point_setting_modal .close', function (e) {
                e.preventDefault();
                hidePointModal();
            });

        $(document)
            .off('hidden.bs.modal.membershipPointSettings', '.point_setting_modal')
            .on('hidden.bs.modal.membershipPointSettings', '.point_setting_modal', function () { $(this).empty(); });

        $(document)
            .off('click.membershipPointSettings.delete', '.delete_point_setting_btn')
            .on('click.membershipPointSettings.delete', '.delete_point_setting_btn', function (e) {
                e.preventDefault();
                var url = $(this).data('href');
                if (!url) { return; }
                var run = function () { deletePointSetting(url); };
                if (typeof swal === 'function') {
                    swal({ title: (window.LANG && LANG.sure) || 'Are you sure?', icon: 'warning', buttons: true, dangerMode: true }).then(function (ok) { if (ok) { run(); } });
                } else if (window.confirm('Are you sure?')) { run(); }
            });
    }

    function init() {
        initTable();
        bindEvents();
    }

    window.membershipPointSettings = { init: init, reload: reloadTable };
    $(document).ready(init);
})(jQuery);
