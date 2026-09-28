(function () {
    function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
    function log(msg, data) {
        var el = document.getElementById('s385-integrity-console');
        if (!el) return;
        el.textContent = msg + (data ? "\n" + JSON.stringify(data, null, 2) : '');
    }
    document.addEventListener('click', function (e) {
        if (e.target.closest('#s385-run-integrity')) {
            log('Running integrity verification...');
            fetch('/pos-module/offline-sync/reliability/verify-integrity', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ source: 'dashboard' }) })
                .then(function (r) { return r.json(); })
                .then(function (json) { log(json.ok ? 'Integrity verification completed.' : 'Integrity verification found issues.', json); })
                .catch(function (err) { log('Integrity verification failed: ' + err.message); });
        }
        if (e.target.closest('#s385-record-pass')) {
            fetch('/pos-module/offline-sync/reliability/register-scenario', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ scenario: 'Manual live reliability checkpoint', status: 'passed', notes: 'Recorded from S385 dashboard.' }) })
                .then(function (r) { return r.json(); })
                .then(function (json) { log(json.ok ? 'Manual pass recorded.' : 'Unable to record pass.', json); })
                .catch(function (err) { log('Scenario registration failed: ' + err.message); });
        }
    });
})();
