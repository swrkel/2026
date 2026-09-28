(function () {
    'use strict';
    document.addEventListener('click', function (event) {
        if (event.target.matches('.stn-btn')) {
            event.target.classList.add('stn-clicked');
        }
    });
})();
