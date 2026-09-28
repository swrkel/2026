(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var table = document.getElementById('stn-transporter-scorecard-table');
        if (!table) { return; }
        table.querySelectorAll('.stn-score').forEach(function (badge) {
            var value = parseInt(badge.textContent, 10) || 0;
            badge.setAttribute('title', value >= 80 ? 'Good transporter performance' : (value >= 50 ? 'Needs monitoring' : 'Needs urgent review'));
        });
    });
})();
