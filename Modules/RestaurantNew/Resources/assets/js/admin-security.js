(function(){
    document.addEventListener('change', function(e){
        if(e.target.matches('.rn-feature-card select')){ e.target.closest('form').classList.add('rn-dirty'); }
    });
})();
