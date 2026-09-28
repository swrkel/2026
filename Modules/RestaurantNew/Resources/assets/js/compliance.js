(function(){
    if (window.jQuery && $.fn.DataTable) {
        $('.restaurantnew-datatable').DataTable({responsive:true, pageLength:25, dom:'Bfrtip', buttons:['csv','excel','pdf','print','colvis']});
    }
})();
