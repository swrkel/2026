(function () {
    function getPayload() {
        var el = document.getElementById('bs_dashboard_payload');
        if (!el) return {};
        try { return JSON.parse(el.textContent || '{}'); } catch (e) { return {}; }
    }
    function simpleTextChart(canvasId, rows, labelKey, valueKey) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillText('Chart data loaded: ' + (rows ? rows.length : 0) + ' record(s)', 20, 30);
        (rows || []).slice(0, 8).forEach(function (r, i) {
            ctx.fillText((r[labelKey] || r.method || r.date || 'Item') + ': ' + (r[valueKey] || r.total || 0), 20, 60 + (i * 22));
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        var payload = getPayload();
        simpleTextChart('bs_revenue_trend', payload.revenue_trend || [], 'date', 'total');
        simpleTextChart('bs_payment_mix', payload.payment_mix || [], 'method', 'total');
    });
})();
