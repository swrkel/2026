document.addEventListener('click', function(e){
    if(e.target && e.target.id === 'expnew-check-all'){
        document.querySelectorAll('#expnew-approval-table tbody input[type="checkbox"]').forEach(function(cb){cb.checked=e.target.checked;});
    }
});
