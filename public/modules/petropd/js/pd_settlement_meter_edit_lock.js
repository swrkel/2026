(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function isPdSettlementPage() {
        var path = (window.location.pathname || '').toLowerCase();
        return path.indexOf('/petropd/pd-settlement') !== -1 || path.indexOf('/petro/pd-settlement') !== -1;
    }

    function normalizeText(value) {
        return (value || '').toString().toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function tableLooksLikeMeterSales(table) {
        if (!table) {
            return false;
        }

        var text = normalizeText(table.innerText || table.textContent || '');
        var hasMeterColumns = text.indexOf('starting meter') !== -1 &&
            text.indexOf('closing meter') !== -1 &&
            text.indexOf('sold qty') !== -1;

        var hasProductPumpColumns = text.indexOf('products') !== -1 && text.indexOf('pump') !== -1;

        return hasMeterColumns && hasProductPumpColumns;
    }

    function showMessage() {
        var message = 'Meter Sales opened from Pumper Dashboard cannot be edited from PD Settlement.';
        if (window.toastr && typeof window.toastr.warning === 'function') {
            window.toastr.warning(message);
            return;
        }
        alert(message);
    }

    function disableEditButton(button) {
        if (!button || button.getAttribute('data-zip32-meter-edit-lock') === 'done') {
            return;
        }

        button.setAttribute('data-zip32-meter-edit-lock', 'done');
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('title', 'Meter Sales opened from Pumper Dashboard cannot be edited from PD Settlement.');
        button.classList.add('disabled');
        button.classList.add('zip32-meter-edit-disabled');

        if (button.tagName === 'BUTTON' || button.tagName === 'INPUT') {
            button.disabled = true;
        }

        if (button.tagName === 'A') {
            button.removeAttribute('href');
        }

        button.style.pointerEvents = 'auto';
        button.style.opacity = '0.55';
        button.style.cursor = 'not-allowed';

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            showMessage();
            return false;
        }, true);
    }

    function isEditButton(element) {
        if (!element) {
            return false;
        }

        var text = normalizeText(element.innerText || element.textContent || element.value || '');
        if (text !== 'edit') {
            return false;
        }

        if (element.matches && element.matches('a, button, input[type="button"], input[type="submit"]')) {
            return true;
        }

        return false;
    }

    function lockMeterSalesEditButtons() {
        if (!isPdSettlementPage()) {
            return;
        }

        var tables = document.querySelectorAll('table');
        Array.prototype.forEach.call(tables, function (table) {
            if (!tableLooksLikeMeterSales(table)) {
                return;
            }

            var actionButtons = table.querySelectorAll('a, button, input[type="button"], input[type="submit"]');
            Array.prototype.forEach.call(actionButtons, function (button) {
                if (isEditButton(button)) {
                    disableEditButton(button);
                }
            });
        });
    }

    ready(function () {
        lockMeterSalesEditButtons();
        setTimeout(lockMeterSalesEditButtons, 300);
        setTimeout(lockMeterSalesEditButtons, 1000);
        setTimeout(lockMeterSalesEditButtons, 2500);

        if (window.MutationObserver) {
            var observer = new MutationObserver(function () {
                lockMeterSalesEditButtons();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
})();
