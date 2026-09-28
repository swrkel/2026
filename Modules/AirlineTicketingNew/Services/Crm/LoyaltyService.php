<?php
namespace Modules\AirlineTicketingNew\Services\Crm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\LoyaltyTransaction;
class LoyaltyService {
    public function transact(array $data): LoyaltyTransaction {
        return DB::transaction(function()use($data){
            $balance=(float)LoyaltyTransaction::where('business_id',$data['business_id'])->where('passenger_id',$data['passenger_id'])->lockForUpdate()->latest('id')->value('balance_after');
            $new=round($balance+(float)$data['points'],2);
            if($new<0)throw ValidationException::withMessages(['points'=>'Insufficient loyalty points.']);
            return LoyaltyTransaction::create(array_merge($data,['balance_after'=>$new]));
        });
    }
}
