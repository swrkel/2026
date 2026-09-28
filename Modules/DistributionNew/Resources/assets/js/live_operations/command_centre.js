$(function(){
 function refreshCommandCentre(){
  $.get('/distribution-new/live/command-centre/data', function(r){
   $('#vehicles_on_road').text(r.vehicles_on_road||0); $('#drivers_active').text(r.drivers_active||0);
   $('#deliveries_pending').text(r.deliveries_pending||0); $('#deliveries_delayed').text(r.deliveries_delayed||0);
  });
 }
 refreshCommandCentre(); setInterval(refreshCommandCentre, 60000);
});
