@extends('layouts.app')
@section('title', __('vat::lang.vat_invoice'))

@section('content')
<section class="content">
    <style>
        .vat-action-dropdown-fix .dropdown-menu {
            z-index: 99999 !important;
        }

        .vat-action-dropdown-fix.table-responsive,
        .vat-action-dropdown-fix .table-responsive,
        .vat-action-dropdown-fix .dataTables_wrapper,
        .vat-action-dropdown-fix div.dataTables_wrapper {
            overflow: visible !important;
        }

        .vat_invoice2_prefix_modal {
            z-index: 10550 !important;
        }

        .modal-backdrop {
            z-index: 10540 !important;
        }

        .vat-prefix-portal-menu {
            display: block !important;
            position: fixed !important;
            min-width: 150px;
            z-index: 999999 !important;
        }
    </style>

    <div class="row">
        @include('vat::vat_invoice2.partials.nav')
        @include('vat::vat_invoice2_prefixes.index')
    </div>

    <div class="modal fade vat_invoice2_prefix_modal"
         tabindex="-1"
         role="dialog"
         aria-labelledby="vatInvoice2PrefixModalTitle"></div>
</section>
@endsection

@section('javascript')
<script>
(function ($) {
    'use strict';

    var pageNamespace = '.s538VatInvoice2Prefix';
    var activeModalRequest = null;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function getMessageFromXhr(xhr) {
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                var messages = [];
                $.each(xhr.responseJSON.errors, function (field, fieldMessages) {
                    if ($.isArray(fieldMessages)) {
                        messages = messages.concat(fieldMessages);
                    } else if (fieldMessages) {
                        messages.push(fieldMessages);
                    }
                });

                if (messages.length) {
                    return messages.join('<br>');
                }
            }

            if (xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.responseJSON.msg) {
                return xhr.responseJSON.msg;
            }
        }

        return @json(__('messages.something_went_wrong'));
    }

    function cleanupModalBackdrop() {
        window.setTimeout(function () {
            if (!$('.modal.in:visible').length) {
                $('.modal-backdrop').remove();
                $('body')
                    .removeClass('modal-open')
                    .css({
                        'padding-right': '',
                        'overflow': ''
                    });
            }
        }, 0);
    }

    function restoreActionMenu($group) {
        var portal = $group.data('s538VatPrefixPortal');

        if (!portal) {
            $group.removeClass('open');
            return;
        }

        var $menu = portal.menu;
        var placeholder = portal.placeholder;

        if ($menu && $menu.length) {
            $menu
                .removeClass('vat-prefix-portal-menu')
                .removeAttr('style');

            if (placeholder && placeholder.parentNode) {
                $(placeholder).replaceWith($menu.detach());
            } else {
                $group.append($menu.detach());
            }
        }

        $group
            .removeData('s538VatPrefixPortal')
            .removeClass('open');
    }

    function closeAllActionMenus() {
        $('.vat-prefix-action-dropdown').each(function () {
            restoreActionMenu($(this));
        });

        $('body > .vat-prefix-portal-menu').each(function () {
            var $menu = $(this);
            var $owner = $menu.data('s538VatPrefixOwner');

            if ($owner && $owner.length) {
                restoreActionMenu($owner);
            } else {
                $menu.remove();
            }
        });
    }

    function portalActionMenu($group) {
        if ($group.data('s538VatPrefixPortal')) {
            restoreActionMenu($group);
            $group.addClass('open');
        }

        var $button = $group.find('.dropdown-toggle').first();
        var $menu = $group.find('.dropdown-menu').first();

        if (!$button.length || !$menu.length || !$button[0]) {
            return;
        }

        var placeholder = document.createComment('VAT Invoice2 prefix action menu');
        $menu.before(placeholder);

        var rect = $button[0].getBoundingClientRect();
        var menuWidth = Math.max($menu.outerWidth() || 150, 150);
        var left = rect.right - menuWidth;
        var viewportWidth = document.documentElement.clientWidth || window.innerWidth;

        left = Math.max(5, Math.min(left, viewportWidth - menuWidth - 5));

        $group.data('s538VatPrefixPortal', {
            menu: $menu,
            placeholder: placeholder
        });

        $menu
            .data('s538VatPrefixOwner', $group)
            .detach()
            .appendTo('body')
            .addClass('vat-prefix-portal-menu')
            .css({
                top: Math.max(rect.bottom, 5) + 'px',
                left: left + 'px',
                right: 'auto'
            });
    }

    function loadingModalHtml() {
        return ''
            + '<div class="modal-dialog" role="document">'
            + '  <div class="modal-content">'
            + '    <div class="modal-header">'
            + '      <button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            + '        <span aria-hidden="true">&times;</span>'
            + '      </button>'
            + '      <h4 class="modal-title"><i class="fa fa-spinner fa-spin"></i> Loading</h4>'
            + '    </div>'
            + '    <div class="modal-body text-center" style="padding:35px;">'
            + '      <i class="fa fa-spinner fa-spin"></i> Loading...'
            + '    </div>'
            + '  </div>'
            + '</div>';
    }

    function getPrefixModal() {
        var $modal = $('.vat_invoice2_prefix_modal').first();

        $modal
            .off('hidden.bs.modal' + pageNamespace)
            .on('hidden.bs.modal' + pageNamespace, function () {
                if (activeModalRequest && activeModalRequest.readyState !== 4) {
                    activeModalRequest.abort();
                }

                activeModalRequest = null;
                closeAllActionMenus();

                $(this)
                    .removeData('bs.modal')
                    .empty();

                cleanupModalBackdrop();
            });

        return $modal;
    }

    function submitPrefixForm(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]').first();

        if ($form.data('s538Submitting') || $submitButton.prop('disabled')) {
            return false;
        }

        $form.data('s538Submitting', true);
        $submitButton.prop('disabled', true);

        $.ajax({
            method: ($form.attr('method') || 'POST').toUpperCase(),
            url: $form.attr('action'),
            dataType: 'json',
            data: $form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            }
        })
        .done(function (result) {
            if (result && result.success === true) {
                $('.vat_invoice2_prefix_modal').modal('hide');
                toastr.success(result.msg || @json(__('messages.success')));

                if (window.prefixes_table && window.prefixes_table.ajax) {
                    window.prefixes_table.ajax.reload(null, false);
                }
            } else {
                toastr.error((result && result.msg) || @json(__('messages.something_went_wrong')));
            }
        })
        .fail(function (xhr) {
            toastr.error(getMessageFromXhr(xhr));
        })
        .always(function () {
            $form.removeData('s538Submitting');
            $submitButton.prop('disabled', false);
        });

        return false;
    }

    function initialisePrefixForm($modal) {
        if ($.fn.iCheck) {
            $modal.find('input.input-icheck').each(function () {
                var $input = $(this);

                if (!$input.parent().hasClass('icheckbox_square-blue')) {
                    $input.iCheck({
                        checkboxClass: 'icheckbox_square-blue'
                    });
                }
            });
        }

        $modal
            .find('#vat_invoice2_prefix_add_form, #vat_invoice2_prefix_edit_form')
            .off('submit' + pageNamespace)
            .on('submit' + pageNamespace, submitPrefixForm);
    }

    function openPrefixModal(trigger) {
        var $trigger = $(trigger);
        var url = $trigger.attr('data-href') || $trigger.data('href') || $trigger.attr('href');

        if (!url || url === '#') {
            return false;
        }

        closeAllActionMenus();

        if (activeModalRequest && activeModalRequest.readyState !== 4) {
            activeModalRequest.abort();
        }

        var $modal = getPrefixModal();
        $modal.html(loadingModalHtml());

        if (!$modal.hasClass('in')) {
            $modal.modal({
                backdrop: 'static',
                keyboard: true,
                show: true
            });
        }

        activeModalRequest = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .done(function (html) {
            $modal.html(html);
            initialisePrefixForm($modal);
            $modal.modal('handleUpdate');
        })
        .fail(function (xhr, status) {
            if (status === 'abort') {
                return;
            }

            $modal.modal('hide');
            toastr.error(getMessageFromXhr(xhr));
        })
        .always(function () {
            activeModalRequest = null;
        });

        return false;
    }

    function bindPrefixModalTriggers($scope) {
        $scope
            .find('.vat-invoice2-prefix-modal-trigger')
            .off('click' + pageNamespace)
            .on('click' + pageNamespace, function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return openPrefixModal(this);
            });
    }

    function deletePrefix(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var url = $(this).attr('data-href') || $(this).data('href') || $(this).attr('href');
        closeAllActionMenus();

        if (!url || url === '#') {
            return false;
        }

        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (willDelete) {
            if (!willDelete) {
                return;
            }

            $.ajax({
                method: 'DELETE',
                url: url,
                dataType: 'json',
                data: {
                    _token: csrfToken()
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken()
                }
            })
            .done(function (result) {
                if (result && result.success === true) {
                    toastr.success(result.msg || @json(__('messages.success')));
                } else {
                    toastr.error((result && result.msg) || @json(__('messages.something_went_wrong')));
                }

                if (window.prefixes_table && window.prefixes_table.ajax) {
                    window.prefixes_table.ajax.reload(null, false);
                }
            })
            .fail(function (xhr) {
                toastr.error(getMessageFromXhr(xhr));
            });
        });

        return false;
    }

    function bindPrefixDeleteButtons($scope) {
        $scope
            .find('.vat-prefix-delete')
            .off('click' + pageNamespace)
            .on('click' + pageNamespace, deletePrefix);
    }

    function bindPrefixRowActions() {
        var $table = $('#prefixes_table');
        bindPrefixModalTriggers($table);
        bindPrefixDeleteButtons($table);
    }

    function initPrefixTable() {
        if ($.fn.DataTable.isDataTable('#prefixes_table')) {
            $('#prefixes_table').DataTable().destroy();
        }

        window.prefixes_table = $('#prefixes_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: @json(action('\\Modules\\Vat\\Http\\Controllers\\VatInvoice2PrefixController@index'))
            },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'prefix', name: 'prefix' },
                { data: 'starting_no', name: 'starting_no' },
                { data: 'user_created', name: 'users.username' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            preDrawCallback: function () {
                closeAllActionMenus();
            },
            drawCallback: function () {
                bindPrefixRowActions();
            }
        });
    }

    $(document)
        .off('shown.bs.dropdown' + pageNamespace, '.vat-prefix-action-dropdown')
        .on('shown.bs.dropdown' + pageNamespace, '.vat-prefix-action-dropdown', function () {
            portalActionMenu($(this));
        });

    $(document)
        .off('hide.bs.dropdown' + pageNamespace, '.vat-prefix-action-dropdown')
        .on('hide.bs.dropdown' + pageNamespace, '.vat-prefix-action-dropdown', function () {
            restoreActionMenu($(this));
        });

    $(window)
        .off('scroll' + pageNamespace + ' resize' + pageNamespace)
        .on('scroll' + pageNamespace + ' resize' + pageNamespace, function () {
            closeAllActionMenus();
        });

    $(document).ready(function () {
        closeAllActionMenus();
        cleanupModalBackdrop();
        bindPrefixModalTriggers($(document));
        initPrefixTable();
    });
})(jQuery);
</script>

{{-- S664: un-clips the Action menu so Edit and Delete are visible. --}}
@include('vat::partials.vat_prefix_action_menu')

@endsection
