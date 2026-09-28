(function () {
    window.BS011BranchResources = {
        init: function () {
            if (window.jQuery && $.fn.DataTable) {
                $('.bs011-datatable').DataTable({scrollX: true, autoWidth: false});
            }
        }
    };
    if (document.readyState !== 'loading') { window.BS011BranchResources.init(); }
    else { document.addEventListener('DOMContentLoaded', window.BS011BranchResources.init); }
})();
