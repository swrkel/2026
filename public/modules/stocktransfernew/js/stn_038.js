(function(){
    document.querySelectorAll('.stn38-inline-form .btn-danger,.stn38-inline-form .btn-warning').forEach(function(btn){
        btn.addEventListener('click', function(e){
            if(!confirm('Please confirm this AI replenishment review action.')){ e.preventDefault(); }
        });
    });
})();
