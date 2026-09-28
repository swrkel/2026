(function () {
    'use strict';

    var page = 'advances';

    function initSupplierFinancialPage() {
        var wrapper = document.querySelector('[data-supplier-financial-page="' + page + '"]');
        if (!wrapper) return;

        wrapper.querySelectorAll('.supplier-apply-filter').forEach(function (button) {
            button.addEventListener('click', function () {
                document.dispatchEvent(new CustomEvent('suppliers:financial:reload', { detail: { page: page } }));
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSupplierFinancialPage);
    } else {
        initSupplierFinancialPage();
    }
})();
