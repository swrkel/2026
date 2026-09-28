(function ($) {
    'use strict';

    function notify(type, message) {
        message = message || 'Something went wrong';
        if (window.toastr && toastr[type]) {
            toastr[type](message);
        } else if (type === 'error') {
            alert(message);
        }
    }

    function errorMessage(xhr) {
        var msg = (window.LANG && LANG.something_went_wrong) || 'Something went wrong';
        if (xhr && xhr.responseJSON) {
            msg = xhr.responseJSON.message || xhr.responseJSON.msg || msg;
            if (xhr.responseJSON.errors) {
                var errors = [];
                $.each(xhr.responseJSON.errors, function (field, values) {
                    if ($.isArray(values)) { errors = errors.concat(values); }
                    else if (values) { errors.push(values); }
                });
                if (errors.length) { msg = errors.join('<br>'); }
            }
        }
        return msg;
    }

    function showModal($modal, html, contentSelector) {
        if (!$modal.length) { return; }
        if (contentSelector) {
            $modal.find(contentSelector).html(html || '');
        } else {
            $modal.html(html || '');
        }

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            window.bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        } else if ($.fn.modal) {
            $modal.modal('show');
        } else {
            $modal.addClass('in show').show().attr('aria-hidden', 'false');
            $('body').addClass('modal-open');
            if (!$('.modal-backdrop').length) {
                $('<div class="modal-backdrop fade in show"></div>').appendTo(document.body);
            }
        }

        initSelect2($modal);
    }

    function hideModal($modal, contentSelector) {
        if (!$modal.length) { return; }
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            window.bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
        } else if ($.fn.modal) {
            $modal.modal('hide');
        } else {
            $modal.removeClass('in show').hide().attr('aria-hidden', 'true');
        }

        window.setTimeout(function () {
            if (contentSelector) { $modal.find(contentSelector).empty(); }
            else { $modal.empty(); }
            if (!$('.modal.in:visible, .modal.show:visible').length) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            }
        }, 200);
    }

    function initSelect2($container) {
        if (!$.fn.select2) { return; }
        $container.find('select.select2').each(function () {
            var $select = $(this);
            try { if ($select.data('select2')) { $select.select2('destroy'); } } catch (e) {}
            $select.select2({ dropdownParent: $container });
        });
    }

    function loadingHtml() {
        return '<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:30px;">' +
            '<i class="fa fa-spinner fa-spin fa-2x"></i><p style="margin-top:10px;">Loading...</p></div></div></div>';
    }

    function loadModal(url, $modal, contentSelector) {
        if (!url || !$modal.length) { return; }
        showModal($modal, loadingHtml(), contentSelector);
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            cache: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) { showModal($modal, html, contentSelector); },
            error: function (xhr) {
                hideModal($modal, contentSelector);
                notify('error', errorMessage(xhr));
            }
        });
    }

    function submitAjaxForm($form, $modal, contentSelector, reloaders) {
        var $submit = $form.find('[type="submit"]').last();
        var original = $submit.html();
        var method = $form.find('input[name="_method"]').val() || $form.attr('method') || 'POST';
        $submit.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            method: method,
            url: $form.attr('action'),
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            success: function (response) {
                if (response && response.success) {
                    notify('success', response.msg || 'Saved successfully');
                    hideModal($modal, contentSelector);
                    $.each(reloaders || [], function (_, fn) { try { fn(); } catch (e) {} });
                } else {
                    notify('error', (response && response.msg) || 'Something went wrong');
                }
            },
            error: function (xhr) { notify('error', errorMessage(xhr)); },
            complete: function () { $submit.prop('disabled', false).html(original); }
        });
    }

    function reloadPointTable() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#membership_point_settings_table')) {
            $('#membership_point_settings_table').DataTable().ajax.reload(null, false);
        }
    }

    function reloadBusinessNameTable() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#business_names_table')) {
            $('#business_names_table').DataTable().ajax.reload(null, false);
        }
    }

    function reloadCardTable() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#membership_card_settings_table')) {
            $('#membership_card_settings_table').DataTable().ajax.reload(null, false);
        }
    }

    function initCardTable() {
        if (!$('#membership_card_settings_table').length || !$.fn.DataTable) { return; }
        if ($.fn.DataTable.isDataTable('#membership_card_settings_table')) { return; }
        $('#membership_card_settings_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/membership/setting/card-settings',
            order: [[1, 'desc']],
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'date_time', name: 'membership_card_settings.created_at' },
                { data: 'length', name: 'membership_card_settings.length', className: 'text-right' },
                { data: 'width', name: 'membership_card_settings.width', className: 'text-right' },
                { data: 'size_details', name: 'size_details', orderable: false, searchable: false },
                { data: 'card_sample', name: 'card_sample', orderable: false, searchable: false },
                { data: 'added_by', name: 'added_by_name' }
            ]
        });
    }

    function bindMembershipSettingsFinal() {
        // Remove older partial handlers for these exact controls to prevent double AJAX calls.
        $(document).off('click', '.membership-point-setting-modal, [data-container=".point_setting_modal"]');
        $(document).off('submit', '#add_point_setting_form, #edit_point_setting_form');
        $(document).off('click', '#open_add_business_name_modal, .membership-business-name-ajax');
        $(document).off('submit', '#add_business_name_form, #edit_business_name_form');
        $(document).off('click', '#add_card_setting_btn');
        $(document).off('click', '.membership-card-setting-modal-trigger');
        $(document).off('submit', '#add_card_setting_form, #edit_card_setting_form');

        $(document)
            .off('click.membershipFinal.pointAdd', '.membership-point-setting-modal, [data-container=".point_setting_modal"]')
            .on('click.membershipFinal.pointAdd', '.membership-point-setting-modal, [data-container=".point_setting_modal"]', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadModal($(this).data('href') || $(this).attr('href'), $('.point_setting_modal'));
            });

        $(document)
            .off('submit.membershipFinal.pointForm', '#add_point_setting_form, #edit_point_setting_form')
            .on('submit.membershipFinal.pointForm', '#add_point_setting_form, #edit_point_setting_form', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                submitAjaxForm($(this), $('.point_setting_modal'), null, [reloadPointTable]);
            });

        $(document)
            .off('click.membershipFinal.businessAdd', '#open_add_business_name_modal, .membership-business-name-ajax')
            .on('click.membershipFinal.businessAdd', '#open_add_business_name_modal, .membership-business-name-ajax', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadModal($(this).data('href') || (window.membershipBusinessNameConfig && window.membershipBusinessNameConfig.createUrl), $('.business_name_modal'));
            });

        $(document)
            .off('submit.membershipFinal.businessForm', '#add_business_name_form, #edit_business_name_form')
            .on('submit.membershipFinal.businessForm', '#add_business_name_form, #edit_business_name_form', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                submitAjaxForm($(this), $('.business_name_modal'), null, [reloadBusinessNameTable]);
            });

        $(document)
            .off('click.membershipFinal.businessRowAdd', '.add-business-name-row')
            .on('click.membershipFinal.businessRowAdd', '.add-business-name-row', function (e) {
                e.preventDefault();
                var html = '<div class="form-group business-name-row"><label>Business Name:</label><div class="input-group">' +
                    '<input type="text" name="business_names[]" class="form-control business-name-input" autocomplete="off" required>' +
                    '<span class="input-group-btn"><button type="button" class="btn btn-danger remove-business-name-row" style="height:34px;"><i class="fa fa-minus"></i></button></span>' +
                    '</div></div>';
                $('#business_name_rows_container').append(html);
            });

        $(document)
            .off('click.membershipFinal.businessRowRemove', '.remove-business-name-row')
            .on('click.membershipFinal.businessRowRemove', '.remove-business-name-row', function (e) {
                e.preventDefault();
                $(this).closest('.business-name-row').remove();
            });

        $(document)
            .off('click.membershipFinal.cardAdd', '#add_card_setting_btn')
            .on('click.membershipFinal.cardAdd', '#add_card_setting_btn', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadModal($(this).data('href') || '/membership/setting/card-settings/create', $('.card_setting_modal'), '#card_setting_modal_content');
            });

        $(document)
            .off('click.membershipFinal.cardModal', '.membership-card-setting-modal-trigger')
            .on('click.membershipFinal.cardModal', '.membership-card-setting-modal-trigger', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                loadModal($(this).data('href') || $(this).attr('href'), $('.card_setting_modal'), '#card_setting_modal_content');
            });

        $(document)
            .off('submit.membershipFinal.cardForm', '#add_card_setting_form, #edit_card_setting_form')
            .on('submit.membershipFinal.cardForm', '#add_card_setting_form, #edit_card_setting_form', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                submitAjaxForm($(this), $('.card_setting_modal'), '#card_setting_modal_content', [reloadCardTable]);
            });


        $(document)
            .off('click.membershipFinal.cardDelete', '.delete_card_setting_btn')
            .on('click.membershipFinal.cardDelete', '.delete_card_setting_btn', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var url = $(this).data('href') || $(this).attr('href');
                if (!url) { return; }
                var runDelete = function () {
                    $.ajax({
                        method: 'POST',
                        url: url,
                        dataType: 'json',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') || (window.Laravel && window.Laravel.csrfToken) || '' },
                        success: function (response) {
                            if (response && response.success) {
                                notify('success', response.msg || 'Deleted successfully');
                                reloadCardTable();
                            } else {
                                notify('error', (response && response.msg) || 'Something went wrong');
                            }
                        },
                        error: function (xhr) { notify('error', errorMessage(xhr)); }
                    });
                };
                if (typeof swal === 'function') {
                    swal({ title: (window.LANG && LANG.sure) || 'Are you sure?', icon: 'warning', buttons: true, dangerMode: true }).then(function (ok) { if (ok) { runDelete(); } });
                } else if (window.confirm('Are you sure?')) { runDelete(); }
            });

        $(document)
            .off('click.membershipFinal.modalClose', '.point_setting_modal [data-dismiss="modal"], .business_name_modal [data-dismiss="modal"], .card_setting_modal [data-dismiss="modal"], .point_setting_modal .close, .business_name_modal .close, .card_setting_modal .close, .membership-card-setting-close')
            .on('click.membershipFinal.modalClose', '.point_setting_modal [data-dismiss="modal"], .business_name_modal [data-dismiss="modal"], .card_setting_modal [data-dismiss="modal"], .point_setting_modal .close, .business_name_modal .close, .card_setting_modal .close, .membership-card-setting-close', function (e) {
                e.preventDefault();
                var $modal = $(this).closest('.modal');
                hideModal($modal, $modal.is('.card_setting_modal') ? '#card_setting_modal_content' : null);
            });
    }

    $(function () {
        bindMembershipSettingsFinal();
        initCardTable();
        $(document).off('shown.bs.tab.membershipFinalReinit').on('shown.bs.tab.membershipFinalReinit', 'a[data-toggle="tab"][href="#card_settings_tab"]', function () { initCardTable(); reloadCardTable(); });
        $(document).off('shown.bs.tab.membershipFinalPointReinit').on('shown.bs.tab.membershipFinalPointReinit', 'a[data-toggle="tab"][href="#point_settings_tab"]', function () { if (window.membershipPointSettings) { window.membershipPointSettings.init(); window.membershipPointSettings.reload(); } });
        $(document).off('shown.bs.tab.membershipFinalBusinessReinit').on('shown.bs.tab.membershipFinalBusinessReinit', 'a[data-toggle="tab"][href="#business_name_tab"]', function () { if (window.membershipBusinessName) { window.membershipBusinessName.init(); window.membershipBusinessName.reload(); } });
    });
})(jQuery);
