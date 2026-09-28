(function () {
    'use strict';
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.stn044-toolbar button');
        if (!btn) return;
        btn.dataset.originalText = btn.dataset.originalText || btn.innerText;
        btn.innerText = 'Please wait...';
    });
})();
