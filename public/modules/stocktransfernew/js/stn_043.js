(function(){
    'use strict';
    document.addEventListener('click', function(e){
        if(e.target && e.target.matches('[data-stn-confirm]')){
            if(!confirm(e.target.getAttribute('data-stn-confirm'))){ e.preventDefault(); }
        }
    });
})();
