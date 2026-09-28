(function () {
    'use strict';
    function post(url, payload) {
        return fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')}, body:JSON.stringify(payload || {})}).then(r => r.json());
    }
    window.RestaurantNewAi = { post: post };
})();
