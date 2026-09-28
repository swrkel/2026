(function(){
    'use strict';
    window.BeautySaloonsPortal = window.BeautySaloonsPortal || {};
    window.BeautySaloonsPortal.init = function(){
        document.querySelectorAll('[data-bs-confirm]').forEach(function(btn){
            btn.addEventListener('click', function(e){
                if(!confirm(btn.getAttribute('data-bs-confirm'))){ e.preventDefault(); }
            });
        });
    };
    document.addEventListener('DOMContentLoaded', window.BeautySaloonsPortal.init);
})();
