(function () {
    'use strict';
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form && form.action && form.action.indexOf('/production-validation/run') !== -1) {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Running Validation...';
            }
        }
    });
})();
