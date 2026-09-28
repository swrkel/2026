<?php
namespace Modules\Tailoring\CustomerCentre\Services;
use Illuminate\Http\Request;

class TailoringCustomerCentreService
{
    public function summary(Request $request): array
    {
        return ['total_customers'=>0,'new_this_month'=>0,'active_orders'=>0,'outstanding_balance'=>0];
    }
    public function profile($id): array
    {
        return ['id'=>$id,'name'=>'','mobile'=>'','measurements'=>[],'orders'=>[],'wardrobe'=>[],'payments'=>[],'alterations'=>[],'trials'=>[]];
    }
}
