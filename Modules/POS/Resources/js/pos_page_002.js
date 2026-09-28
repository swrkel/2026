(function () {
    function filterTable(input) {
        var target = document.querySelector(input.getAttribute('data-target'));
        if (!target) return;
        var value = (input.value || '').toLowerCase();
        target.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = row.innerText.toLowerCase().indexOf(value) >= 0 ? '' : 'none';
        });
    }
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('pos-instant-search')) filterTable(e.target);
        if (e.target.classList.contains('pos-calc-variance') || e.target.classList.contains('pos-actual-count')) {
            var form = e.target.closest('form'); if (!form) return;
            var expectedEl = form.querySelector('[name="expected_amount"], .pos-calc-variance');
            var actualEl = form.querySelector('[name="actual_amount"], [name="actual_closing_amount"]');
            var out = form.querySelector('.pos-variance-output');
            if (!out || !actualEl) return;
            var expected = parseFloat((expectedEl && (expectedEl.value || expectedEl.getAttribute('data-expected'))) || 0) || 0;
            var actual = parseFloat(actualEl.value || 0) || 0;
            out.value = (actual - expected).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4});
        }
    });
    document.addEventListener('click', function (e) {
        var printBtn = e.target.closest('.pos-print, .pos-print-page');
        if (printBtn) { window.print(); }
        var noteBtn = e.target.closest('.pos-note-btn');
        if (noteBtn) { alert(noteBtn.getAttribute('data-note') || 'No note'); }
        var exportBtn = e.target.closest('.pos-export');
        if (exportBtn) {
            var table = document.querySelector(exportBtn.getAttribute('data-table')); if (!table) return;
            var csv = Array.from(table.querySelectorAll('tr')).map(function(row){return Array.from(row.children).filter(function(cell){return !cell.classList.contains('no-print');}).map(function(cell){return '"'+cell.innerText.replace(/"/g,'""')+'"';}).join(',');}).join('\n');
            var blob = new Blob([csv], {type:'text/csv'}); var url = URL.createObjectURL(blob); var a = document.createElement('a'); a.href=url; a.download='pos_export.csv'; a.click(); URL.revokeObjectURL(url);
        }
    });
    if (window.jQuery && jQuery.fn.select2) { jQuery(function(){ jQuery('.select2').select2({width:'100%'}); }); }
})();
