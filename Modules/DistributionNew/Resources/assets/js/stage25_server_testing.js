(function () {
    document.addEventListener('click', function (event) {
        if (!event.target || event.target.id !== 'disnew-run-server-test') return;
        event.preventDefault();
        var output = document.getElementById('disnew-server-test-output');
        output.style.display = 'block';
        output.textContent = 'Running Distribution New server checks...';
        fetch('/distribution-new/server-testing/run', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'}
        }).then(function (res) { return res.json(); })
          .then(function (json) { output.textContent = JSON.stringify(json, null, 2); })
          .catch(function (err) { output.textContent = 'Server testing failed: ' + err.message; });
    });
})();
