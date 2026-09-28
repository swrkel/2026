/**
 * IS1618 Membership Settings repair script.
 * Fixes Point Setting add modal, Business Name add modal, Card Setting add modal,
 * and Card Setting action buttons without changing unrelated module flows.
 */
(function ($) {
    'use strict';

    if (!$) { return; }

    function notify(type, message) {
        message = message || ((window.LANG && LANG.something_went_wrong) || 'Something went wrong');
        if (window.toastr && typeof toastr[type] === 'function') {
            toastr[type](message);
        } else if (type === 'error') {
            alert(message);
        }
    }

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val() || '';
    }

    function ajaxError(xhr) {
        var msg = ((window.LANG && LANG.something_went_wrong) || 'Something went wrong');
        if (xhr && xhr.responseJSON) {
            msg = xhr.responseJSON.message || xhr.responseJSON.msg || msg;
            if (xhr.responseJSON.errors) {
                var errors = [];
                $.each(xhr.responseJSON.errors, function (k, values) {
                    if ($.isArray(values)) { errors = errors.concat(values); }
                    else if (values) { errors.push(values); }
                });
                if (errors.length) { msg = errors.join('<br>'); }
            }
        }
        notify('error', msg);
    }

    function showModal($modal) {
        if (!$modal.length) { return; }
        if (typeof $.fn.modal === 'function') {
            $modal.modal('show');
            return;
        }
        $modal.addClass('in show').show().attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        if (!$('.modal-backdrop').length) {
            $('<div class="modal-backdrop fade in show"></div>').appendTo(document.body);
        }
    }

    function hideModal($modal) {
        if (!$modal.length) { return; }
        if (typeof $.fn.modal === 'function') {
            $modal.modal('hide');
        } else {
            $modal.removeClass('in show').hide().attr('aria-hidden', 'true');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    }

    function loadModal(url, modalSelector) {
        var $modal = $(modalSelector);
        if (!url || !$modal.length) { return; }
        $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:30px;"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
        showModal($modal);
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            cache: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                $modal.html(html);
                showModal($modal);
                if (typeof $.fn.select2 === 'function') {
                    $modal.find('.select2').each(function () {
                        var $select = $(this);
                        if ($select.data('select2')) { $select.select2('destroy'); }
                        $select.select2({ dropdownParent: $modal });
                    });
                }
                refreshCardPreview($modal);
            },
            error: function (xhr) {
                hideModal($modal);
                ajaxError(xhr);
            }
        });
    }

    function reloadTable(selector) {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    }

    function submitAjaxForm($form, modalSelector, tableSelector) {
        var $submit = $form.find('button[type="submit"], input[type="submit"]').first();
        var original = $submit.length ? $submit.html() : '';
        var method = ($form.find('input[name="_method"]').val() || $form.attr('method') || 'POST').toUpperCase();
        $submit.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            method: method,
            url: $form.attr('action'),
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.success) {
                    notify('success', res.msg || 'Saved successfully');
                    hideModal($(modalSelector));
                    reloadTable(tableSelector);
                    return;
                }
                notify('error', (res && res.msg) || ((window.LANG && LANG.something_went_wrong) || 'Something went wrong'));
            },
            error: ajaxError,
            complete: function () { $submit.prop('disabled', false).html(original); }
        });
    }

    function deleteUrl(url, tableSelector) {
        if (!url) { return; }
        var run = function () {
            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                data: { _token: csrf(), _method: 'DELETE' },
                success: function (res) {
                    if (res && res.success) {
                        notify('success', res.msg || 'Deleted successfully');
                        reloadTable(tableSelector);
                    } else {
                        notify('error', (res && res.msg) || ((window.LANG && LANG.something_went_wrong) || 'Something went wrong'));
                    }
                },
                error: ajaxError
            });
        };
        if (typeof swal === 'function') {
            swal({ title: (window.LANG && LANG.sure) || 'Are you sure?', icon: 'warning', buttons: true, dangerMode: true }).then(function (ok) { if (ok) { run(); } });
        } else if (window.confirm('Are you sure?')) { run(); }
    }

    function initTable(selector, options) {
        if (!$(selector).length || !$.fn.DataTable) { return; }
        if ($.fn.DataTable.isDataTable(selector)) { return; }
        $(selector).DataTable(options);
    }

    function initPointSettingsTable() {
        initTable('#membership_point_settings_table', {
            processing: true,
            serverSide: true,
            ajax: window.MembershipPointSettingsConfig ? window.MembershipPointSettingsConfig.tableUrl : '/membership/setting/point-settings',
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
            ],
            drawCallback: function () { $('.dropdown-toggle').dropdown && $('.dropdown-toggle').dropdown(); }
        });
    }

    function initBusinessNamesTable() {
        var cfg = window.membershipBusinessNameConfig || {};
        initTable('#business_names_table', {
            processing: true,
            serverSide: true,
            ajax: { url: cfg.listUrl || '/membership/setting/business-name/data' },
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

    function initCardSettingsTable() {
        initTable('#membership_card_settings_table', {
            processing: true,
            serverSide: true,
            ajax: '/membership/setting/card-settings',
            order: [[1, 'desc']],
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-nowrap' },
                { data: 'date_time', name: 'membership_card_settings.created_at' },
                { data: 'length', name: 'membership_card_settings.length', className: 'text-right' },
                { data: 'width', name: 'membership_card_settings.width', className: 'text-right' },
                { data: 'size_details', name: 'size_details', orderable: false, searchable: false },
                { data: 'card_sample', name: 'card_sample', orderable: false, searchable: false },
                { data: 'added_by', name: 'added_by_name' }
            ],
            drawCallback: function () { $('.dropdown-toggle').dropdown && $('.dropdown-toggle').dropdown(); }
        });
    }

    function refreshCardPreview($scope) {
        $scope = $scope && $scope.length ? $scope : $(document);
        var l = parseFloat($scope.find('input[name="length"]').val() || 0);
        var w = parseFloat($scope.find('input[name="width"]').val() || 0);
        var wrapper = $scope.find('#add_form_card_sample_wrapper, #edit_form_card_sample_wrapper');
        var box = $scope.find('#add_form_card_sample_box, #edit_form_card_sample_box');
        var label = $scope.find('#add_form_card_sample_label, #edit_form_card_sample_label');
        if (!l || !w || l <= 0 || w <= 0) { wrapper.hide(); return; }
        var maxPx = 120;
        var maxMm = Math.max(l, w);
        box.css({ width: Math.round(maxPx * (w / maxMm)) + 'px', height: Math.round(maxPx * (l / maxMm)) + 'px' });
        label.text(l.toFixed(2) + ' mm × ' + w.toFixed(2) + ' mm');
        wrapper.show();
    }

    function bindEvents() {
        $(document)
            .off('click.is1618.pointAdd click.is1618.pointModal')
            .on('click.is1618.pointAdd', '.membership-point-setting-modal, .point_setting_modal_btn, [data-container=".point_setting_modal"]', function (e) {
                e.preventDefault();
                loadModal($(this).data('href') || $(this).attr('href'), '.point_setting_modal');
            })
            .on('click.is1618.businessAdd', '#open_add_business_name_modal, .membership-business-name-ajax, [data-container=".business_name_modal"]', function (e) {
                e.preventDefault();
                loadModal($(this).data('href') || $(this).attr('href') || '/membership/setting/business-names/create', '.business_name_modal');
            })
            .on('click.is1618.cardAdd', '#add_card_setting_btn, .membership-card-setting-modal-trigger, [data-container=".card_setting_modal"]', function (e) {
                e.preventDefault();
                loadModal($(this).data('href') || $(this).attr('href') || '/membership/setting/card-settings/create', '.card_setting_modal');
            })
            .on('submit.is1618.pointForm', '#add_point_setting_form, #edit_point_setting_form', function (e) {
                e.preventDefault();
                submitAjaxForm($(this), '.point_setting_modal', '#membership_point_settings_table');
            })
            .on('submit.is1618.businessForm', '#add_business_name_form, #edit_business_name_form', function (e) {
                e.preventDefault();
                submitAjaxForm($(this), '.business_name_modal', '#business_names_table');
            })
            .on('submit.is1618.cardForm', '#add_card_setting_form, #edit_card_setting_form', function (e) {
                e.preventDefault();
                submitAjaxForm($(this), '.card_setting_modal', '#membership_card_settings_table');
            })
            .on('click.is1618.pointDelete', '.delete_point_setting_btn', function (e) {
                e.preventDefault();
                deleteUrl($(this).data('href') || $(this).attr('href'), '#membership_point_settings_table');
            })
            .on('click.is1618.businessDelete', '.delete_business_name_btn', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url = $(this).data('href') || ((window.membershipBusinessNameConfig && window.membershipBusinessNameConfig.deleteUrlBase) ? window.membershipBusinessNameConfig.deleteUrlBase + '/' + id : '/membership/setting/business-names/' + id);
                deleteUrl(url, '#business_names_table');
            })
            .on('click.is1618.cardDelete', '.delete_card_setting_btn', function (e) {
                e.preventDefault();
                deleteUrl($(this).data('href') || $(this).attr('href'), '#membership_card_settings_table');
            })
            .on('click.is1618.close', '.point_setting_modal [data-dismiss="modal"], .business_name_modal [data-dismiss="modal"], .card_setting_modal [data-dismiss="modal"], .point_setting_modal [data-membership-dismiss="modal"], .business_name_modal [data-membership-dismiss="modal"], .card_setting_modal [data-membership-dismiss="modal"], .point_setting_modal .close, .business_name_modal .close, .card_setting_modal .close', function (e) {
                e.preventDefault();
                hideModal($(this).closest('.modal'));
            })
            .on('click.is1618.businessRowAdd', '.add-business-name-row', function (e) {
                e.preventDefault();
                var html = '<div class="form-group business-name-row"><label>Business Name:</label><div class="input-group"><input type="text" name="business_names[]" class="form-control business-name-input" autocomplete="off" required><span class="input-group-btn"><button type="button" class="btn btn-danger remove-business-name-row" style="height:34px;"><i class="fa fa-minus"></i></button></span></div></div>';
                $('#business_name_rows_container').append(html);
            })
            .on('click.is1618.businessEditRowAdd', '.add-edit-business-name-row', function (e) {
                e.preventDefault();
                var html = '<div class="form-group business-name-edit-row"><label>Business Name:</label><div class="input-group"><input type="text" name="additional_business_names[]" class="form-control business-name-edit-input" autocomplete="off"><span class="input-group-btn"><button type="button" class="btn btn-danger remove-edit-business-name-row" style="height:34px;"><i class="fa fa-minus"></i></button></span></div></div>';
                $('#edit_business_name_rows_container').append(html);
            })
            .on('click.is1618.businessRowRemove', '.remove-business-name-row, .remove-edit-business-name-row', function (e) {
                e.preventDefault();
                $(this).closest('.business-name-row, .business-name-edit-row').remove();
            })
            .on('input.is1618.cardPreview change.is1618.cardPreview', '.card_setting_modal input[name="length"], .card_setting_modal input[name="width"]', function () {
                refreshCardPreview($(this).closest('.modal'));
            });
    }

    function init() {
        bindEvents();
        initPointSettingsTable();
        initBusinessNamesTable();
        initCardSettingsTable();
    }

    window.membershipSettingsIS1618 = { init: init };
    $(document).ready(init);
})(window.jQuery);
