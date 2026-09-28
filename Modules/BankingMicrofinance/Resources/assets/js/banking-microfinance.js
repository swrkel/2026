(function(){
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('[data-bkg-mfi-confirm]').forEach(function(btn){
            btn.addEventListener('click', function(e){ if(!confirm(btn.dataset.bkgMfiConfirm)){ e.preventDefault(); }});
        });
    });
})();
