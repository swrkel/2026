(function () {
    window.RestaurantNewHaccp = window.RestaurantNewHaccp || {};
    window.RestaurantNewHaccp.refreshTimers = function () {
        document.querySelectorAll('[data-restnew-haccp-timer]').forEach(function (el) {
            el.textContent = new Date().toLocaleTimeString();
        });
    };
})();
