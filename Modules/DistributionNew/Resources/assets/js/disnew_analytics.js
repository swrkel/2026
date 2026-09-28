(function($){
    'use strict';
    $(function(){
        if ($.fn.DataTable) {
            $('.disnew-datatable').DataTable({
                dom: 'Bfrtip',
                buttons: ['csv', 'excel', 'pdf', 'print', 'colvis'],
                pageLength: 25
            });
        }
    });
})(jQuery);
