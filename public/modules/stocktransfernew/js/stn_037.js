(function(){
    document.addEventListener('click', function(e){
        if(e.target && e.target.matches('.stn037-table form button')){
            if(!confirm('Continue with this forecasting action?')){
                e.preventDefault();
            }
        }
    });
})();
