(function(window, document){
    'use strict';
    function initToolbar(){
        document.querySelectorAll('[data-leads-new-column-toggle]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var target = document.querySelector(btn.getAttribute('data-leads-new-column-toggle'));
                if(target){ target.classList.toggle('hidden'); }
            });
        });
    }
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', initToolbar);
    } else {
        initToolbar();
    }
})(window, document);
