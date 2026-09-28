(function(){
    function initTable(id){
        if (window.jQuery && $.fn.DataTable && $(id).length && !$.fn.DataTable.isDataTable(id)) {
            $(id).DataTable({pageLength:25,order:[],scrollX:true});
        }
    }
    document.addEventListener('DOMContentLoaded',function(){
        initTable('#stn-reservations-table');
        initTable('#stn-reservation-candidates-table');
        document.querySelectorAll('.stn-release-form').forEach(function(form){
            form.addEventListener('submit',function(e){
                var input=form.querySelector('input[name="release_reason"]');
                if(!input || !input.value.trim()){
                    e.preventDefault(); alert('Please enter release reason.');
                }
            });
        });
    });
})();
