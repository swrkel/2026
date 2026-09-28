<?php
namespace Modules\DistributionNew\Services\LiveOperations;
use Illuminate\Http\Request;
use Modules\DistributionNew\Entities\LiveOperations\VehicleLocation;
use Modules\DistributionNew\Entities\LiveOperations\DriverStatus;
use Modules\DistributionNew\Entities\LiveOperations\DeliveryTimeline;
class LiveOperationsApiService
{
 public function saveDriverStatus(Request $r): array { DriverStatus::create($r->only(['business_id','business_location_id','driver_id','status','remarks','created_by'])); return ['success'=>true]; }
 public function saveVehicleLocation(Request $r): array { VehicleLocation::create($r->only(['business_id','business_location_id','vehicle_id','trip_id','latitude','longitude','speed','recorded_at','created_by'])); return ['success'=>true]; }
 public function saveDeliveryTimeline(Request $r): array { DeliveryTimeline::create($r->only(['business_id','business_location_id','delivery_id','trip_id','event_type','event_note','latitude','longitude','recorded_at','created_by'])); return ['success'=>true]; }
}
