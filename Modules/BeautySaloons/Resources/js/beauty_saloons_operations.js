(function () {
    window.BeautySaloonsOperations = window.BeautySaloonsOperations || {};
    window.BeautySaloonsOperations.init = function () {
        $('.beauty-saloons-table').DataTable({scrollX: true, order: [[0, 'desc']]});
    };
})();
