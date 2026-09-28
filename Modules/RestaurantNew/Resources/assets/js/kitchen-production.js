(function () {
    function postAction(card, action) {
        var id = card.getAttribute('data-id');
        var token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/restaurant-new/kitchen/production/' + id + '/' + action, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}
        }).then(function (r) { return r.json(); }).then(function () { window.location.reload(); });
    }

    document.addEventListener('click', function (e) {
        var button = e.target.closest('[data-action]');
        if (!button) return;
        var action = button.getAttribute('data-action');
        var card = button.closest('.rn-kitchen-card');
        if (action === 'print') {
            window.print();
            return;
        }
        postAction(card, action);
    });

    var refresh = document.getElementById('rn-refresh-kitchen-board');
    if (refresh) refresh.addEventListener('click', function () { window.location.reload(); });
    setInterval(function () { if (document.getElementById('rn-kitchen-board')) window.location.reload(); }, 45000);
})();
