(function () {
    window.POSAdvanced = window.POSAdvanced || {};
    window.POSAdvanced.formatAmount = function (value, precision) {
        precision = precision || 4;
        return Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: precision, maximumFractionDigits: precision});
    };
})();
