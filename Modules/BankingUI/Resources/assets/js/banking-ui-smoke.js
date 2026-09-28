(function () {
    'use strict';
    window.BankingUiSmoke = {
        markReviewed: function (element) {
            if (!element) return;
            element.classList.add('banking-smoke-reviewed');
        }
    };
})();
