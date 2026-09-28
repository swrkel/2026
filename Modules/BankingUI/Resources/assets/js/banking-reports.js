(function () {
    document.addEventListener('click', function (event) {
        const exportButton = event.target.closest('.bkg-export');
        if (!exportButton) return;
        const table = document.querySelector('.bkg-report-table');
        const reportKey = table ? table.getAttribute('data-report-key') : null;
        const type = exportButton.getAttribute('data-export');
        if (!reportKey) return;
        const params = new URLSearchParams(new FormData(document.getElementById('bkg-report-filter-form')));
        window.location.href = '/banking/reports/' + reportKey + '/export/' + type + '?' + params.toString();
    });
})();
