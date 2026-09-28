(function ($) {
    'use strict';

    var table = null;
    var selectors = {
        table: '#membership_authorized_signatures_table',
        addButton: '#membership_add_signature_btn',
        modal: '.membership_authorized_signature_modal',
        modalContent: '#membership_authorized_signature_modal_content',
        createTemplate: '#membership_authorized_signature_create_template',
        loadModal: '.membership-signature-load-modal',
        closeModal: '.membership-signature-modal-close',
        deleteButton: '.membership-signature-delete',
        forms: '#membership_authorized_signature_create_form, #membership_authorized_signature_edit_form',
        fileInputs: '#membership_authorized_signature_create_file, #membership_authorized_signature_edit_file'
    };

    function csrfHeaders() {
        var token = $('meta[name="csrf-token"]').attr('content');
        return token ? {'X-CSRF-TOKEN': token} : {};
    }

    function initDataTable() {
        if (!$(selectors.table).length || table !== null) {
            return;
        }

        table = $(selectors.table).DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: window.membershipAuthorizedSignatureRoutes.index,
            columns: [
                {data: 'action', name: 'action', orderable: false, searchable: false},
                {data: 'date_time', name: 'created_at'},
                {data: 'status', name: 'is_active', searchable: false},
                {data: 'signature_preview', name: 'signature_preview', orderable: false, searchable: false},
                {data: 'added_by', name: 'createdBy.first_name'}
            ],
            columnDefs: [
                {targets: 0, width: '260px'},
                {targets: 2, width: '120px', className: 'text-center'},
                {targets: 4, width: '180px'}
            ],
            order: [[1, 'desc']]
        });
    }

    function reloadTable() {
        if (table !== null) {
            table.ajax.reload(null, false);
        }
    }

    function openModal(html) {
        $(selectors.modalContent).html(html);
        $(selectors.modal).modal('show');
    }

    function closeModal() {
        $(selectors.modal).modal('hide');
        $(selectors.modalContent).empty();
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    function loadingModal() {
        return '<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:30px;"><i class="fa fa-spinner fa-spin fa-3x"></i><p style="margin-top:12px;">Loading...</p></div></div></div>';
    }

    function previewSelectedFile(input) {
        var file = input.files && input.files.length ? input.files[0] : null;
        var $modal = $(input).closest('.modal-content');
        var $container = $modal.find('.membership-authorized-signature-preview-container');
        var $image = $modal.find('.membership-authorized-signature-preview-image');
        var $info = $modal.find('.membership-authorized-signature-file-info');

        $image.hide().attr('src', '');
        $info.hide().html('');

        if (!file) {
            $container.hide();
            return;
        }

        if (file.type && file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function (event) {
                $image.attr('src', event.target.result).show();
                $info.hide();
                $container.show();
            };
            reader.readAsDataURL(file);
            return;
        }

        $info.html('<strong>' + file.name + '</strong><br><small>' + (file.size / 1024).toFixed(2) + ' KB</small>').show();
        $image.hide();
        $container.show();
    }

    $(document).ready(function () {
        initDataTable();
    });

    $(document).off('click.membershipSignatureAdd', selectors.addButton)
        .on('click.membershipSignatureAdd', selectors.addButton, function (event) {
            event.preventDefault();
            openModal($(selectors.createTemplate).html());
        });

    $(document).off('click.membershipSignatureLoad', selectors.loadModal)
        .on('click.membershipSignatureLoad', selectors.loadModal, function (event) {
            event.preventDefault();
            var $button = $(this);
            var url = $button.data('href');

            if (!url) {
                return;
            }

            $button.prop('disabled', true);
            openModal(loadingModal());

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'html',
                cache: false,
                success: function (html) {
                    openModal(html);
                },
                error: function () {
                    closeModal();
                    toastr.error('Failed to load form. Please try again.');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

    $(document).off('click.membershipSignatureClose', selectors.closeModal)
        .on('click.membershipSignatureClose', selectors.closeModal, function (event) {
            event.preventDefault();
            closeModal();
        });

    $(document).off('hidden.bs.modal.membershipSignature', selectors.modal)
        .on('hidden.bs.modal.membershipSignature', selectors.modal, function () {
            $(selectors.modalContent).empty();
        });

    $(document).off('change.membershipSignaturePreview', selectors.fileInputs)
        .on('change.membershipSignaturePreview', selectors.fileInputs, function () {
            previewSelectedFile(this);
        });

    $(document).off('submit.membershipSignatureSave', selectors.forms)
        .on('submit.membershipSignatureSave', selectors.forms, function (event) {
            event.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var originalText = $submitButton.html();
            var formData = new FormData(this);
            var method = ($form.find('input[name="_method"]').val() || $form.attr('method') || 'POST').toUpperCase();

            $submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                method: method === 'PUT' ? 'POST' : method,
                url: $form.attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                headers: csrfHeaders(),
                success: function (result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                        closeModal();
                        reloadTable();
                    } else {
                        toastr.error(result.msg || 'Something went wrong.');
                    }
                },
                error: function (xhr) {
                    var message = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    }
                    toastr.error(message);
                },
                complete: function () {
                    $submitButton.prop('disabled', false).html(originalText);
                }
            });
        });

    $(document).off('click.membershipSignatureDelete', selectors.deleteButton)
        .on('click.membershipSignatureDelete', selectors.deleteButton, function (event) {
            event.preventDefault();
            var url = $(this).data('href');

            if (!url) {
                return;
            }

            swal({
                title: LANG && LANG.sure ? LANG.sure : 'Are you sure?',
                icon: 'warning',
                buttons: true,
                dangerMode: true
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: url,
                    dataType: 'json',
                    data: {_method: 'DELETE'},
                    headers: csrfHeaders(),
                    success: function (result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            reloadTable();
                        } else {
                            toastr.error(result.msg || 'Something went wrong.');
                        }
                    },
                    error: function () {
                        toastr.error('Something went wrong.');
                    }
                });
            });
        });
})(jQuery);
