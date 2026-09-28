<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceVehicle;
class TimelineController extends AutoServiceBaseController
{
 public function show($vehicleId){ $vehicle=AutoServiceVehicle::findOrFail($vehicleId); $events=DB::table('auto_service_timeline')->where('vehicle_id',$vehicleId)->orderByDesc('event_at')->paginate(50); return view('autoservice::timeline.show',compact('vehicle','events')); }
}
