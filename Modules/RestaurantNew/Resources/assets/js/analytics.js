(function () {
    'use strict';
    window.RestaurantNewAnalytics = {
        generateForecast: function (url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload || {})
            }).then(function (response) { return response.json(); });
        }
    };
})();
