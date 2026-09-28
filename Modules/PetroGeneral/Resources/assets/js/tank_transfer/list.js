$(document).ready(function () {
    if ($('#pg_tank_transfers_table').length && $.fn.DataTable) {
        $('#pg_tank_transfers_table').DataTable({ processing: true, serverSide: true, ajax: { url: '/petro-general/tank-transfer', data: function(d){ d.date_range=$('#pg_transfer_date_range').val(); d.location_id=$('#pg_transfer_location_id').val(); d.from_tank=$('#pg_transfer_from_tank').val(); d.to_tank=$('#pg_transfer_to_tank').val(); } }, columns: [ {data:'date',name:'date'}, {data:'location_name',name:'business_locations.name'}, {data:'transfer_no',name:'transfer_no'}, {data:'from_tank',name:'from_tank.fuel_tank_number'}, {data:'from_qty',name:'from_qty'}, {data:'to_tank',name:'to_tank.fuel_tank_number'}, {data:'to_qty',name:'to_qty'}, {data:'product',name:'products.name'}, {data:'transfer_qty',name:'transfer_qty'}, {data:'user_added',name:'users.username'} ] });
        $('#pg_transfer_date_range,#pg_transfer_location_id,#pg_transfer_from_tank,#pg_transfer_to_tank').on('change', function(){ $('#pg_tank_transfers_table').DataTable().ajax.reload(); });
    }
});
