(function(){
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.restnew-datatable').forEach(function(table){
            if (window.jQuery && jQuery.fn.DataTable) { jQuery(table).DataTable({responsive:true}); }
        });
    });
})();
