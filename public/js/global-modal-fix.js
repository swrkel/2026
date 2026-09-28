/* GLOBAL_MODAL_ROOT_FIX_V11
 * Stable Add/Edit modal loader for Laravel ERP.
 * Loaded before public/js/app.js so a later page/app error cannot leave Add buttons dead.
 */
(function (window, document) {
    'use strict';

    function ensureJquery(callback) {
        if (window.jQuery) {
            callback(window.jQuery);
            return;
        }
        setTimeout(function () { ensureJquery(callback); }, 50);
    }

    function normaliseContainer(selector) {
        selector = selector || '.view_modal';
        selector = ('' + selector).trim();
        if (!selector) selector = '.view_modal';
        if (selector !== 'body' && selector.charAt(0) !== '.' && selector.charAt(0) !== '#') {
            selector = '.' + selector;
        }
        return selector;
    }

    function safeModalHtml(message) {
        return '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
            '<div class="modal-body text-center" style="padding:30px;">' + message + '</div>' +
            '</div></div>';
    }

    function ensureContainer($, selector) {
        selector = normaliseContainer(selector);
        var $container = $(selector).first();
        if ($container.length) return $container;

        var className = 'view_modal';
        if (selector.charAt(0) === '.') className = selector.substring(1).replace(/[^A-Za-z0-9_-]/g, ' ');
        if (selector.charAt(0) === '#') {
            $('body').append('<div id="' + selector.substring(1).replace(/[^A-Za-z0-9_-]/g, '') + '" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>');
        } else {
            $('body').append('<div class="modal fade ' + className + '" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>');
        }
        return $(selector).first();
    }

    function openAjaxModal($, element) {
        var $btn = $(element);
        var url = $btn.attr('data-href') || $btn.data('href') || $btn.attr('href');

        if (!url || url === '#') return true;

        var containerSelector = $btn.attr('data-container') || $btn.data('container') || '.view_modal';
        var $container = ensureContainer($, containerSelector);

        if (!$container.length || !$.fn.modal) {
            window.location.href = url;
            return false;
        }

        $container.html(safeModalHtml('<i class="fa fa-spinner fa-spin"></i> Loading...'));
        $container.modal('show');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function (html) {
                $container.html(html);
                $container.modal('show');

                try {
                    if ($.fn.select2) {
                        $container.find('select.select2, select.form-control.select2').each(function () {
                            var $el = $(this);
                            try { if ($el.data('select2')) $el.select2('destroy'); } catch (ignore) {}
                            $el.select2({ dropdownParent: $container });
                        });
                    }
                    if ($.fn.datepicker) {
                        $container.find('.datepicker').datepicker({ autoclose: true });
                    }
                    if (typeof window.__currency_convert_recursively === 'function') {
                        window.__currency_convert_recursively($container);
                    }
                } catch (ignore) {}
            },
            error: function (xhr) {
                var msg = 'Unable to open the form.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
                else if (xhr && xhr.status) msg += ' Error ' + xhr.status + '.';
                $container.html(safeModalHtml('<div class="text-danger">' + msg + '</div>'));
                $container.modal('show');
            }
        });
        return false;
    }

    ensureJquery(function ($) {
        $(document)
            .off('click.globalModalRootFixV11', '.btn-modal, .vat-btn-modal, .btn-vat-modal')
            .on('click.globalModalRootFixV11', '.btn-modal, .vat-btn-modal, .btn-vat-modal', function (e) {
                var handled = openAjaxModal($, this);
                if (handled === false) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }
                return true;
            });
    });
})(window, document);
