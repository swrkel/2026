<script type="text/javascript">
(function ($) {
    'use strict';

    var tableInstance = null;
    var selectors = {
        table: '#membership_card_settings_table',
        modal: '.card_setting_modal',
        modalContent: '#card_setting_modal_content',
        addButton: '#add_card_setting_btn'
    };

    function showModal(html) {
        $(selectors.modalContent).html(html || '');
        $(selectors.modal).modal('show');
        setTimeout(function () {
            refreshCardSample($(selectors.modal));
        }, 100);
    }

    function closeModal() {
        $(selectors.modal).modal('hide');
        $(selectors.modalContent).html('');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }

    function renderLoadingModal() {
        showModal('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:30px;">' +
            '<i class="fa fa-spinner fa-spin fa-3x"></i><p style="margin-top:10px;">Loading...</p></div></div></div>');
    }

    function renderCardSample(length, width, $scope) {
        var l = parseFloat(String(length || '').replace(/,/g, ''));
        var w = parseFloat(String(width || '').replace(/,/g, ''));
        var $wrapper = $scope.find('.membership-card-sample-wrapper');
        var $box = $scope.find('.membership-card-sample-box');
        var $label = $scope.find('.membership-card-sample-label');

        if (!l || !w || l <= 0 || w <= 0) {
            $wrapper.hide();
            return;
        }

        var maxPx = 120;
        var maxMm = Math.max(l, w);
        $box.css({
            width: Math.round(maxPx * (w / maxMm)) + 'px',
            height: Math.round(maxPx * (l / maxMm)) + 'px'
        });
        $label.text(l.toFixed(2) + ' mm × ' + w.toFixed(2) + ' mm');
        $wrapper.show();
    }

    function refreshCardSample($scope) {
        renderCardSample(
            $scope.find('input[name="length"]').val(),
            $scope.find('input[name="width"]').val(),
            $scope
        );
    }

    function reloadTable() {
        if (tableInstance) {
            tableInstance.ajax.reload(null, false);
        }
    }

    function getErrorMessage(xhr) {
        var msg = 'Something went wrong';
        if (xhr && xhr.responseJSON) {
            msg = xhr.responseJSON.msg || xhr.responseJSON.message || msg;
            if (xhr.responseJSON.errors) {
                var errors = [];
                $.each(xhr.responseJSON.errors, function (key, value) {
                    errors = errors.concat(value);
                });
                if (errors.length) {
                    msg = errors.join('<br>');
                }
            }
        }
        return msg;
    }

    function submitAjaxForm($form) {
        var $btn = $form.find('button[type="submit"]');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            method: 'POST',
            url: $form.attr('action'),
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                $btn.prop('disabled', false).html(originalHtml);
                if (response.success) {
                    toastr.success(response.msg);
                    closeModal();
                    reloadTable();
                } else {
                    toastr.error(response.msg || 'Something went wrong');
                }
            },
            error: function (xhr) {
                toastr.error(getErrorMessage(xhr));
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function initCardSettingsTable() {
        if (!$(selectors.table).length || $.fn.DataTable.isDataTable(selectors.table)) {
            tableInstance = $(selectors.table).DataTable();
            return;
        }

        tableInstance = $(selectors.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: '/membership/setting/card-settings',
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'date_time', name: 'membership_card_settings.created_at' },
                { data: 'length', name: 'membership_card_settings.length', className: 'text-right' },
                { data: 'width', name: 'membership_card_settings.width', className: 'text-right' },
                { data: 'size_details', name: 'size_details', orderable: false, searchable: false },
                { data: 'card_sample', name: 'card_sample', orderable: false, searchable: false },
                { data: 'added_by', name: 'added_by_name' }
            ],
            order: [[1, 'desc']]
        });
    }

    $(document)
        .off('click.membership_card_settings_add', selectors.addButton)
        .on('click.membership_card_settings_add', selectors.addButton, function (e) {
            e.preventDefault();
            renderLoadingModal();
            $.ajax({
                url: '/membership/setting/card-settings/create',
                dataType: 'html',
                cache: false,
                success: showModal,
                error: function (xhr) {
                    closeModal();
                    toastr.error(getErrorMessage(xhr));
                }
            });
        });

    $(document)
        .off('click.membership_card_settings_modal', '.membership-card-setting-modal-trigger')
        .on('click.membership_card_settings_modal', '.membership-card-setting-modal-trigger', function (e) {
            e.preventDefault();
            var url = $(this).data('href');
            renderLoadingModal();
            $.ajax({
                url: url,
                dataType: 'html',
                cache: false,
                success: showModal,
                error: function (xhr) {
                    closeModal();
                    toastr.error(getErrorMessage(xhr));
                }
            });
        });

    $(document)
        .off('click.membership_card_settings_close', '.membership-card-setting-close')
        .on('click.membership_card_settings_close', '.membership-card-setting-close', function (e) {
            e.preventDefault();
            closeModal();
        });

    $(document)
        .off('input.membership_card_settings_preview change.membership_card_settings_preview', '.membership-card-dimension')
        .on('input.membership_card_settings_preview change.membership_card_settings_preview', '.membership-card-dimension', function () {
            refreshCardSample($(this).closest('.modal-content'));
        });

    $(document)
        .off('submit.membership_card_settings_form', '#add_card_setting_form, #edit_card_setting_form')
        .on('submit.membership_card_settings_form', '#add_card_setting_form, #edit_card_setting_form', function (e) {
            e.preventDefault();
            submitAjaxForm($(this));
        });

    $(document)
        .off('click.membership_card_settings_delete', '.delete_card_setting_btn')
        .on('click.membership_card_settings_delete', '.delete_card_setting_btn', function (e) {
            e.preventDefault();
            var url = $(this).data('href');
            swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true }).then(function (ok) {
                if (!ok) {
                    return;
                }
                $.ajax({
                    method: 'DELETE',
                    url: url,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            reloadTable();
                        } else {
                            toastr.error(response.msg || 'Something went wrong');
                        }
                    },
                    error: function (xhr) {
                        toastr.error(getErrorMessage(xhr));
                    }
                });
            });
        });

    $(function () {
        initCardSettingsTable();
    });
})(jQuery);
</script>
