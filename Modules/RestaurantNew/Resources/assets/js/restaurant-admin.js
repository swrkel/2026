(function(){
    'use strict';
    document.addEventListener('click', function(e){
        if(e.target.classList.contains('rn-open-admin-form')){
            e.preventDefault();
            const form = document.querySelector('.rn-admin-form');
            if(form){ form.classList.toggle('d-none'); }
        }
    });
})();
