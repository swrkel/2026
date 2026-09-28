(function ($) {
    'use strict';

    var cfg = window.MembershipPrefixStartingNumberConfig || {};
    var texts = cfg.texts || {};
    var editingId = null;

    function getModal() {
        return $('#mem_prefix_modal');
    }

    function resetForm() {
        editingId = null;
        $('#mem_prefix_editing_id').val('');
        $('#mem_prefix_form')[0].reset();
        $('.mem-prefix-title-add').removeClass('hide').show();
        $('.mem-prefix-title-edit').addClass('hide').hide();
    }

    function openModal() {
        var $modal = getModal();
        if ($.fn.modal) {
            $modal.modal('show');
        } else {
            $modal.addClass('in').show();
            $('body').addClass('modal-open');
        }
    }

    function closeModal() {
        var $modal = getModal();
        if ($.fn.modal) {
            $modal.modal('hide');
        } else {
            $modal.removeClass('in').hide();
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }
    }

    function reloadActiveTab() {
        try {
            var activeTabPane = $('.tab-pane.active').attr('id');
            if (activeTabPane && window.sessionStorage) {
                sessionStorage.setItem('membershipActiveTab:' + window.location.pathname, activeTabPane);
            }
        } catch (e) {}
        window.location.reload();
    }

    function showError(xhr) {
        var message = texts.somethingWrong || 'Something went wrong';
        if (xhr && xhr.responseJSON) {
            message = xhr.responseJSON.message || xhr.responseJSON.msg || message;
        }
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
    }

    $(document)
        .off('click.memPrefixAdd', '#mem_prefix_add_btn')
        .on('click.memPrefixAdd', '#mem_prefix_add_btn', function (e) {
            e.preventDefault();
            resetForm();
            openModal();
        });

    $(document)
        .off('click.memPrefixClose', '.mem-prefix-close')
        .on('click.memPrefixClose', '.mem-prefix-close', function (e) {
            e.preventDefault();
            closeModal();
        });

    $(document)
        .off('click.memPrefixEdit', '.mem_prefix_edit_btn')
        .on('click.memPrefixEdit', '.mem_prefix_edit_btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            if (!id) return;

            $.ajax({
                url: cfg.baseUrl + '/' + id + '/edit',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    editingId = id;
                    $('#mem_prefix_editing_id').val(id);
                    $('#mem_prefix_region').val(data.region || '');
                    $('#mem_prefix_prefix').val(data.prefix || '');
                    $('#mem_prefix_starting_number').val(data.starting_number || '');
                    $('.mem-prefix-title-add').addClass('hide').hide();
                    $('.mem-prefix-title-edit').removeClass('hide').show();
                    openModal();
                },
                error: showError
            });
        });

    $(document)
        .off('submit.memPrefixForm', '#mem_prefix_form')
        .on('submit.memPrefixForm', '#mem_prefix_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var url = cfg.storeUrl;
            var method = 'POST';
            var data = $form.serializeArray();

            if (editingId) {
                url = cfg.baseUrl + '/' + editingId;
                method = 'POST';
                data.push({name: '_method', value: 'PUT'});
            }
            data.push({name: '_token', value: cfg.csrfToken});

            $.ajax({
                method: method,
                url: url,
                dataType: 'json',
                data: $.param(data),
                success: function (result) {
                    if (result && result.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(result.msg || 'Saved successfully');
                        }
                        closeModal();
                        reloadActiveTab();
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error((result && result.msg) || texts.somethingWrong || 'Something went wrong');
                        }
                    }
                },
                error: showError
            });
        });

    $(document)
        .off('click.memPrefixDelete', '.mem_prefix_delete_btn')
        .on('click.memPrefixDelete', '.mem_prefix_delete_btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            if (!id) return;

            var runDelete = function () {
                $.ajax({
                    method: 'POST',
                    url: cfg.baseUrl + '/' + id,
                    dataType: 'json',
                    data: {_token: cfg.csrfToken, _method: 'DELETE'},
                    success: function (result) {
                        if (result && result.success) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(result.msg || 'Deleted successfully');
                            }
                            reloadActiveTab();
                        } else {
                            if (typeof toastr !== 'undefined') {
                                toastr.error((result && result.msg) || texts.somethingWrong || 'Something went wrong');
                            }
                        }
                    },
                    error: showError
                });
            };

            if (typeof swal === 'function') {
                swal({
                    title: (window.LANG && LANG.sure) || texts.sure || 'Are you sure?',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true
                }).then(function (confirmed) {
                    if (confirmed) runDelete();
                });
            } else if (confirm(texts.sure || 'Are you sure?')) {
                runDelete();
            }
        });

    $(function () {
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#mem_prefix_table')) {
            $('#mem_prefix_table').DataTable({
                aaSorting: [],
                responsive: true,
                language: window.LANG ? { search: window.LANG.search || 'Search:' } : {}
            });
        }
    });
})(jQuery);
