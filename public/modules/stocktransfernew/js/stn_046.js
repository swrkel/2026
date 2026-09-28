(function () {
    'use strict';
    document.addEventListener('submit', function (event) {
        if (event.target && event.target.classList.contains('stn046-inline-form')) {
            var button = event.target.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.innerText = 'Running...';
            }
        }
    });
})();
