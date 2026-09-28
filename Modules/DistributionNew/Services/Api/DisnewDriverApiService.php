<?php

namespace Modules\DistributionNew\Services\Api;

use Illuminate\Http\Request;
use Modules\DistributionNew\Entities\DisnewDeliveryCheckpoint;
use Modules\DistributionNew\Entities\DisnewEpod;

class DisnewDriverApiService
{
    public function trips(Request $request): array { return ['success'=>true,'data'=>[],'message'=>'Driver trips endpoint ready']; }
    public function acceptTrip($trip, Request $request): array { return ['success'=>true,'trip_id'=>$trip,'message'=>'Trip accepted']; }
    public function checkpoint(Request $request): array
    {
        $checkpoint = DisnewDeliveryCheckpoint::create($request->only(['business_id','location_id','trip_id','delivery_id','checkpoint_type','latitude','longitude','remarks']));
        return ['success'=>true,'checkpoint_id'=>$checkpoint->id];
    }
    public function epod(Request $request): array
    {
        $epod = DisnewEpod::create($request->only(['business_id','location_id','trip_id','delivery_id','receiver_name','receiver_mobile','signature_path','photo_path','remarks']));
        return ['success'=>true,'epod_id'=>$epod->id];
    }
}
