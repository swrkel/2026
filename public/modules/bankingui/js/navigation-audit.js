(function () {
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-banking-nav], [data-module-key]');
        if (!link || !window.fetch) return;
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch('/banking/navigation-audit', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token ? token.content : ''},
            body: JSON.stringify({module_key: link.getAttribute('data-banking-nav') || link.getAttribute('data-module-key'), route_name: link.getAttribute('href'), url: link.href})
        }).catch(function () {});
    });
})();
