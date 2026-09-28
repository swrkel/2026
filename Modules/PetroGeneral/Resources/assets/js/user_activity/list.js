$(document).ready(function () {
    if ($('#pg_user_activity_table').length && $.fn.DataTable) {
        $('#pg_user_activity_table').DataTable({ processing: true, serverSide: true, ajax: { url: '/petro-general/settlement/activity-report', data: function(d){ d.date_range=$('#pg_activity_date_range').val(); d.user_id=$('#pg_activity_user_id').val(); d.location_id=$('#pg_activity_location_id').val(); } }, columns: [ {data:'created_at',name:'created_at'}, {data:'user',name:'user'}, {data:'location',name:'location'}, {data:'action',name:'action'}, {data:'description',name:'description'} ] });
        $('#pg_activity_date_range,#pg_activity_user_id,#pg_activity_location_id').on('change', function(){ $('#pg_user_activity_table').DataTable().ajax.reload(); });
    }
});
