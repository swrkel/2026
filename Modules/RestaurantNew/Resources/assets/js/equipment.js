(function(window, $){
    'use strict';
    $(function(){
        if ($.fn.DataTable) {
            $('.rn-datatable').DataTable({ responsive: true, pageLength: 25, dom: 'Bfrtip', buttons: ['csv','excel','pdf','print','colvis'] });
        }
        $('.rn-equipment-status-action').on('click', function(){
            return confirm($(this).data('confirm') || 'Confirm this equipment action?');
        });
    });
})(window, jQuery);
