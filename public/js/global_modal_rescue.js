/**
 * GLOBAL_ADD_MODAL_RESCUE_20260627
 * Root-level modal loader for ERP Add/Edit buttons.
 * Runs in capture phase so buttons still work even when older page scripts fail
 * or another delegated handler is not registered.
 */
(function () {
    'use strict';

    if (window.__GLOBAL_ADD_MODAL_RESCUE_20260627__) {
        return;
    }
    window.__GLOBAL_ADD_MODAL_RESCUE_20260627__ = true;

    function closestButton(node) {
        while (node && node !== document) {
            if (node.matches && node.matches('.btn-modal, .vat-btn-modal, .btn-vat-modal')) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    function getAttr(el, name) {
        return el ? (el.getAttribute(name) || '') : '';
    }

    function normalizeContainer(selector) {
        selector = selector || '.view_modal';
        selector = String(selector).trim();
        if (!selector) {
            selector = '.view_modal';
        }
        if (selector !== 'body' && selector.charAt(0) !== '.' && selector.charAt(0) !== '#') {
            selector = '.' + selector;
        }
        return selector;
    }

    function ensureContainer(selector) {
        selector = normalizeContainer(selector);
        var container = document.querySelector(selector);
        if (!container && (selector.charAt(0) === '.' || selector.charAt(0) === '#')) {
            container = document.createElement('div');
            container.className = 'modal fade ' + selector.substring(1).replace(/[^A-Za-z0-9_\- ]/g, '');
            container.setAttribute('tabindex', '-1');
            container.setAttribute('role', 'dialog');
            container.setAttribute('aria-labelledby', 'gridSystemModalLabel');
            document.body.appendChild(container);
        }
        return container;
    }

    function showModal(container) {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(container).modal('show');
        } else {
            container.style.display = 'block';
            container.classList.add('in');
        }
    }

    function initModalContent(container) {
        if (!(window.jQuery)) {
            return;
        }
        var $container = window.jQuery(container);

        if (window.jQuery.fn.select2) {
            $container.find('select.select2, select.form-control.select2').each(function () {
                var $el = window.jQuery(this);
                try {
                    if ($el.data('select2')) {
                        $el.select2('destroy');
                    }
                    $el.select2({ dropdownParent: $container });
                } catch (err) {}
            });
        }

        if (window.jQuery.fn.datepicker) {
            $container.find('.datepicker').each(function () {
                try {
                    window.jQuery(this).datepicker({ autoclose: true });
                } catch (err) {}
            });
        }

        if (typeof window.__currency_convert_recursively === 'function') {
            try { window.__currency_convert_recursively($container); } catch (err) {}
        }
    }

    function loadIntoModal(button, url, containerSelector) {
        var container = ensureContainer(containerSelector);
        if (!container) {
            return;
        }

        container.innerHTML = '<div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-body text-center" style="padding:30px;"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>';
        showModal(container);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'text/html, */*; q=0.01');

        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            button.removeAttribute('data-global-modal-loading');

            if (xhr.status >= 200 && xhr.status < 300) {
                container.innerHTML = xhr.responseText;
                showModal(container);
                initModalContent(container);
                return;
            }

            var message = 'Unable to open the form. Server returned HTTP ' + xhr.status + '.';
            container.innerHTML = '<div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Form could not be opened</h4></div><div class="modal-body"><p>' + message + '</p></div></div></div>';
            showModal(container);
        };

        xhr.onerror = function () {
            button.removeAttribute('data-global-modal-loading');
            container.innerHTML = '<div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Form could not be opened</h4></div><div class="modal-body"><p>Network error while opening the form.</p></div></div></div>';
            showModal(container);
        };

        xhr.send();
    }

    document.addEventListener('click', function (event) {
        var button = closestButton(event.target);
        if (!button) {
            return;
        }

        var url = getAttr(button, 'data-href') || getAttr(button, 'href');
        if (!url || url === '#' || url.indexOf('javascript:') === 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
        }

        if (button.getAttribute('data-global-modal-loading') === '1') {
            return false;
        }
        button.setAttribute('data-global-modal-loading', '1');

        loadIntoModal(button, url, getAttr(button, 'data-container') || '.view_modal');
        return false;
    }, true);
})();
