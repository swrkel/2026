(function () {
    'use strict';

    function parseAmount(value) {
        var parsed = parseFloat(value);
        return isNaN(parsed) ? 0 : parsed;
    }

    document.addEventListener('input', function (event) {
        if (!event.target.classList.contains('rn-bill-calc')) {
            return;
        }

        var form = event.target.closest('form');
        if (!form) {
            return;
        }

        var discount = parseAmount(form.querySelector('[name="discount_amount"]')?.value);
        var tax = parseAmount(form.querySelector('[name="tax_amount"]')?.value);
        var service = parseAmount(form.querySelector('[name="service_charge_amount"]')?.value);
        var roundOff = parseAmount(form.querySelector('[name="round_off_amount"]')?.value);
        var preview = form.querySelector('[data-rn-grand-total-preview]');

        if (preview) {
            var subtotal = parseAmount(preview.getAttribute('data-subtotal'));
            preview.textContent = (subtotal - discount + tax + service + roundOff).toFixed(4);
        }
    });
})();
