(function(){
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.stn-btn').forEach(function(btn){
            btn.addEventListener('mousedown', function(){ btn.classList.add('active'); });
            btn.addEventListener('mouseup', function(){ btn.classList.remove('active'); });
        });
    });
})();
