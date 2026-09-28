<script>
/*
 * S536 VAT page stability, modal and AJAX-form controller.
 *
 * VAT pages have historically been affected by several global Bootstrap/jQuery
 * handlers acting on the same tabs and modal buttons. This controller owns only
 * VAT elements marked with data-vat-stable-tabs or .vat-modal-ajax-form, so one
 * click produces one tab transition and one form submission.
 */
(function ($) {
    'use strict';

    if (window.__vatStableUiControllerS536) {
        return;
    }
    window.__vatStableUiControllerS536 = true;

    var modalTriggerSelector = [
        '.settlement_tabs .vat-btn-modal',
        '.settlement_tabs .btn-modal[data-container=".fuel_tank_modal"]',
        '.vat-action-menu-group .vat-btn-modal',
        '.vat-action-menu-group .btn-modal[data-container=".fuel_tank_modal"]',
        '.vat-action-portal-menu .vat-btn-modal',
        '.vat-action-portal-menu .btn-modal[data-container=".fuel_tank_modal"]',
        '.settlement_tabs .add_fuel_tank',
        '.vat-instant-add'
    ].join(', ');

    var activeRequest = null;
    var activeUrl = null;

    function visibleModalExists() {
        return $('.modal.in:visible, .modal.show:visible').length > 0;
    }

    function cleanupModalState(force) {
        window.setTimeout(function () {
            if (!force && visibleModalExists()) {
                return;
            }

            $('.modal-backdrop').remove();
            $('body')
                .removeClass('modal-open')
                .css({
                    'padding-right': '',
                    'overflow': ''
                });

            $('.btn-group.open').removeClass('open');
        }, 0);
    }

    function clearOldVatModalCache() {
        try {
            var removeKeys = [];
            for (var i = 0; i < window.sessionStorage.length; i++) {
                var key = window.sessionStorage.key(i);
                if (key && (
                    key.indexOf('vat_modal_html_') === 0 ||
                    key.indexOf('vat_modal_html_is1566:') === 0
                )) {
                    removeKeys.push(key);
                }
            }

            for (var j = 0; j < removeKeys.length; j++) {
                window.sessionStorage.removeItem(removeKeys[j]);
            }
        } catch (ignore) {
            // sessionStorage may be unavailable in private/restricted browsers.
        }
    }

    function getModal() {
        var $modal = $('.fuel_tank_modal').first();

        if (!$modal.length) {
            $modal = $('<div class="modal fade fuel_tank_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>');
            $('body').append($modal);
        }

        $modal
            .off('hidden.bs.modal.s536VatModal')
            .on('hidden.bs.modal.s536VatModal', function () {
                if (activeRequest && activeRequest.readyState !== 4) {
                    activeRequest.abort();
                }

                activeRequest = null;
                activeUrl = null;

                $(this)
                    .removeData('bs.modal')
                    .empty();

                cleanupModalState(false);
            });

        return $modal;
    }

    function getUrl(element) {
        var $element = $(element);
        return $element.attr('data-href') || $element.data('href') || $element.attr('href');
    }

    function loadingHtml() {
        return '' +
            '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                        '<h4 class="modal-title">' +
                            '<i class="fa fa-spinner fa-spin"></i> Loading' +
                        '</h4>' +
                    '</div>' +
                    '<div class="modal-body text-center" style="padding:30px;">' +
                        'Loading latest form...' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function initModalContent($modal) {
        if (!$modal || !$modal.length) {
            return;
        }

        if ($.fn.select2) {
            $modal.find('select.select2').each(function () {
                var $select = $(this);

                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    allowClear: true
                });
            });
        }

        if ($.fn.iCheck) {
            $modal
                .find('input[type="checkbox"].input-icheck, input[type="radio"].input-icheck')
                .each(function () {
                    var $input = $(this);
                    if (!$input.parent().hasClass('icheckbox_square-blue') &&
                        !$input.parent().hasClass('iradio_square-blue')) {
                        $input.iCheck({
                            checkboxClass: 'icheckbox_square-blue',
                            radioClass: 'iradio_square-blue'
                        });
                    }
                });
        }

        $modal
            .off('change.s536VatPeriod', 'select[name="vat_period"]')
            .on('change.s536VatPeriod', 'select[name="vat_period"]', function () {
                var isCustom = $(this).val() === 'custom';
                $modal.find('#custom_fields').toggle(isCustom);
                $modal
                    .find('#report_cycle_starting_date, #report_cycle_ending_date')
                    .prop('required', isCustom);
            });

        $modal.find('select[name="vat_period"]').triggerHandler('change.s536VatPeriod');
    }

    function showModal($modal) {
        if (!$modal.hasClass('in') && !$modal.hasClass('show')) {
            cleanupModalState(false);
            $modal.modal({
                backdrop: true,
                keyboard: true,
                show: true
            });
        }
    }

    function renderModal($modal, html) {
        $modal.html(html);
        showModal($modal);
        initModalContent($modal);
    }

    function openFresh(element, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) {
                event.stopImmediatePropagation();
            }
        }

        var url = getUrl(element);
        if (!url || url === '#') {
            return false;
        }

        if (typeof window.closeVatActionMenus === 'function') {
            window.closeVatActionMenus();
        }

        if (activeRequest && activeRequest.readyState !== 4 && activeUrl === url) {
            return false;
        }

        clearOldVatModalCache();

        if (activeRequest && activeRequest.readyState !== 4) {
            activeRequest.abort();
        }

        activeUrl = url;

        var $modal = getModal();
        $modal.html(loadingHtml());
        showModal($modal);

        activeRequest = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .done(function (html) {
            renderModal($modal, html);
        })
        .fail(function (xhr, status) {
            if (status === 'abort') {
                return;
            }

            $modal.modal('hide');

            var message = 'Unable to open the VAT form. Please refresh and try again.';
            if (xhr && xhr.status === 409 && xhr.responseText) {
                message = $('<div>').html(xhr.responseText).text().trim() || message;
            } else if (xhr && xhr.status >= 500) {
                message = 'Unable to open the VAT form due to a server error. Please check the Laravel log.';
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else {
                window.alert(message);
            }
        })
        .always(function () {
            activeRequest = null;
            activeUrl = null;
        });

        return false;
    }

    function getStableTabDetails(link) {
        var $link = $(link);
        var $list = $link.closest('ul[data-vat-stable-tabs="1"]');

        if (!$list.length) {
            return null;
        }

        var targetSelector = $link.attr('data-target') || $link.attr('href');
        var contentSelector = $list.attr('data-vat-tab-content');

        if (!targetSelector || targetSelector.charAt(0) !== '#' || !contentSelector) {
            return null;
        }

        var targetElement = document.getElementById(targetSelector.substring(1));
        var $content = $(contentSelector).first();

        if (!targetElement || !$content.length || !$.contains($content[0], targetElement)) {
            return null;
        }

        return {
            $link: $link,
            $list: $list,
            $content: $content,
            $target: $(targetElement)
        };
    }

    function activateStableTab(link, event, emitEvents) {
        var details = getStableTabDetails(link);
        if (!details) {
            return false;
        }

        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) {
                event.stopImmediatePropagation();
            }
        }

        var $previous = details.$list.children('li.active').children('a').first();

        if (details.$link.parent('li').hasClass('active') && details.$target.hasClass('active')) {
            cleanupModalState(false);
            return true;
        }

        if (emitEvents !== false) {
            if ($previous.length) {
                $previous.trigger($.Event('hide.bs.tab', { relatedTarget: details.$link[0] }));
            }
            details.$link.trigger($.Event('show.bs.tab', { relatedTarget: $previous[0] || null }));
        }

        details.$list.children('li').removeClass('active');
        details.$list.find('a[data-toggle="tab"]').removeClass('active').attr({
            'aria-selected': 'false',
            'tabindex': '-1'
        });

        details.$content.children('.tab-pane').removeClass('active in').attr('aria-hidden', 'true');

        details.$link.parent('li').addClass('active');
        details.$link.addClass('active').attr({
            'aria-selected': 'true',
            'tabindex': '0'
        });
        details.$target.addClass('active in').attr('aria-hidden', 'false');

        cleanupModalState(false);

        if (emitEvents !== false) {
            if ($previous.length) {
                $previous.trigger($.Event('hidden.bs.tab', { relatedTarget: details.$link[0] }));
            }
            details.$link.trigger($.Event('shown.bs.tab', { relatedTarget: $previous[0] || null }));
        }

        return true;
    }

    function initialiseStableTabs() {
        $('ul[data-vat-stable-tabs="1"]').each(function () {
            var $list = $(this);
            var $activeLink = $list.children('li.active').children('a[data-toggle="tab"]').first();

            if (!$activeLink.length) {
                $activeLink = $list.find('a[data-toggle="tab"]').first();
            }

            if ($activeLink.length) {
                activateStableTab($activeLink[0], null, false);
            }
        });
    }

    function reloadDataTable(selector) {
        if (!selector || !$.fn.dataTable) {
            return;
        }

        var $table = $(selector).first();
        if ($table.length && $.fn.dataTable.isDataTable($table[0])) {
            $table.DataTable().ajax.reload(null, false);
        }
    }

    function notifySuccess(message) {
        if (typeof toastr !== 'undefined' && toastr.success) {
            toastr.success(message);
        } else {
            window.alert(message);
        }
    }

    function notifyError(message) {
        if (typeof toastr !== 'undefined' && toastr.error) {
            toastr.error(message);
        } else {
            window.alert(message);
        }
    }

    function firstAjaxError(xhr) {
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.responseJSON.errors) {
                var keys = Object.keys(xhr.responseJSON.errors);
                if (keys.length && xhr.responseJSON.errors[keys[0]].length) {
                    return xhr.responseJSON.errors[keys[0]][0];
                }
            }

            if (xhr.responseJSON.msg) {
                return xhr.responseJSON.msg;
            }
        }

        return 'Unable to save the VAT details. Please check the entered values and try again.';
    }

    $(document)
        .off('submit.s536VatModalForm', 'form.vat-modal-ajax-form')
        .on('submit.s536VatModalForm', 'form.vat-modal-ajax-form', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var form = this;
            var $form = $(form);

            if (form.checkValidity && !form.checkValidity()) {
                form.reportValidity();
                return false;
            }

            if ($form.data('vatSubmitting')) {
                return false;
            }

            $form.data('vatSubmitting', true);
            var $submitButtons = $form.find('button[type="submit"], input[type="submit"]');
            $submitButtons.prop('disabled', true);

            var hasFileInput = $form.find('input[type="file"]').length > 0;
            var requestData = hasFileInput ? new FormData(form) : $form.serialize();
            var reloadSelector = $form.attr('data-vat-reload-table');
            var $modal = $form.closest('.modal');

            $.ajax({
                url: $form.attr('action'),
                type: ($form.attr('method') || 'POST').toUpperCase(),
                data: requestData,
                processData: !hasFileInput,
                contentType: hasFileInput ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .done(function (result) {
                if (!result || result.success !== true) {
                    var failureMessage = result && result.msg
                        ? result.msg
                        : 'Unable to save the VAT details.';
                    notifyError(failureMessage);
                    return;
                }

                notifySuccess(result.msg || 'Saved successfully.');

                if ($modal.length) {
                    $modal.one('hidden.bs.modal.s536VatReset', function () {
                        form.reset();
                        initModalContent($modal);
                    });
                    $modal.modal('hide');
                }

                reloadDataTable(reloadSelector);
                $(document).trigger('vat:saved', [reloadSelector, result]);
            })
            .fail(function (xhr) {
                notifyError(firstAjaxError(xhr));
            })
            .always(function () {
                $form.data('vatSubmitting', false);
                $submitButtons.prop('disabled', false);
            });

            return false;
        });

    // Keep legacy function names used by older VAT buttons.
    window.openVatSettingsFastModalS303 = openFresh;
    window.openVatSettingsFastModalV19 = openFresh;
    window.openVatSettingsFastModalIS1566 = openFresh;
    window.openVatSettingsFastModalIS1571 = openFresh;
    window.openVatStableModalS534 = openFresh;
    window.openVatStableModalS536 = openFresh;

    clearOldVatModalCache();

    /*
     * Capture tab and modal clicks before global delegated Bootstrap handlers.
     * This makes one VAT click equal exactly one navigation/modal operation.
     */
    document.addEventListener('click', function (event) {
        var target = event.target;

        while (target && target !== document) {
            if (target.matches && target.matches('ul[data-vat-stable-tabs="1"] a[data-toggle="tab"]')) {
                activateStableTab(target, event, true);
                return;
            }

            if (target.matches && target.matches(modalTriggerSelector)) {
                openFresh(target, event);
                return;
            }

            target = target.parentNode;
        }
    }, true);

    $(function () {
        initialiseStableTabs();
        cleanupModalState(false);
    });

    $(window)
        .off('pageshow.s536VatUi')
        .on('pageshow.s536VatUi', function () {
            initialiseStableTabs();
            cleanupModalState(false);
        });
})(jQuery);
</script>
