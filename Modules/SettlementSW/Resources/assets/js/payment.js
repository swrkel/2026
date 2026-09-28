/*
 * Settlement SW module-local payment JavaScript entry point.
 * Keeps SettlementSW views away from global /public/js/payment.js dependency.
 * Detailed Add Payment handlers live in swsettlement/add-payment.js.
 */
(function (window) {
    window.SettlementSwPaymentLoaded = true;
})(window);
