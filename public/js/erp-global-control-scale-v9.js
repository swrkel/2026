/* ERP GDS V9 helper - keeps dynamically created tabs/buttons aligned with global control scale. */
(function () {
    'use strict';
    function normalizeErpTabs() {
        document.querySelectorAll('.nav-tabs, .nav-pills, .erp-tabs, .erp-tab-list').forEach(function (el) {
            el.classList.add('erp-gds-tabs-ready');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', normalizeErpTabs);
    } else {
        normalizeErpTabs();
    }
    if (window.MutationObserver) {
        new MutationObserver(normalizeErpTabs).observe(document.documentElement, {childList:true, subtree:true});
    }
})();
